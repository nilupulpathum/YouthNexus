<?php

class ManageEvents extends Controller {

    /**
     * Enforce DivisionalSecretary authentication and role.
     */
    private function requireSecretary() {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'DivisionalSecretary') {
            $this->redirect('auth/signin');
        }
    }

    /**
     * Determine if the incoming request is expecting a JSON response.
     */
    private function isJsonRequest() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $accept = $_SERVER['HTTP_ACCEPT'] ?? ($headers['Accept'] ?? '');
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ($headers['X-Requested-With'] ?? '');
        return strpos($accept, 'application/json') !== false || strtolower($xReq) === 'xmlhttprequest';
    }

    // ---------------------------------------------------------------
    // LIST: Manage Events page
    // ---------------------------------------------------------------
    public function index() {
        $this->requireSecretary();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $divisionId = (int)($_SESSION['division_id'] ?? 0);
        $eventModel = $this->model('EventModel');

        // Extract query filters
        $filters = [
            'search'         => trim($_GET['search'] ?? ''),
            'status'         => trim($_GET['status'] ?? 'All'),
            'target_club_id' => !empty($_GET['target_club_id']) ? (int)$_GET['target_club_id'] : null,
            'date_from'      => trim($_GET['date_from'] ?? ''),
            'date_to'        => trim($_GET['date_to'] ?? ''),
        ];

        $stats    = $eventModel->getDivisionStats($divisionId);
        $events   = $eventModel->getEventsByDivision($divisionId, $filters);
        $clubs    = $eventModel->getClubsByDivision($divisionId);
        $division = $eventModel->getDivisionById($divisionId);

        $this->view('manageevents/list', [
            'title'         => 'Manage Events — YouthNexus',
            'events'        => $events,
            'stats'         => $stats,
            'clubs'         => $clubs,
            'filters'       => $filters,
            'division'      => $division,
            'csrf_token'    => $_SESSION['csrf_token'],
            'user_name'     => $_SESSION['user_name'] ?? 'N. Fernando',
            'user_role'     => 'DivisionalSecretary',
            'user_initials' => $_SESSION['user_initials'] ?? 'NF',
        ]);
    }

    // ---------------------------------------------------------------
    // CREATE: Handle Divisional Event creation
    // ---------------------------------------------------------------
    public function create() {
        $this->requireSecretary();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('manageevents');
        }

        $divisionId = (int)($_SESSION['division_id'] ?? 0);
        $userId     = (int)($_SESSION['user_id'] ?? 0);
        $isJson     = $this->isJsonRequest();

        // 1. CSRF Verification
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid session token. Please refresh the page.']);
                exit();
            }
            $this->redirect('manageevents');
        }

        // 2. Input extraction & sanitization
        $title         = trim($_POST['title'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $eventType     = trim($_POST['event_type'] ?? '');
        $maxAttendance = trim($_POST['max_attendance'] ?? '');
        $startDatetime = trim($_POST['start_datetime'] ?? '');
        $endDatetime   = trim($_POST['end_datetime'] ?? '');
        $location      = trim($_POST['location'] ?? '');
        $targetClubId  = (int)($_POST['target_club_id'] ?? 0);

        $errors = [];

        // 3. Validation
        if (empty($title)) {
            $errors['title'] = 'Event title is required.';
        } elseif (mb_strlen($title) > 150) {
            $errors['title'] = 'Event title must not exceed 150 characters.';
        }

        if (mb_strlen($description) > 1000) {
            $errors['description'] = 'Description must not exceed 1000 characters.';
        }

        if (mb_strlen($eventType) > 50) {
            $errors['event_type'] = 'Event type must not exceed 50 characters.';
        }

        if (mb_strlen($location) > 255) {
            $errors['location'] = 'Location must not exceed 255 characters.';
        }

        $maxAttendeesVal = null;
        if ($maxAttendance !== '') {
            if (!ctype_digit($maxAttendance) || (int)$maxAttendance <= 0) {
                $errors['max_attendance'] = 'Max attendees must be a positive integer.';
            } else {
                $maxAttendeesVal = (int)$maxAttendance;
            }
        }

        // Datetime validation: end_datetime > start_datetime > NOW()
        if (empty($startDatetime) || empty($endDatetime)) {
            $errors['datetime'] = 'Both start and end dates and times are required.';
        } else {
            $startTs = strtotime($startDatetime);
            $endTs   = strtotime($endDatetime);
            $nowTs   = time();

            if (!$startTs || !$endTs) {
                $errors['datetime'] = 'Invalid date/time format provided.';
            } elseif ($startTs <= $nowTs || $endTs <= $startTs) {
                $errors['datetime'] = 'Event start must be after now, and end must be after start';
            }
        }

        // Target audience validation: must be a valid club in the secretary's division
        $eventModel = $this->model('EventModel');
        $targetClubValid = false;

        if ($targetClubId > 0) {
            $divisionClubs = $eventModel->getClubsByDivision($divisionId);
            foreach ($divisionClubs as $c) {
                if ((int)$c->club_id === $targetClubId) {
                    $targetClubValid = true;
                    break;
                }
            }
        }

        if (!$targetClubValid) {
            $errors['target_club_id'] = 'Please select a valid target club from your division.';
        }

        // If validation errors exist
        if (!empty($errors)) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit();
            }
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_old']    = $_POST;
            $this->redirect('manageevents');
        }

        // 4. Persistence
        $eventData = [
            'title'                 => $title,
            'description'           => $description ?: null,
            'event_type'            => $eventType ?: null,
            'max_attendance'        => $maxAttendeesVal,
            'start_datetime'        => date('Y-m-d H:i:s', strtotime($startDatetime)),
            'end_datetime'          => date('Y-m-d H:i:s', strtotime($endDatetime)),
            'location'              => $location ?: null,
            'organizer_division_id' => $divisionId,
            'organizer_club_id'     => null,
            'organizer_zonal_id'    => null,
            'target_scope'          => 'SingleTarget',
            'status'                => 'PendingApproval',
            'created_by'            => $userId,
        ];

        $newEventId = $eventModel->createEvent($eventData);

        $targetModel = $this->model('EventTargetModel');
        $targetModel->createTarget($newEventId, $targetClubId);

        // Regenerate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'success'  => true,
                'event_id' => $newEventId,
                'redirect' => ROOT . '/manageevents/status/' . $newEventId,
            ]);
            exit();
        }

        $this->redirect('manageevents/status/' . $newEventId);
    }

    // ---------------------------------------------------------------
    // STATUS: View Event Details and Read-only Submission Status
    // ---------------------------------------------------------------
    public function status($id = null) {
        $this->requireSecretary();

        $eventId = (int)$id;
        if (!$eventId) {
            $this->redirect('manageevents');
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $divisionId = (int)($_SESSION['division_id'] ?? 0);
        $userId     = (int)($_SESSION['user_id'] ?? 0);
        $eventModel = $this->model('EventModel');

        $event = $eventModel->findById($eventId);

        if (!$event) {
            $this->redirect('manageevents');
        }

        // Scope verification: Event must be organized by this division or by a club in this division
        $isInDivision = false;
        if ((int)$event->organizer_division_id === $divisionId) {
            $isInDivision = true;
        } elseif (!empty($event->organizer_club_id)) {
            $divisionClubs = $eventModel->getClubsByDivision($divisionId);
            foreach ($divisionClubs as $c) {
                if ((int)$c->club_id === (int)$event->organizer_club_id) {
                    $isInDivision = true;
                    break;
                }
            }
        }

        if (!$isInDivision) {
            $this->redirect('manageevents');
        }

        $clubs = $eventModel->getClubsByDivision($divisionId);

        // Check if editable: Only if status is PendingApproval AND created by the logged-in user
        $canEdit = ($event->status === 'PendingApproval' && (int)$event->created_by === $userId);

        $this->view('manageevents/status', [
            'title'         => htmlspecialchars($event->title) . ' — Event Status — YouthNexus',
            'event'         => $event,
            'clubs'         => $clubs,
            'can_edit'      => $canEdit,
            'csrf_token'    => $_SESSION['csrf_token'],
            'user_name'     => $_SESSION['user_name'] ?? 'N. Fernando',
            'user_role'     => 'DivisionalSecretary',
            'user_initials' => $_SESSION['user_initials'] ?? 'NF',
        ]);
    }

    // ---------------------------------------------------------------
    // EDIT: Update a pending event created by the current Secretary
    // ---------------------------------------------------------------
    public function edit($id = null) {
        $this->requireSecretary();

        $eventId = (int)$id;
        if (!$eventId) {
            $this->redirect('manageevents');
        }

        $divisionId = (int)($_SESSION['division_id'] ?? 0);
        $userId     = (int)($_SESSION['user_id'] ?? 0);
        $isJson     = $this->isJsonRequest();
        $eventModel = $this->model('EventModel');

        $event = $eventModel->findById($eventId);

        // Ownership and status check
        if (!$event 
            || (int)$event->organizer_division_id !== $divisionId 
            || (int)$event->created_by !== $userId 
            || $event->status !== 'PendingApproval') {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'This event cannot be edited or does not exist.']);
                exit();
            }
            $this->redirect('manageevents/status/' . $eventId);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('manageevents/status/' . $eventId);
        }

        // CSRF Check
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid session token. Please refresh the page.']);
                exit();
            }
            $this->redirect('manageevents/status/' . $eventId);
        }

        $title         = trim($_POST['title'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $eventType     = trim($_POST['event_type'] ?? '');
        $maxAttendance = trim($_POST['max_attendance'] ?? '');
        $startDatetime = trim($_POST['start_datetime'] ?? '');
        $endDatetime   = trim($_POST['end_datetime'] ?? '');
        $location      = trim($_POST['location'] ?? '');
        $targetClubId  = (int)($_POST['target_club_id'] ?? 0);

        $errors = [];

        if (empty($title)) {
            $errors['title'] = 'Event title is required.';
        } elseif (mb_strlen($title) > 150) {
            $errors['title'] = 'Event title must not exceed 150 characters.';
        }

        if (mb_strlen($description) > 1000) {
            $errors['description'] = 'Description must not exceed 1000 characters.';
        }

        if (mb_strlen($eventType) > 50) {
            $errors['event_type'] = 'Event type must not exceed 50 characters.';
        }

        if (mb_strlen($location) > 255) {
            $errors['location'] = 'Location must not exceed 255 characters.';
        }

        $maxAttendeesVal = null;
        if ($maxAttendance !== '') {
            if (!ctype_digit($maxAttendance) || (int)$maxAttendance <= 0) {
                $errors['max_attendance'] = 'Max attendees must be a positive integer.';
            } else {
                $maxAttendeesVal = (int)$maxAttendance;
            }
        }

        // Datetime validation: end_datetime > start_datetime > NOW()
        if (empty($startDatetime) || empty($endDatetime)) {
            $errors['datetime'] = 'Both start and end dates and times are required.';
        } else {
            $startTs = strtotime($startDatetime);
            $endTs   = strtotime($endDatetime);
            $nowTs   = time();

            if (!$startTs || !$endTs) {
                $errors['datetime'] = 'Invalid date/time format provided.';
            } elseif ($startTs <= $nowTs || $endTs <= $startTs) {
                $errors['datetime'] = 'Event start must be after now, and end must be after start';
            }
        }

        // Target audience validation
        $targetClubValid = false;
        if ($targetClubId > 0) {
            $divisionClubs = $eventModel->getClubsByDivision($divisionId);
            foreach ($divisionClubs as $c) {
                if ((int)$c->club_id === $targetClubId) {
                    $targetClubValid = true;
                    break;
                }
            }
        }

        if (!$targetClubValid) {
            $errors['target_club_id'] = 'Please select a valid target club from your division.';
        }

        if (!empty($errors)) {
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit();
            }
            $_SESSION['form_errors'] = $errors;
            $this->redirect('manageevents/status/' . $eventId);
        }

        $updateData = [
            'title'          => $title,
            'description'    => $description ?: null,
            'event_type'     => $eventType ?: null,
            'max_attendance' => $maxAttendeesVal,
            'start_datetime' => date('Y-m-d H:i:s', strtotime($startDatetime)),
            'end_datetime'   => date('Y-m-d H:i:s', strtotime($endDatetime)),
            'location'       => $location ?: null,
        ];

        // Update event record (immutable division_id)
        $eventModel->updateEvent($eventId, $divisionId, $userId, $updateData);

        // Update target record
        $targetModel = $this->model('EventTargetModel');
        $targetModel->updateTargetClub($eventId, $targetClubId);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'success'  => true,
                'event_id' => $eventId,
                'redirect' => ROOT . '/manageevents/status/' . $eventId,
            ]);
            exit();
        }

        $this->redirect('manageevents/status/' . $eventId);
    }
}
