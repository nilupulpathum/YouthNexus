<?php

class EventApproval extends Controller {

    private function requireCoordinator() {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'DivisionalCoordinator') {
            $this->redirect('auth/signin');
        }
    }

    public function index() {
        $this->requireCoordinator();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $eventModel = $this->model('EventModel');
        $divisionId = (int)($_SESSION['division_id'] ?? 0);

        // Approved events are the coordinator's default landing view. Pending
        // events remain available through the interactive status cards.
        $events = $eventModel->findClubEventsByDivisionAndStatus($divisionId, 'Approved');

        $counts = [
            'Pending'  => $eventModel->countClubEventsByDivisionAndStatus($divisionId, 'PendingApproval'),
            'Approved' => $eventModel->countClubEventsByDivisionAndStatus($divisionId, 'Approved'),
            'Rejected' => $eventModel->countClubEventsByDivisionAndStatus($divisionId, 'Rejected'),
        ];

        $this->view('eventapproval/event-list', [
            'title'         => 'Approve Events — YouthNexus',
            'events'        => $events,
            'initialStatus' => 'Approved',
            'counts'        => $counts,
            'csrf_token'    => $_SESSION['csrf_token'],
            'userName'      => $_SESSION['user_name'] ?? 'R. Perera',
            'userRole'      => 'DivisionalCoordinator',
        ]);
    }

    public function review($id = null) {
        $this->requireCoordinator();
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'No event ID provided.']);
            exit();
        }

        $eventModel = $this->model('EventModel');
        $eventTargetModel = $this->model('EventTargetModel');
        
        $event = $eventModel->findById((int)$id);
        
        // Scope check: ensure the event belongs to this Coordinator's division and is a club-level event
        $divisionId = (int)($_SESSION['division_id'] ?? 0);
        if (!$event || (int)$event->organizer_division_id !== 0 || empty($event->organizer_club_id)) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found or not in scope.']);
            exit();
        }

        // We need the club's division to verify scope
        $clubModel = $this->model('ClubModel'); // assuming ClubModel exists
        $club = $clubModel->findById((int)$event->organizer_club_id);
        if (!$club || (int)$club->division_id !== $divisionId) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found or out of scope.']);
            exit();
        }

        $targets = $eventTargetModel->findByEventId($event->event_id);
        
        // Structure the response to match what JS expects
        header('Content-Type: application/json');
        echo json_encode([
            'event'   => $event,
            'targets' => $targets
        ]);
        exit();
    }

    /**
     * Display the existing event detail screen in read-only mode for a
     * Divisional Coordinator. Access is restricted to club events that belong
     * to the coordinator's own division.
     */
    public function detail($id = null) {
        $this->requireCoordinator();

        $eventId = (int)$id;
        if ($eventId <= 0) {
            $this->redirect('eventapproval');
        }

        $divisionId = (int)($_SESSION['division_id'] ?? 0);
        $eventModel = $this->model('EventModel');
        $event = $eventModel->findById($eventId);

        if (!$this->isClubEventInCoordinatorDivision($event, $divisionId)) {
            http_response_code(404);
            $this->redirect('eventapproval');
        }

        $targetModel = $this->model('EventTargetModel');
        $targets = $targetModel->findByEventId($eventId);

        $this->view('manageevents/status', [
            'title'                   => $event->title . ' — Event Details — YouthNexus',
            'pageTitle'               => 'Event Details',
            'pageDescription'         => 'Review the complete event submission and its current decision status',
            'currentRoute'            => 'eventapproval',
            'unreadNotificationCount' => 0,
            'event'                   => $event,
            'clubs'                   => [],
            'targets'                 => $targets,
            'target_map'              => [],
            'can_edit'                => false,
            'csrf_token'              => $_SESSION['csrf_token'] ?? '',
            'user_name'               => $_SESSION['user_name'] ?? 'Divisional Coordinator',
            'user_role'               => 'DivisionalCoordinator',
            'back_url'                => ROOT . '/eventapproval',
            'back_label'              => 'Back to Event Approval',
            'detail_context'          => 'approval',
        ]);
    }

    public function approve($id = null) {
        $this->requireCoordinator();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('eventapproval/index');
        }
        
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->jsonError('Invalid request. Please refresh the page.');
        }

        $eventModel = $this->model('EventModel');
        $auditModel = $this->model('AuditLogModel');
        $divisionId = (int)($_SESSION['division_id'] ?? 0);

        $event = $eventModel->findById((int)$id);
        
        if (!$event || $event->status !== 'PendingApproval') {
            $this->jsonError('Event not found or already processed.');
        }

        $clubModel = $this->model('ClubModel');
        $club = $clubModel->findById((int)$event->organizer_club_id);
        if (!$club || (int)$club->division_id !== $divisionId || !empty($event->organizer_division_id)) {
            $this->jsonError('Event not found or out of scope.');
        }

        // Apply approval
        $eventModel->updateEventStatus($event->event_id, 'Approved', $_SESSION['user_id'], null);
        $auditModel->log($_SESSION['user_id'], 'APPROVE_EVENT', 'Event', $event->event_id, "Approved event '{$event->title}'");

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    public function reject($id = null) {
        $this->requireCoordinator();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('eventapproval/index');
        }
        
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->jsonError('Invalid request. Please refresh the page.');
        }

        $remarks = trim($_POST['remarks'] ?? '');
        if (empty($remarks)) {
            $this->jsonError('Please provide remarks explaining the rejection.');
        }

        $eventModel = $this->model('EventModel');
        $auditModel = $this->model('AuditLogModel');
        $divisionId = (int)($_SESSION['division_id'] ?? 0);

        $event = $eventModel->findById((int)$id);
        
        if (!$event || $event->status !== 'PendingApproval') {
            $this->jsonError('Event not found or already processed.');
        }

        $clubModel = $this->model('ClubModel');
        $club = $clubModel->findById((int)$event->organizer_club_id);
        if (!$club || (int)$club->division_id !== $divisionId || !empty($event->organizer_division_id)) {
            $this->jsonError('Event not found or out of scope.');
        }

        // Apply rejection
        $eventModel->updateEventStatus($event->event_id, 'Rejected', $_SESSION['user_id'], $remarks);
        $auditModel->log($_SESSION['user_id'], 'REJECT_EVENT', 'Event', $event->event_id, $remarks);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    // ---------------------------------------------------------------
    // APPROVED — list of approved club events as JSON (AJAX)
    // ---------------------------------------------------------------
    public function approved() {
        $this->requireCoordinator();

        $eventModel = $this->model('EventModel');
        $divisionId = (int)($_SESSION['division_id'] ?? 0);

        $events = $eventModel->findClubEventsByDivisionAndStatus($divisionId, 'Approved');

        header('Content-Type: application/json');
        echo json_encode([
            'events' => $events,
        ]);
        exit();
    }

    // ---------------------------------------------------------------
    // PENDING — list of pending club events as JSON (AJAX)
    // ---------------------------------------------------------------
    public function pending() {
        $this->requireCoordinator();

        $eventModel = $this->model('EventModel');
        $divisionId = (int)($_SESSION['division_id'] ?? 0);

        $events = $eventModel->findClubEventsByDivisionAndStatus($divisionId, 'PendingApproval');

        header('Content-Type: application/json');
        echo json_encode(['events' => $events]);
        exit();
    }

    // ---------------------------------------------------------------
    // REJECTED — list of rejected club events as JSON (AJAX)
    // ---------------------------------------------------------------
    public function rejected() {
        $this->requireCoordinator();

        $eventModel = $this->model('EventModel');
        $divisionId = (int)($_SESSION['division_id'] ?? 0);

        $events = $eventModel->findClubEventsByDivisionAndStatus($divisionId, 'Rejected');

        header('Content-Type: application/json');
        echo json_encode([
            'events' => $events,
        ]);
        exit();
    }


    private function jsonError($message) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => $message]);
        exit();
    }

    /**
     * Confirm that a retrieved event is a club-level event inside the current
     * coordinator's division. This prevents ID-based cross-division access.
     */
    private function isClubEventInCoordinatorDivision($event, $divisionId) {
        if (!$event || empty($event->organizer_club_id) || !empty($event->organizer_division_id)) {
            return false;
        }

        $clubModel = $this->model('ClubModel');
        $club = $clubModel->findById((int)$event->organizer_club_id);

        return $club && (int)$club->division_id === (int)$divisionId;
    }
}
