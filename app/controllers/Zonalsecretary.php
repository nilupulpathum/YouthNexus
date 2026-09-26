<?php

/**
 * Zonalsecretary — zonal secretary dashboards (Z0 scaffolding).
 *
 * Presentation-only: session data mirroring the future backend contract.
 * No database reads or writes. Event scheduling and attendance summaries
 * remain frontend demonstrations until the backend phase is unlocked.
 *
 * Routes:
 *   zonalsecretary -> index()  (zonal secretary only)
 *   zonalsecretary/clubs -> clubs()  (monitor club health, shared Z1 build)
 *   zonalsecretary/events -> events()
 *   zonalsecretary/attendance -> attendance()
 *   zonalsecretary/reports -> reports()
 */
class Zonalsecretary extends Controller {

    private function demoState() {
        $key = 'gampaha';
        if (!isset($_SESSION['zonal_secretary_demo'][$key])) {
            $_SESSION['zonal_secretary_demo'][$key] = [
                'budget' => 2500000.00,
                'spent' => 425000.00,
                'notifications' => 2,
                'events' => [
                    ['id' => 'ZE-101', 'title' => 'Zone Youth Leadership Forum', 'type' => 'Leadership', 'date' => '2026-10-10', 'time' => '09:00', 'location' => 'Gampaha Youth Centre', 'budget' => 225000.00, 'audience' => 'All divisions', 'status' => 'Approved'],
                    ['id' => 'ZE-102', 'title' => 'Digital Skills Workshop', 'type' => 'Training', 'date' => '2026-10-22', 'time' => '10:00', 'location' => 'Ja-Ela Community Hall', 'budget' => 200000.00, 'audience' => 'Ja-Ela Division', 'status' => 'Pending approval'],
                ],
            ];
        }
        return $_SESSION['zonal_secretary_demo'][$key];
    }

    private function saveDemoState(array $state) {
        $key = 'gampaha';
        $_SESSION['zonal_secretary_demo'][$key] = $state;
    }

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
     * Secretary overview with zonal programme and attendance summary.
     */
    public function index() {
        $this->requireZonalSecretary();

        $state = $this->demoState();
        $data = $this->shell(
            'Zonal Secretary Overview — YouthNexus Pulse',
            'Zonal Secretary Overview',
            'Zonal events, reports and attendance of Gampaha Zone.',
            'zonalsecretary'
        );
        $data['eventCount'] = count($state['events']);
        $data['budget'] = $state['budget'];
        $data['spent'] = $state['spent'];
        $data['attendanceSummary'] = ['rate' => 78, 'present' => 486, 'sessions' => 18];
        $data['announcements'] = [
            ['title' => 'Quarterly reporting window', 'summary' => 'Division reports are due before the end of the current reporting period.', 'age' => '1 day ago', 'is_new' => true],
            ['title' => 'NYSC leadership forum', 'summary' => 'Confirm the zone representative list before the programme deadline.', 'age' => '4 days ago', 'is_new' => false],
        ];
        $data['upcomingEvents'] = array_map(static function ($event) {
            return ['title' => $event['title'], 'date' => date('M j, Y', strtotime($event['date'])), 'location' => $event['location'], 'status' => $event['status'], 'status_key' => 'attending'];
        }, array_slice($state['events'], 0, 2));

        $this->view('zonalsecretary/index', $data);
    }

    /**
     * Monitor club health (shared Z1 build with the coordinator monitor —
     * same zone picture; secretary flags follow the same NYSC Admin path).
     * Renders the shared monitor view under the secretary shell.
     */
    public function clubs() {
        $this->requireZonalSecretary();

        $mock = $this->model('ZoneHealthMock');
        $clubs = $mock->zoneClubs();
        usort($clubs, static function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $data = $this->shell(
            'Monitor Club Health — YouthNexus Pulse',
            'Monitor Club Health',
            'Monitor club health scores and identify clubs requiring intervention.',
            'zonalsecretary/clubs'
        );
        $data['zoneHealth'] = $mock->zoneHealth($clubs);
        $data['divisions'] = $mock->divisionAverages($clubs);
        $data['clubs'] = $clubs;

        $this->view('zonalcoordinator/clubs', $data);
    }

    /**
     * Zonal events with a session-only budget guard and notifications.
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

    /**
     * Report catalog + generated zone reports (D13: real Report rows).
     */
    public function reports() {
        $this->requireZonalSecretary();
        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $model = $this->model('ZoneReportModel');
        $catalog = $model->getCatalog();

        $category = trim((string)($_GET['category'] ?? ''));
        $search = strtolower(trim((string)($_GET['search'] ?? '')));
        if ($category !== '' && !isset($catalog[$category])) $category = '';

        $reports = [];
        foreach ($model->getReports($zonalId) as $r) {
            $ts = strtotime((string) $r->generated_at);
            $reports[] = [
                'id' => (int) $r->report_id,
                'category' => $r->category ?? '',
                'type' => $r->type_name ?? '',
                'division' => 'All divisions',
                'format' => $r->format ?? '',
                'date' => $ts ? date('M d, Y', $ts) : '—',
                'by' => $r->generated_by_name ?? '',
            ];
        }
        $reports = array_values(array_filter($reports, static function ($report) use ($category, $search) {
            return ($category === '' || $report['category'] === $category)
                && ($search === '' || str_contains(strtolower($report['type']), $search));
        }));

        $data = $this->shell(
            'Aggregate Reports - YouthNexus Pulse',
            'Aggregate Reports',
            'Division rollups across the zone.',
            'zonalsecretary/reports'
        );
        $data += ['catalog' => $catalog, 'reports' => $reports, 'category' => $category, 'search' => $search,
            'zoneName' => $this->zoneName($zonalId), 'flash' => $this->pullSecretaryFlash()];
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->view('zonalsecretary/reports', $data);
    }

    /**
     * Generate a zone report from the catalog (secretary).
     */    public function generateReport() {
        $this->requireZonalSecretary();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('zonalsecretary/reports');
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->setSecretaryFlash('error', 'Refresh the page before generating a report.');
            $this->redirect('zonalsecretary/reports');
        }
        try {
            $reportId = $this->model('ZoneReportModel')->createReport(
                (int) ($_SESSION['zonal_id'] ?? 0), (int) $_SESSION['user_id'],
                (int) ($_POST['report_type_id'] ?? 0),
                trim($_POST['start'] ?? ''), trim($_POST['end'] ?? ''),
                trim($_POST['format'] ?? 'OnScreen')
            );
        } catch (InvalidArgumentException $e) {
            $this->setSecretaryFlash('error', $e->getMessage());
            $this->redirect('zonalsecretary/reports');
        }
        $this->setSecretaryFlash('success', "Report generated (ID {$reportId}).");
        $this->redirect('zonalsecretary/reportpreview/' . $reportId);
    }

