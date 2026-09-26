<?php

class Events extends Controller {

    /**
     * Member events browser (rewired fix/club-event-wiring): real events
     * visible to the viewer — own club + own division + own zone + national
     * rows, SelectedClubs targeting enforced — with live statuses computed
     * from datetimes. Each event also carries the viewer's own RSVP response
     * (from EventRsvp; '' = undecided) so the served card paints the correct
     * active button and the RSVP filter group works on first load.
     */
    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $userId = (int) $_SESSION['user_id'];
        $scope = ZoneOverview::effectiveScope($this, $userId);
        $rows = $this->model('EventModel')->getVisibleEvents(
            $scope['club_id'], $scope['division_id'], $scope['zonal_id']
        );

        $now = time();
        $events = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $row = is_array($row) ? (object) $row : $row;
            $start = strtotime((string) ($row->start_datetime ?? ''));
            $end = strtotime((string) ($row->end_datetime ?? ''));
            $status = (string) ($row->status ?? '');
            if ($status === 'Completed') {
                $display = 'Completed';
                $remaining = 'COMPLETED';
            } elseif ($status === 'PendingApproval') {
                $display = 'Pending';
                $remaining = 'AWAITING APPROVAL';
            } elseif ($start && $start > $now) {
                $display = 'Upcoming';
                $days = (int) floor(($start - $now) / 86400);
                $remaining = $days <= 0 ? 'TODAY' : ($days === 1 ? 'TOMORROW' : $days . ' DAYS LEFT');
            } elseif ($start && $end && $start <= $now && $end >= $now) {
                $display = 'Ongoing';
                $remaining = 'HAPPENING NOW';
            } else {
                $display = 'Ended';
                $remaining = 'ENDED';
            }
            if ((int) ($row->organizer_club_id ?? 0) > 0) {
                $level = 'Club';
            } elseif ((int) ($row->organizer_division_id ?? 0) > 0) {
                $level = 'Divisional';
            } elseif ((int) ($row->organizer_zonal_id ?? 0) > 0) {
                $level = 'Zonal';
            } else {
                $level = 'National';
            }
            $events[] = [
                'id' => (int) ($row->event_id ?? 0),
                'title' => (string) ($row->title ?? ''),
                'description' => (string) ($row->description ?? ''),
                'scope' => $level,
                'date' => $start ? date('M d, Y', $start) : '—',
                'start_iso' => $start ? date('c', $start) : '',
                'posted_iso' => isset($row->created_at) && strtotime((string) $row->created_at)
                    ? date('c', strtotime((string) $row->created_at)) : '',
                'location' => trim((string) ($row->location ?? '')) !== '' ? (string) $row->location : '—',
                'status' => $display,
                'remaining' => $remaining,
                'attendance' => (int) ($row->present_count ?? 0),
                // RSVP state stamped below; null until the viewer responds.
                'rsvp_status' => null,
            ];
        }

        try {
            $responses = $this->model('EventRsvpModel')
                ->responsesForEvents($userId, array_column($events, 'id'));
        } catch (Throwable $e) {
            $responses = [];
        }
        foreach ($events as &$item) {
            $item['rsvp_status'] = $responses[$item['id']] ?? null;
        }
        unset($item);

        $memberName = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User';

        $headerNotif = ZoneOverview::headerNotifications($this);
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $data = [
            'title' => 'Events — YouthNexus Pulse',
            'pageTitle' => 'Events',
            'pageDescription' => 'Browse, respond, and track all your events.',
            'currentRoute' => 'events',
            'userRole' => $_SESSION['user_role'] ?? 'ClubMember',
            'userName' => $memberName,
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userInitials' => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => $headerNotif['count'],
            'headerNotifications' => $headerNotif['items'],
            'csrf_token' => $_SESSION['csrf_token'],
            'events' => $events
        ];

        $this->view('events/index', $data);
    }

    /**
     * Record (or revise) the viewer's RSVP for one visible event.
     * Only Upcoming/Ongoing events accept responses. POST only, JSON in/out.
     */
    public function rsvp() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonResponse(405, ['ok' => false, 'error' => 'This action requires POST.']);
        }
        $token = (string) ($_POST['csrf_token'] ?? '');
        if ($token === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            $this->jsonResponse(403, ['ok' => false, 'error' => 'Invalid request. Please refresh the page.']);
        }
        $response = isset($_POST['response']) && is_string($_POST['response']) ? $_POST['response'] : '';
        if ($response !== 'attending' && $response !== 'declined' && $response !== 'none') {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'Response must be attending, declined or none.']);
        }

        $userId = (int) $_SESSION['user_id'];
        $scope = ZoneOverview::effectiveScope($this, $userId);
        $event = $this->model('EventModel')->findVisibleEvent(
            (int) ($_POST['event_id'] ?? 0),
            $scope['club_id'], $scope['division_id'], $scope['zonal_id']
        );
        if (!$event) {
            $this->jsonResponse(404, ['ok' => false, 'error' => 'Event not found.']);
        }
        if (!$this->rsvpOpen($event)) {
            $this->jsonResponse(422, ['ok' => false, 'error' => 'Responses are closed for this event.']);
        }

        if ($response === 'none') {
            // Toggle off: pressing the active option again clears the row.
            $this->model('EventRsvpModel')->deleteByPair($userId, (int) $event->event_id);
            $this->model('AuditLogModel')->log(
                $userId, 'RSVP_EVENT', 'Event', (int) $event->event_id, 'Cleared response'
            );
            $this->jsonResponse(200, ['ok' => true, 'rsvp_status' => null]);
        }

        $this->model('EventRsvpModel')->upsert($userId, (int) $event->event_id, $response);
        $this->model('AuditLogModel')->log(
            $userId, 'RSVP_EVENT', 'Event', (int) $event->event_id, 'Responded ' . $response
        );

        $this->jsonResponse(200, ['ok' => true, 'rsvp_status' => $response]);
    }

    /**
     * RSVP window: Approved events that have not ended (Upcoming + Ongoing).
     * Pending-approval, completed and ended events refuse responses.
     */
    private function rsvpOpen($event) {
        if ((string) ($event->status ?? '') !== 'Approved') {
            return false;
        }
        $end = strtotime((string) ($event->end_datetime ?? ''));
        return $end && $end >= time();
    }

    private function jsonResponse($status, array $payload) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit();
    }
}
