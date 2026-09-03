<?php

class Announcements extends Controller {

    private function requireDivisionalViewer() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $allowed = ['DivisionalSecretary', 'DivisionalCoordinator', 'DivisionalTreasurer'];
        if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', $allowed, true)) {
            $this->redirect('auth/signin');
        }
        // An incomplete session must never silently grant access to Division 1.
        if (filter_var($_SESSION['division_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $this->jsonResponse(403, ['error' => 'Your account has no valid division assigned.']);
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    private function requireDivisionalSecretary() {
        $this->requireDivisionalViewer();
        if (($_SESSION['user_role'] ?? '') !== 'DivisionalSecretary') {
            $this->jsonResponse(403, ['error' => 'Only the Divisional Secretary can perform this action.']);
        }
    }

    private function requirePost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            $this->jsonResponse(405, ['error' => 'This action requires POST.']);
        }
        $token = $_POST['csrf_token'] ?? null;
        if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'], $token)) {
            $this->jsonResponse(403, ['error' => 'Invalid CSRF token. Please refresh the page.']);
        }
    }

    private function positiveId($id) {
        $value = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value === false) {
            $this->jsonResponse(404, ['error' => 'Announcement or attachment not found.']);
        }
        return $value;
    }

    /** Use the same access rule for details, downloads, and read tracking. */
    private function visibleAnnouncement($id) {
        $announcement = $this->model('AnnouncementModel')->findById($this->positiveId($id));
        $ownsDraft = $announcement && ($_SESSION['user_role'] ?? '') === 'DivisionalSecretary'
            && (int)$announcement->created_by === (int)$_SESSION['user_id'];
        if (!$announcement || (int)$announcement->organizer_division_id !== (int)$_SESSION['division_id']
            || ($announcement->status !== 'Published' && !$ownsDraft)) {
            $this->jsonResponse(404, ['error' => 'Announcement not found.']);
        }
        return $announcement;
    }

    public function index() {
        $this->requireDivisionalViewer();
        $model = $this->model('AnnouncementModel');
        $divisionId = (int)$_SESSION['division_id'];
        $canCreate = $_SESSION['user_role'] === 'DivisionalSecretary';
        $draftOwnerId = $canCreate ? (int)$_SESSION['user_id'] : 0;
        parent::view('announcements/list', [
            'title' => 'Broadcast Announcement — YouthNexus',
            'announcements' => $model->findByDivision($divisionId, ['draft_owner_id' => $draftOwnerId]),
            'counts' => $model->countByStatus($divisionId, $draftOwnerId),
            'csrf_token' => $_SESSION['csrf_token'],
            'userName' => $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Secretary',
            'userRole' => $_SESSION['user_role'],
            'canCreate' => $canCreate,
        ]);
    }

    public function create() { $this->saveAnnouncement(true); }
    public function saveDraft() { $this->saveAnnouncement(false); }
    public function update($id = null) { $this->saveAnnouncement(false, $this->positiveId($id)); }
    public function publish($id = null) { $this->saveAnnouncement(true, $this->positiveId($id)); }

    public function edit($id = null) {
        $this->requireDivisionalSecretary();
        $id = $this->positiveId($id);
        $announcement = $this->model('AnnouncementModel')->findEditable($id, $_SESSION['division_id'], $_SESSION['user_id']);
        if (!$announcement) {
            $this->jsonResponse(404, ['error' => 'Your announcement was not found.']);
        }
        $attachments = $this->model('AnnouncementAttachmentModel')->findByAnnouncementId($id);
        $this->jsonResponse(200, ['announcement' => $announcement, 'attachments' => array_map(function ($attachment) {
            return ['attachment_id' => (int)$attachment->attachment_id, 'file_name' => $attachment->file_name];
        }, $attachments)]);
    }

    private function formText($key) {
        if (isset($_POST[$key]) && !is_string($_POST[$key])) {
            $this->jsonResponse(422, ['error' => 'Invalid form field: ' . $key]);
        }
        return trim($_POST[$key] ?? '');
    }

    private function saveAnnouncement($publish, $id = null) {
        $this->requireDivisionalSecretary();
        $this->requirePost();
        $title = $this->formText('title');
        $body = $this->formText('body');
        $audience = $this->formText('target_audience');
        $priority = $this->formText('priority');
        $category = $this->formText('category');
        if ($title === '' || $body === '' || mb_strlen($title) > 150 || strlen($body) > 65535 || mb_strlen($category) > 100) {
            $this->jsonResponse(422, ['error' => 'Title and body are required. Limit the title to 150 characters, category to 100, and body to 65 KB.']);
        }
        $audiences = ['AllDivisionalClubs', 'ClubPresidentsSecretaries', 'AllMembers'];
        if (($publish || $audience !== '') && !in_array($audience, $audiences, true)) {
            $this->jsonResponse(422, ['error' => 'Select a valid audience before publishing.']);
        }
        if (!in_array($priority, ['Normal', 'Urgent'], true)) {
            $this->jsonResponse(422, ['error' => 'Select Normal or Urgent priority.']);
        }
        // Validate every file before writing anything; a rejected file must not disappear silently.
        $uploads = $this->validatedUploads();
        $removalIds = $this->requestedAttachmentRemovals();
        if ($id === null && $removalIds) {
            $this->jsonResponse(422, ['error' => 'Only existing announcements have attachments to remove.']);
        }
        $model = $this->model('AnnouncementModel');
        $attachmentModel = $this->model('AnnouncementAttachmentModel');
        $auditModel = $this->model('AuditLogModel');
        $db = Database::getInstance()->getConnection();
        $savedFiles = [];
        $removedAttachments = [];
        $changed = true;
        $wasPublished = false;
        $data = [
            'title' => $title, 'body' => $body, 'target_audience' => $audience ?: null,
            'priority' => $priority, 'category' => $category ?: null, 'level' => 'Divisional',
            'organizer_division_id' => (int)$_SESSION['division_id'],
            'created_by' => (int)$_SESSION['user_id'], 'status' => $publish ? 'Published' : 'Draft',
        ];
        try {
            $db->beginTransaction();
            if ($id !== null) {
                $existing = $model->findEditable($id, $data['organizer_division_id'], $data['created_by'], true);
                if (!$existing || ($publish && $existing->status !== 'Draft')
                    || (!$publish && ($_POST['expected_status'] ?? '') !== $existing->status)) {
                    $db->rollBack();
                    $this->jsonResponse(409, ['error' => 'This announcement is unavailable or its status changed. Reopen it before saving.']);
                }
                $wasPublished = $existing->status === 'Published';
                if ($wasPublished && !in_array($audience, $audiences, true)) {
                    $db->rollBack();
                    $this->jsonResponse(422, ['error' => 'A published announcement must have a valid audience.']);
                }
                // Never trust an attachment ID from the form without checking its parent.
                foreach ($removalIds as $attachmentId) {
                    $attachment = $attachmentModel->findById($attachmentId);
                    if (!$attachment || (int)$attachment->announcement_id !== (int)$id) {
                        $db->rollBack();
                        $this->jsonResponse(409, ['error' => 'An attachment is unavailable. Reopen the editor before saving.']);
                    }
                    $removedAttachments[] = $attachment;
                }
                // Views and unchanged saves must never produce an "Edited" note.
                $changed = count($uploads) > 0 || count($removedAttachments) > 0;
                foreach (['title', 'body', 'target_audience', 'category', 'priority'] as $field) {
                    if ((string)$existing->$field !== (string)$data[$field]) { $changed = true; }
                }
                if ($changed) {
                    $model->updateContent($id, $data['organizer_division_id'], $data['created_by'], $data);
                }
                if ($publish) {
                    $model->publish($id, $data['organizer_division_id'], $data['created_by'], $data);
                }
            } else {
                $id = $model->create($data);
            }
            foreach ($uploads as $upload) {
                $path = $this->storeUpload($upload);
                $savedFiles[] = $path;
                $attachmentModel->create($id, $upload['name'], 'uploads/announcement_attachments/' . basename($path), $upload['size']);
            }
            foreach ($removedAttachments as $attachment) {
                $attachmentModel->deleteFromAnnouncement($attachment->attachment_id, $id);
            }
            if ($publish || $changed) {
                $action = $publish ? 'PUBLISH_ANNOUNCEMENT' : ($wasPublished ? 'EDIT_ANNOUNCEMENT' : 'SAVE_DRAFT_ANNOUNCEMENT');
                $auditModel->log($data['created_by'], $action, 'Announcement', $id, "Saved announcement '{$title}'");
            }
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) { $db->rollBack(); }
            // Files are outside the database transaction, so explicitly undo successful moves.
            foreach ($savedFiles as $path) { if (is_file($path)) { unlink($path); } }
            error_log('Announcement save failed: ' . $error->getMessage());
            $this->jsonResponse(500, ['error' => 'Unable to save the announcement and its attachments. Please try again.']);
        }
        // Only remove old files after commit: a failed replacement must retain the originals.
        foreach ($removedAttachments as $attachment) {
            $this->removeStoredAttachment($attachment->file_path);
        }
        $this->jsonResponse(200, ['success' => true, 'id' => $id,
            'message' => $publish ? 'Announcement published successfully.' : 'Announcement saved successfully.']);
    }

    private function requestedAttachmentRemovals() {
        $values = $_POST['remove_attachments'] ?? [];
        if (!is_array($values)) {
            $this->jsonResponse(422, ['error' => 'Invalid attachment removal selection.']);
        }
        $ids = [];
        foreach ($values as $value) {
            $id = is_string($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
            if ($id === false) {
                $this->jsonResponse(422, ['error' => 'Invalid attachment removal selection.']);
            }
            $ids[$id] = $id;
        }
        return array_values($ids);
    }

    private function removeStoredAttachment($storedPath) {
        $directory = realpath(dirname(__DIR__, 2) . '/uploads/announcement_attachments');
        $path = realpath(dirname(__DIR__, 2) . '/' . $storedPath);
        // Use database paths and canonical containment, never a client-supplied filename.
        if ($directory && $path && strpos($path, $directory . DIRECTORY_SEPARATOR) === 0 && is_file($path)) {
            if (!@unlink($path)) {
                // The committed removal still blocks downloads; report cleanup failure to the server log.
                error_log('Unable to clean up removed announcement attachment: ' . $storedPath);
            }
        }
    }

    // Keep existing /announcements/view/:id links, but never treat URL input as a view filename.
    public function view($id = null, $data = []) {
        $this->requireDivisionalViewer();
        $announcement = $this->visibleAnnouncement($id);
        $id = (int)$announcement->announcement_id;
        $viewKey = $_SESSION['user_id'] . ':' . $id;
        if ($announcement->status === 'Published' && empty($_SESSION['viewed_announcements'][$viewKey])) {
            $this->model('AnnouncementModel')->incrementViewCount($id);
            $_SESSION['viewed_announcements'][$viewKey] = true;
            $announcement->view_count++;
        }
        parent::view('announcements/detail', [
            'title' => $announcement->title . ' — YouthNexus', 'announcement' => $announcement,
            'attachments' => $this->model('AnnouncementAttachmentModel')->findByAnnouncementId($id),
            'hasRead' => $this->model('AnnouncementReadModel')->hasRead($id, $_SESSION['user_id']),
            'isOwnAnnouncement' => (int)$announcement->created_by === (int)$_SESSION['user_id'] && $_SESSION['user_role'] === 'DivisionalSecretary',
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    }

    public function markRead($id = null) {
        $this->requireDivisionalViewer();
        $this->requirePost();
        $announcement = $this->visibleAnnouncement($id);
        if ($announcement->status !== 'Published') {
            $this->jsonResponse(409, ['error' => 'Only published announcements can be marked as read.']);
        }
        $this->model('AnnouncementReadModel')->markRead($announcement->announcement_id, $_SESSION['user_id']);
        $this->jsonResponse(200, ['success' => true]);
    }

    public function download($id = null) {
        $this->requireDivisionalViewer();
        $attachment = $this->model('AnnouncementAttachmentModel')->findById($this->positiveId($id));
        if (!$attachment) { $this->jsonResponse(404, ['error' => 'Attachment not found.']); }
        $this->visibleAnnouncement($attachment->announcement_id);
        $directory = realpath(dirname(__DIR__, 2) . '/uploads/announcement_attachments');
        $path = realpath(dirname(__DIR__, 2) . '/' . $attachment->file_path);
        // Canonical paths prevent ../ segments and symlinks from escaping the attachment directory.
        if (!$directory || !$path || strpos($path, $directory . DIRECTORY_SEPARATOR) !== 0 || !is_file($path) || !is_readable($path)) {
            $this->jsonResponse(404, ['error' => 'Attachment file not found.']);
        }
        $name = basename(str_replace('\\', '/', $attachment->file_name));
        $name = str_replace(["\r", "\n", '"'], '_', $name);
        header('Content-Type: application/octet-stream');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header('Content-Disposition: attachment; filename="download"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('Content-Length: ' . filesize($path));
        session_write_close();
        readfile($path);
        exit();
    }

    private function validatedUploads() {
        if (!isset($_FILES['attachments'])) { return []; }
        $files = $_FILES['attachments'];
        if (!isset($files['error']) || !is_array($files['error'])) {
            $this->jsonResponse(422, ['error' => 'Invalid attachment upload.']);
        }
        $types = [
            'pdf' => ['application/pdf'], 'png' => ['image/png'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
            'doc' => ['application/msword', 'application/x-ole-storage'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];
        $uploads = [];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        foreach ($files['error'] as $i => $error) {
            if ($error === UPLOAD_ERR_NO_FILE) { continue; }
            $name = $files['name'][$i] ?? null;
            $tmp = $files['tmp_name'][$i] ?? null;
            if ($error !== UPLOAD_ERR_OK || !is_string($name) || !is_string($tmp) || !is_uploaded_file($tmp)) {
                $this->jsonResponse(422, ['error' => 'An attachment could not be uploaded. Please select it again.']);
            }
            $name = basename(str_replace('\\', '/', $name));
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $size = filesize($tmp);
            if ($size > 10 * 1024 * 1024 || mb_strlen($name) > 255 || !isset($types[$extension])
                || !in_array($finfo->file($tmp), $types[$extension], true)) {
                $this->jsonResponse(422, ['error' => 'Attachments must be PDF, PNG, JPEG, DOC or DOCX files of at most 10 MB each.']);
            }
            $uploads[] = ['name' => $name, 'tmp' => $tmp, 'size' => $size, 'extension' => $extension];
        }
        return $uploads;
    }

    private function storeUpload($upload) {
        $directory = dirname(__DIR__, 2) . '/uploads/announcement_attachments';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Attachment directory is unavailable.');
        }
        $path = $directory . '/' . bin2hex(random_bytes(16)) . '.' . $upload['extension'];
        if (!move_uploaded_file($upload['tmp'], $path)) {
            throw new RuntimeException('Unable to store attachment.');
        }
        return $path;
    }

    private function jsonResponse($statusCode, array $data) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}