    private function zoneName(int $zonalId): string {
        $zone = $this->model('ZoneFundModel')->getZone($zonalId);
        return $zone->zonal_name ?? 'Zone';
    }

    private function setSecretaryFlash(string $type, string $message): void {        $_SESSION['zonal_secretary_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullSecretaryFlash(): ?array {
        $flash = $_SESSION['zonal_secretary_flash'] ?? null;
        unset($_SESSION['zonal_secretary_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function reportpreview($id = null) {
        $this->requireZonalSecretary();
        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $report = $this->model('ZoneReportModel')->getReport($zonalId, (int) $id);
        if (!$report) {
            $this->redirect('zonalsecretary/reports');
        }
        $data = $this->shell('Report Preview — YouthNexus Pulse', 'Report Preview', $report->type_name ?? 'Report', 'zonalsecretary/reports');
        $data['report'] = $report;
        $data['snapshot'] = json_decode((string) ($report->data_snapshot ?? ''), true) ?? [];
        $data['zoneName'] = $this->zoneName($zonalId);
        $this->view('zonalsecretary/reportpreview', $data);
    }

    public function exportreport($id = null) {
        $this->requireZonalSecretary();
        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $report = $this->model('ZoneReportModel')->getReport($zonalId, (int) $id);
        if (!$report) {
            $this->redirect('zonalsecretary/reports');
        }
        $snapshot = json_decode((string) ($report->data_snapshot ?? ''), true) ?? [];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="zone-report-' . (int) $report->report_id . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $snapshot['columns'] ?? []);
        foreach ($snapshot['rows'] ?? [] as $row) {
            fputcsv($out, array_values(is_array($row) ? $row : (array) $row));
        }
        fclose($out);
    }

    public function exportreports() {
        $this->requireZonalSecretary();
        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $model = $this->model('ZoneReportModel');
        $zoneName = preg_replace('/[^A-Za-z0-9]+/', '_', $this->zoneName($zonalId));
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $zoneName . '_Aggregate_Reports.csv"');
        $out = fopen('php://output', 'w'); fputcsv($out, ['Report', 'Category', 'Period', 'Format', 'Generated']);
        foreach ($model->getReports($zonalId) as $r) {
            fputcsv($out, [$r->type_name, $r->category, $r->date_range_start . ' to ' . $r->date_range_end, $r->format, $r->generated_at]);
        }
        fclose($out);
    }

    /**
     * Zone attendance statistics are read-only rollups by division.
     */
    public function attendance() {
        $this->requireZonalSecretary();

        $state = $this->demoState();
        $division = trim((string)($_GET['division'] ?? 'All divisions'));
        $period = trim((string)($_GET['period'] ?? '90'));
        $divisionRows = [
            ['name' => 'Gampaha Division', 'clubs' => 8, 'sessions' => 7, 'present' => 214, 'possible' => 276, 'rate' => 78],
            ['name' => 'Ja-Ela Division', 'clubs' => 6, 'sessions' => 6, 'present' => 165, 'possible' => 210, 'rate' => 79],
            ['name' => 'Negombo Division', 'clubs' => 5, 'sessions' => 5, 'present' => 107, 'possible' => 144, 'rate' => 74],
        ];
        if (in_array($division, ['Gampaha Division', 'Ja-Ela Division', 'Negombo Division'], true)) {
            $divisionRows = array_values(array_filter($divisionRows, static fn($row) => $row['name'] === $division));
        } else {
            $division = 'All divisions';
        }

        $data = $this->shell(
            'Zone Attendance — YouthNexus Pulse',
            'Zone Attendance',
            'Attendance statistics across Gampaha Zone.',
            'zonalsecretary/attendance'
        );
        $data['divisionRows'] = $divisionRows;
        $data['division'] = $division;
        $data['period'] = in_array($period, ['30', '90', '365'], true) ? $period : '90';
        $data['zoneAttendance'] = ['rate' => 78, 'present' => 486, 'possible' => 630, 'sessions' => 18, 'clubs' => 19];
        $data['eventCount'] = count($state['events']);

        $this->view('zonalsecretary/attendance', $data);
    }
}
