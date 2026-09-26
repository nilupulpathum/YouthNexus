<?php

class EventApproval extends Controller {

    private function requireCoordinator() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SESSION['user_role'] ?? '') !== 'DivisionalCoordinator') {
            $this->redirect('home');
        }
    }

    public function index() {
        $this->requireCoordinator();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $eventModel = $this->model('EventModel');
        $divisionId = (int)($_SESSION['division_id'] ?? 0);

        $cancellationEvents = array_values(array_filter(
            $eventModel->findClubEventsByDivisionAndStatus($divisionId, 'CancellationPending'),
            static fn($event) => (int) ($event->organizer_division_id ?? 0) === $divisionId
        ));
        $pendingEvents = array_merge(
            $eventModel->findPendingClubEventsByDivision($divisionId),
            $cancellationEvents
        );
        
        $counts = [
            'Pending'  => count($pendingEvents),
            'Approved' => $eventModel->countClubEventsByDivisionAndStatus($divisionId, 'Approved'),
            'Rejected' => $eventModel->countClubEventsByDivisionAndStatus($divisionId, 'Rejected'),
        ];

        $this->view('eventapproval/event-list', [
            'title'         => 'Approve Events — YouthNexus',
            'pendingEvents' => $pendingEvents,
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

        $eventModel      = $this->model('EventModel');
        $eventTargetModel = $this->model('EventTargetModel');
        
        $event = $eventModel->findById((int)$id);
        
        // Scope check: verify event belongs to this coordinator's division
        $divisionId = (int)($_SESSION['division_id'] ?? 0);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found.']);
            exit();
        }

        $isInScope = false;
        if (!empty($event->organizer_division_id) && (int)$event->organizer_division_id === $divisionId) {
            $isInScope = true;
        } elseif (!empty($event->organizer_club_id)) {
            $clubModel = $this->model('ClubModel');
            $club = $clubModel->findById((int)$event->organizer_club_id);
            if ($club && (int)$club->division_id === $divisionId) {
                $isInScope = true;
            }
        }

        if (!$isInScope) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found or out of scope.']);
            exit();
        }

        $targets = $eventTargetModel->findByEventId($event->event_id);
        
        header('Content-Type: application/json');
        echo json_encode([
            'event'   => $event,
            'targets' => $targets
        ]);
        exit();
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

        $isInScope = false;
        if (!empty($event->organizer_division_id) && (int)$event->organizer_division_id === $divisionId) {
            $isInScope = true;
        } elseif (!empty($event->organizer_club_id)) {
            $clubModel = $this->model('ClubModel');
            $club = $clubModel->findById((int)$event->organizer_club_id);
            if ($club && (int)$club->division_id === $divisionId) {
                $isInScope = true;
            }
        }

        if (!$isInScope) {
            $this->jsonError('Event not found or out of scope.');
        }

        if (!$eventModel->decidePendingEvent($event->event_id, $divisionId, (int) $_SESSION['user_id'], 'Approved', null)) {
            $this->jsonError('The event status changed before approval was saved.');
        }
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

        $isInScope = false;
        if (!empty($event->organizer_division_id) && (int)$event->organizer_division_id === $divisionId) {
            $isInScope = true;
        } elseif (!empty($event->organizer_club_id)) {
            $clubModel = $this->model('ClubModel');
            $club = $clubModel->findById((int)$event->organizer_club_id);
            if ($club && (int)$club->division_id === $divisionId) {
                $isInScope = true;
            }
        }

        if (!$isInScope) {
            $this->jsonError('Event not found or out of scope.');
        }

        if (!$eventModel->decidePendingEvent($event->event_id, $divisionId, (int) $_SESSION['user_id'], 'Rejected', $remarks)) {
            $this->jsonError('The event status changed before rejection was saved.');
        }
        $auditModel->log($_SESSION['user_id'], 'REJECT_EVENT', 'Event', $event->event_id, $remarks);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    public function cancellation($id = null) {
        $this->requireCoordinator();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id
            || !isset($_POST['csrf_token'])
            || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
            $this->jsonError('Invalid request. Please refresh the page.');
        }
        $decision = (string) ($_POST['decision'] ?? '');
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        if (!in_array($decision, ['approve', 'reject'], true) || strlen($remarks) < 5 || strlen($remarks) > 1000) {
            $this->jsonError('Select a decision and provide remarks between 5 and 1000 characters.');
        }
        $eventModel = $this->model('EventModel');
        $event = $eventModel->findById((int) $id);
        $divisionId = (int) ($_SESSION['division_id'] ?? 0);
        if (!$event || (int) $event->organizer_division_id !== $divisionId || $event->status !== 'CancellationPending') {
            $this->jsonError('Cancellation request not found or already processed.');
        }
        $changed = $eventModel->decideDivisionalCancellation(
            (int) $id,
            $divisionId,
            (int) $_SESSION['user_id'],
            $decision === 'approve',
            $remarks
        );
        if (!$changed) $this->jsonError('The cancellation request changed before the decision was saved.');
        $this->model('AuditLogModel')->log(
            (int) $_SESSION['user_id'],
            $decision === 'approve' ? 'APPROVE_EVENT_CANCELLATION' : 'REJECT_EVENT_CANCELLATION',
            'Event',
            (int) $id,
            $remarks
        );
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    // APPROVED — list as JSON (AJAX)
    public function approved() {
        $this->requireCoordinator();
        $eventModel = $this->model('EventModel');
        $divisionId = (int)($_SESSION['division_id'] ?? 0);
        $events = $eventModel->findClubEventsByDivisionAndStatus($divisionId, 'Approved');
        header('Content-Type: application/json');
        echo json_encode(['events' => $events]);
        exit();
    }

    // REJECTED — list as JSON (AJAX)
    public function rejected() {
        $this->requireCoordinator();
        $eventModel = $this->model('EventModel');
        $divisionId = (int)($_SESSION['division_id'] ?? 0);
        $events = $eventModel->findClubEventsByDivisionAndStatus($divisionId, 'Rejected');
        header('Content-Type: application/json');
        echo json_encode(['events' => $events]);
        exit();
    }

    private function jsonError($message) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => $message]);
        exit();
    }
}
