<?php

class Zonalannouncements extends Controller {

    private function requireZonalRole() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $roles = ['ZonalCoordinator', 'ZonalSecretary', 'ZonalTreasurer', 'zonalcoordinator', 'zonalsecretary', 'zonaltreasurer'];
        if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
            $this->redirect('home');
        }
        if ((int) ($_SESSION['zonal_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a zone.');
        }
    }

    public function index() {
        $this->requireZonalRole();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $role = $_SESSION['user_role'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $clubId = (int) ($_SESSION['club_id'] ?? 0) ?: null;
        $divisionId = (int) ($_SESSION['division_id'] ?? 0) ?: null;
        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0) ?: null;
        $memberName = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User';

        $readModel = $this->model('AnnouncementReadModel');
        $attachmentModel = $this->model('AnnouncementAttachmentModel');
        $announcements = [];
        try {
            $rows = $this->model('AnnouncementModel')->findForUser($userId, $role, $clubId, $divisionId, $zonalId);
        } catch (Throwable $e) {
            $rows = [];
        }
        foreach (is_array($rows) ? $rows : [] as $a) {
            $row = is_array($a) ? (object) $a : $a;
            $id = (int) ($row->announcement_id ?? $row->id ?? 0);
            if ($id < 1) {
                continue;
            }
            $raw = $row->published_at ?? $row->created_at ?? null;
            $ts = $raw ? strtotime((string) $raw) : false;
            $isRead = $readModel->hasRead($id, $userId);
            $files = $attachmentModel->findByAnnouncementId($id);
            $first = (is_array($files) && count($files) > 0) ? $files[0] : null;
            if ($first && is_array($first)) {
                $first = (object) $first;
            }
            $announcements[] = [
                'id' => $id,
                'title' => $row->title ?? '',
                'summary' => mb_substr(trim(strip_tags((string) ($row->body ?? ''))), 0, 200),
                'body' => (string) ($row->body ?? ''),
                'scope' => $row->level ?? 'Zonal',
                'date' => $ts ? date('M d, Y', $ts) : '—',
                'age' => ClubOverview::ageLabel($ts),
                'is_new' => !$isRead,
                'is_unread' => !$isRead,
                'attachment' => $first->file_name ?? '',
                'attachment_size' => isset($first->file_size) ? $this->formatSize((int) $first->file_size) : '',
                'attachment_id' => isset($first->attachment_id) ? (int) $first->attachment_id : 0,
            ];
        }

        $unread = 0;
        foreach ($announcements as $item) {
            if (!empty($item['is_unread'])) {
                $unread++;
            }
        }

        $data = [
            'title' => 'Zone Announcements — YouthNexus Pulse',
            'pageTitle' => 'Zone Announcements',
            'pageDescription' => 'Announcements for the zone, its divisions, and their clubs.',
            'currentRoute' => 'zonalannouncements',
            'userRole' => $role,
            'userName' => $memberName,
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userInitials' => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => $unread,
            'unreadCount' => $unread,
            'announcements' => $announcements,
            'can_publish' => in_array($role, ['ZonalSecretary', 'zonalsecretary'], true),
            'csrf_token' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
        ];

        $this->view('zonalannouncements/index', $data);
    }

    /**
     * Secretary publishes a zone announcement (D14).
     */
    public function publish() {
        $this->requireZonalRole();
        if (!in_array($_SESSION['user_role'] ?? '', ['ZonalSecretary', 'zonalsecretary'], true)) {
            $this->redirect('zonalannouncements');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('zonalannouncements');
        }
        if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $this->setFlash('error', 'Refresh the page before publishing.');
            $this->redirect('zonalannouncements');
        }

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $priority = trim($_POST['priority'] ?? 'Normal');
        if ($title === '' || $body === '' || mb_strlen($title) > 150 || strlen($body) > 65535) {
            $this->setFlash('error', 'Title and body are required (title ≤ 150 characters).');
            $this->redirect('zonalannouncements');
        }
        if (!in_array($priority, ['Normal', 'Urgent'], true)) {
            $priority = 'Normal';
        }

        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO Announcement (title, body, level, organizer_zonal_id, priority, status, created_by, published_at)
                 VALUES (?, ?, 'Zonal', ?, ?, 'Published', ?, NOW())"
            );
            $stmt->execute([$title, $body, $zonalId, $priority, $userId]);
            $announcementId = (int) $db->lastInsertId();

            $roles = ['ZonalCoordinator', 'ZonalSecretary', 'ZonalTreasurer', 'DivisionalCoordinator', 'DivisionalSecretary', 'DivisionalTreasurer', 'ClubPresident', 'ClubSecretary', 'ClubTreasurer', 'ClubMember'];
            $aud = $db->prepare("INSERT INTO AnnouncementAudience (announcement_id, target_role, selection_mode) VALUES (?, ?, 'All')");
            foreach ($roles as $targetRole) {
                $aud->execute([$announcementId, $targetRole]);
            }

            $file = $_FILES['attachment'] ?? null;
            if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $stored = $this->storeAttachment($file, $announcementId);
                $att = $db->prepare("INSERT INTO AnnouncementAttachment (announcement_id, file_name, file_path, file_size) VALUES (?, ?, ?, ?)");
                $att->execute([$announcementId, $stored['name'], $stored['path'], $stored['size']]);
            }

            $audit = $db->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details) VALUES (?, 'PUBLISH_ANNOUNCEMENT', 'Announcement', ?, ?)");
            $audit->execute([$userId, $announcementId, substr($title, 0, 200)]);
            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->setFlash('error', $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'The announcement could not be published.');
            $this->redirect('zonalannouncements');
        }
        $this->setFlash('success', $priority === 'Urgent' ? 'Urgent zone announcement published.' : 'Zone announcement published.');
        $this->redirect('zonalannouncements');
    }

    /**
     * Mark one announcement read (scoped to visible items).
     */
    public function markread() {
        $this->requireZonalRole();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('zonalannouncements');
        }
        $id = (int) ($_POST['announcement_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($id > 0 && $this->model('AnnouncementAudienceModel')->isUserTargeted($id, $_SESSION['user_role'] ?? '', $userId)) {
            $this->model('AnnouncementReadModel')->markRead($id, $userId);
        }
        if (($_POST['format'] ?? '') === 'json' || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            return;
        }
        $this->redirect('zonalannouncements');
    }

    private function storeAttachment($file, int $announcementId): array {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 5242880) {
            throw new InvalidArgumentException('Attachment must be no larger than 5 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = [
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];
        if (!isset($extensions[$mime])) {
            throw new InvalidArgumentException('Attachment must be PDF, image, or Word document.');
        }
        $directory = dirname(__DIR__, 2) . '/uploads/announcement_attachments';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Attachment storage is unavailable.');
        }
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name'] ?? 'file'));
        $name = $announcementId . '_' . bin2hex(random_bytes(8)) . '_' . $safe;
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            throw new RuntimeException('The attachment could not be stored.');
        }
        return ['name' => basename($file['name'] ?? $name), 'path' => $name, 'size' => (int) $file['size']];
    }

    private function formatSize(int $bytes): string {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 1) . ' MB';
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['zonal_ann_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['zonal_ann_flash'] ?? null;
        unset($_SESSION['zonal_ann_flash']);
        return is_array($flash) ? $flash : null;
    }
}
