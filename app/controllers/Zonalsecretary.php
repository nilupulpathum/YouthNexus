<?php

/**
 * Zonalsecretary — zonal secretary overview, zonal event lifecycle (D9/Plan
 * 02: create, edit, resubmit, delete) and read-only attendance rollups.
 * Club health monitoring and reports live in Zonalclubhealth and
 * Zonalreports (divisional workflow at zone scope).
 *
 * Routes:
 *   zonalsecretary -> index()  (zonal secretary only)
 *   zonalsecretary/events -> events()
 *   zonalsecretary/updateevent, zonalsecretary/deleteevent
 *   zonalsecretary/attendance -> attendance()
 */
class Zonalsecretary extends Controller {

    private function eventCsrf() {
        if (empty($_SESSION['zonal_event_csrf'])) {
            $_SESSION['zonal_event_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['zonal_event_csrf'];
    }

    private function requireZonalSecretary() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $allowedRoles = ['ZonalSecretary', 'zonalsecretary'];
        if (!in_array($_SESSION['user_role'] ?? '', $allowedRoles, true)) {
            $this->redirect('home');
        }
        if ((int) ($_SESSION['zonal_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a zone.');
        }
    }

    /**
     * Base view data for the shared shell.
     */
    private function shell($title, $pageTitle, $pageDescription, $currentRoute) {
        $memberName = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User';
        return [
            'title'                   => $title,
            'pageTitle'               => $pageTitle,
            'pageDescription'         => $pageDescription,
            'currentRoute'            => $currentRoute,
            'userRole'                => $_SESSION['user_role'] ?? 'ZonalSecretary',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
        ];
    }

    /**
     * Secretary overview: live zone programme, funds, attendance and feed.
     */
    public function index() {
        $this->requireZonalSecretary();

        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $role = (string) ($_SESSION['user_role'] ?? 'ZonalSecretary');
        $zoneName = ZoneOverview::zoneName($this, $zonalId);
        $attendance = ZoneOverview::attendance($this, $zonalId);
        $funds = ZoneOverview::funds($this, $zonalId);
        $data = $this->shell(
            'Zonal Secretary Overview — YouthNexus Pulse',
            'Zonal Secretary Overview',
            'Zonal events, reports and attendance of ' . $zoneName . '.',
            'zonalsecretary'
        );
        $data['zoneName'] = $zoneName;
        $data['eventCount'] = ZoneOverview::eventCount($this, $zonalId);
        $data['budget'] = $funds['available'] ?? 0;
        $data['attendanceSummary'] = [
            'rate' => $attendance['rate'],
            'present' => $attendance['present'],
            'sessions' => $attendance['sessions'],
        ];
        $data['announcements'] = ZoneOverview::announcements(
            $this, $userId, $role, $_SESSION['division_id'] ?? null, $zonalId
        );
        $data['upcomingEvents'] = ZoneOverview::upcoming($this, $zonalId);

        $this->view('zonalsecretary/index', $data);
    }

    /**
     * Zonal events on the real Event table (Plan 02 lifecycle).
     */
    public function events() {
        $this->requireZonalSecretary();

        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $eventModel = $this->model('EventModel');
        $divisions = $eventModel->getZoneDivisions($zonalId);
        $divisionNames = array_map(static fn($d) => $d->division_name, $divisions);

        $errors = [];
        $old = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old = $_POST;
            if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($this->eventCsrf(), $_POST['csrf_token'])) {
                $errors['general'] = 'Refresh the page before creating an event.';
            } else {
                $checked = $this->validateZonalEventInput($eventModel, $zonalId, $_POST);
                $errors = $checked['errors'];
                $title = $checked['values']['title'];
                $type = $checked['values']['type'];
                $location = $checked['values']['location'];
                $targetDivisionId = $checked['values']['targetDivisionId'];
                $start = $checked['values']['start'];
            }

            if (!$errors) {
                $eventId = $eventModel->createZonalEvent($zonalId, (int) $_SESSION['user_id'], [
                    'title' => substr($title, 0, 150),
                    'type' => substr($type, 0, 50),
                    'location' => $location,
                    'start' => $start->format('Y-m-d H:i:s'),
                    'end' => $start->modify('+2 hours')->format('Y-m-d H:i:s'),
                ], $targetDivisionId);
                $this->model('AuditLogModel')->log($_SESSION['user_id'], 'CREATE_EVENT', 'Event', $eventId, "Created zonal event '{$title}'");
                $_SESSION['zonal_event_flash'] = 'Event submitted to the Zonal Coordinator for approval.';
                $_SESSION['zonal_event_csrf'] = bin2hex(random_bytes(32));
                $this->redirect('zonalsecretary/events');
            }
        }

        $statusMap = [
            'PendingApproval' => 'Pending approval',
            'Approved' => 'Approved',
            'Completed' => 'Completed',
            'Rejected' => 'Changes requested',
        ];
        $events = [];
        $pending = 0;
        $approved = 0;
        foreach ($eventModel->getZonalEvents($zonalId) as $ev) {
            if ($ev->status === 'PendingApproval') {
                $pending++;
            } elseif ($ev->status === 'Approved') {
                $approved++;
            }
            $start = strtotime((string) $ev->start_datetime);
            $events[] = [
                'id' => (int) $ev->event_id,
                'title' => $ev->title ?? '',
                'type' => $ev->event_type ?? '',
                'date' => $start ? date('M d, Y', $start) : '—',
                'time' => $start ? date('g:i A', $start) : '',
                'raw_date' => $start ? date('Y-m-d', $start) : '',
                'raw_time' => $start ? date('H:i', $start) : '',
                'location' => $ev->location ?? '',
                'audience' => $ev->target_divisions ?: 'All divisions',
                'audience_value' => $ev->target_divisions ?: 'All divisions',
                'status' => $statusMap[$ev->status] ?? $ev->status,
                'status_key' => strtolower($ev->status ?? ''),
                'coordinator_remark' => $ev->rejection_remarks ?? '',
            ];
        }

        $data = $this->shell(
            'Zonal Events — YouthNexus Pulse',
            'Zonal Events',
            'Create zonal events for divisions and clubs.',
            'zonalsecretary/events'
        );
        $data['events'] = $events;
        $data['zoneName'] = $this->zoneName($zonalId);
        $data['divisions'] = $divisionNames;
        $data['eventStats'] = [
            'scheduled' => $pending,
            'approved' => $approved,
            'total' => count($events),
        ];
        $data['csrf_token'] = $this->eventCsrf();
        $data['flash'] = $_SESSION['zonal_event_flash'] ?? '';
        $data['errors'] = $errors;
        $data['old'] = $old;
        unset($_SESSION['zonal_event_flash']);

        $this->view('zonalsecretary/events', $data);
    }

    /**
     * Shared server-side boundary for create and edit. Scope comes from the
     * session zone; the posted audience is only validated against it.
     *
     * @return array{errors: array, values: array}
     */
    private function validateZonalEventInput($eventModel, int $zonalId, array $post): array {
        $errors = [];
        $title = trim((string)($post['title'] ?? ''));
        $type = trim((string)($post['event_type'] ?? ''));
        $date = trim((string)($post['event_date'] ?? ''));
        $time = trim((string)($post['event_time'] ?? ''));
        $location = trim((string)($post['location'] ?? ''));
        $audience = trim((string)($post['audience'] ?? 'All divisions'));
        $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($title === '' || mb_strlen($title) > 150) $errors['title'] = 'Enter an event title of 150 characters or fewer.';
        if ($type === '' || mb_strlen($type) > 50) $errors['event_type'] = 'Enter an event type of 50 characters or fewer.';
        if (!$dateValue || $dateValue->format('Y-m-d') !== $date) $errors['event_date'] = 'Enter a valid event date.';
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) $errors['event_time'] = 'Enter a valid event time.';
        $start = null;
        if ($dateValue && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
            if ($start && $start <= new DateTimeImmutable()) {
                $errors['event_date'] = 'Choose a date and time in the future.';
                $start = null;
            }
            if (!$start) $errors['event_date'] = 'Enter a valid event date.';
        }
        if ($location === '' || mb_strlen($location) > 255) $errors['location'] = 'Enter a location of 255 characters or fewer.';
        $targetDivisionId = null;
        if ($audience !== 'All divisions') {
            $target = $eventModel->findZoneDivision($zonalId, $audience);
            if (!$target) {
                $errors['audience'] = 'Select a valid audience.';
            } else {
                $targetDivisionId = (int) $target->division_id;
            }
        }
        return ['errors' => $errors, 'values' => [
            'title' => $title, 'type' => $type, 'location' => $location,
            'targetDivisionId' => $targetDivisionId, 'start' => $start,
        ]];
    }

    /**
     * Secretary edits a PendingApproval event in place, or corrects an
     * Approved/Rejected one — which returns it to PendingApproval for the
     * coordinator to decide again.
     */
    public function updateevent() {
        $this->requireZonalSecretary();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('zonalsecretary/events');
        }
        if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($this->eventCsrf(), $_POST['csrf_token'])) {
            $_SESSION['zonal_event_flash'] = 'Refresh the page before editing an event.';
            $this->redirect('zonalsecretary/events');
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId < 1) {
            $_SESSION['zonal_event_flash'] = 'Invalid event request.';
            $this->redirect('zonalsecretary/events');
        }

        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $eventModel = $this->model('EventModel');
        $checked = $this->validateZonalEventInput($eventModel, $zonalId, $_POST);
        if ($checked['errors']) {
            $_SESSION['zonal_event_flash'] = reset($checked['errors']);
            $this->redirect('zonalsecretary/events');
        }

        $current = $eventModel->getZonalEventForSecretary($zonalId, $eventId);
        if (!$current || !in_array($current->status ?? '', ['PendingApproval', 'Approved', 'Rejected'], true)) {
            $_SESSION['zonal_event_flash'] = 'Event not found in your zone.';
            $this->redirect('zonalsecretary/events');
        }
        $wasPending = ($current->status === 'PendingApproval');

        $values = $checked['values'];
        $rows = $eventModel->updateZonalEvent($zonalId, $eventId, [
            'title' => substr($values['title'], 0, 150),
            'type' => substr($values['type'], 0, 50),
            'location' => $values['location'],
            'start' => $values['start']->format('Y-m-d H:i:s'),
            'end' => $values['start']->modify('+2 hours')->format('Y-m-d H:i:s'),
        ], $values['targetDivisionId']);
        if ($rows < 1) {
            $_SESSION['zonal_event_flash'] = 'Event not found in your zone.';
            $this->redirect('zonalsecretary/events');
        }

        if ($wasPending) {
            $this->model('AuditLogModel')->log($_SESSION['user_id'], 'UPDATE_EVENT', 'Event', $eventId, "Edited pending zonal event '{$values['title']}'");
            $_SESSION['zonal_event_flash'] = 'Pending event updated.';
        } else {
            $this->model('AuditLogModel')->log($_SESSION['user_id'], 'RESUBMIT_EVENT', 'Event', $eventId, "Edited zonal event '{$values['title']}' and returned it for approval");
            $_SESSION['zonal_event_flash'] = 'Event updated and sent back to the Zonal Coordinator for approval.';
        }
        $this->redirect('zonalsecretary/events');
    }

    /**
     * Secretary deletes a coordinator-rejected event. Refused when recorded
     * attendance still references it.
     */
    public function deleteevent() {
        $this->requireZonalSecretary();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('zonalsecretary/events');
        }
        if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($this->eventCsrf(), $_POST['csrf_token'])) {
            $_SESSION['zonal_event_flash'] = 'Refresh the page before deleting an event.';
            $this->redirect('zonalsecretary/events');
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId < 1) {
            $_SESSION['zonal_event_flash'] = 'Invalid event request.';
            $this->redirect('zonalsecretary/events');
        }

        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $result = $this->model('EventModel')->deleteRejectedZonalEvent($zonalId, $eventId);
        if (!$result['deleted']) {
            if (!$result['blockers']) {
                $_SESSION['zonal_event_flash'] = 'Rejected event not found in your zone.';
            } else {
                $labels = array_map(
                    static fn($b) => $b['table'] . ' (' . $b['count'] . ')',
                    $result['blockers']
                );
                $_SESSION['zonal_event_flash'] = 'Cannot delete — this event is still referenced by ' . implode(', ', $labels) . '.';
            }
            $this->redirect('zonalsecretary/events');
        }

        $this->model('AuditLogModel')->log($_SESSION['user_id'], 'DELETE_EVENT', 'Event', $eventId, 'Deleted rejected zonal event');
        $_SESSION['zonal_event_flash'] = 'Rejected event deleted.';
        $this->redirect('zonalsecretary/events');
    }

    private function zoneName(int $zonalId): string {
        $zone = $this->model('ZoneFundModel')->getZone($zonalId);
        return $zone->zonal_name ?? 'Zone';
    }

    /**
     * Zone attendance statistics are read-only rollups by division, computed
     * live from division and club events in the period. Zonal-organized
     * events belong to the zone rather than any division, so the summary
     * always matches the displayed division rows.
     */
    public function attendance() {
        $this->requireZonalSecretary();

        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $monitor = $this->model('ZoneMonitorModel');
        $divisionNames = array_map(
            static fn($d) => $d->division_name,
            $monitor->getDivisions($zonalId)
        );
        $division = trim((string)($_GET['division'] ?? 'All divisions'));
        if ($division !== 'All divisions' && !in_array($division, $divisionNames, true)) {
            $division = 'All divisions';
        }
        $period = (string) ($_GET['period'] ?? '90');
        if (!in_array($period, ['30', '90', '365'], true)) {
            $period = '90';
        }

        $divisionRows = $monitor->getDivisionAttendance($zonalId, (int) $period);
        if ($division !== 'All divisions') {
            $divisionRows = array_values(array_filter(
                $divisionRows, static fn($row) => $row['name'] === $division
            ));
        }
        $present = array_sum(array_column($divisionRows, 'present'));
        $possible = array_sum(array_column($divisionRows, 'possible'));

        $zoneName = ZoneOverview::zoneName($this, $zonalId);
        $data = $this->shell(
            'Zone Attendance — YouthNexus Pulse',
            'Zone Attendance',
            'Attendance statistics across ' . $zoneName . '.',
            'zonalsecretary/attendance'
        );
        $data['zoneName'] = $zoneName;
        $data['divisionNames'] = $divisionNames;
        $data['divisionRows'] = $divisionRows;
        $data['division'] = $division;
        $data['period'] = $period;
        $data['zoneAttendance'] = [
            'rate' => $possible > 0 ? round($present * 100 / $possible, 1) : 0,
            'present' => $present,
            'possible' => $possible,
            'sessions' => array_sum(array_column($divisionRows, 'sessions')),
            'clubs' => array_sum(array_column($divisionRows, 'clubs')),
        ];

        $this->view('zonalsecretary/attendance', $data);
    }
}
