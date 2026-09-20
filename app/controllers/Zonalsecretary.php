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

        $state = $this->demoState();
        $errors = [];
        $old = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old = $_POST;
            if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($this->eventCsrf(), $_POST['csrf_token'])) {
                $errors['general'] = 'Refresh the page before creating an event.';
            }
            $title = trim((string)($_POST['title'] ?? ''));
            $type = trim((string)($_POST['event_type'] ?? ''));
            $date = trim((string)($_POST['event_date'] ?? ''));
            $time = trim((string)($_POST['event_time'] ?? ''));
            $location = trim((string)($_POST['location'] ?? ''));
            $audience = trim((string)($_POST['audience'] ?? 'All divisions'));
            $budgetRaw = trim((string)($_POST['budget'] ?? ''));
            $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($title === '' || mb_strlen($title) > 150) $errors['title'] = 'Enter an event title of 150 characters or fewer.';
            if ($type === '' || mb_strlen($type) > 50) $errors['event_type'] = 'Enter an event type of 50 characters or fewer.';
            if (!$dateValue || $dateValue->format('Y-m-d') !== $date) $errors['event_date'] = 'Enter a valid event date.';
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) $errors['event_time'] = 'Enter a valid event time.';
            if ($dateValue && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
                $eventAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
                if ($eventAt && $eventAt <= new DateTimeImmutable()) {
                    $errors['event_date'] = 'Choose a date and time in the future.';
                }
            }
            if ($location === '' || mb_strlen($location) > 255) $errors['location'] = 'Enter a location of 255 characters or fewer.';
            if (!in_array($audience, ['All divisions', 'Gampaha Division', 'Ja-Ela Division', 'Negombo Division'], true)) $errors['audience'] = 'Select a valid audience.';
            if (!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D', str_replace(',', '', $budgetRaw)) || (float)str_replace(',', '', $budgetRaw) <= 0) {
                $errors['budget'] = 'Enter a positive budget amount.';
            }
            $budget = (float)str_replace(',', '', $budgetRaw);
            if ($budget > (float)$state['budget']) $errors['budget'] = 'The requested budget exceeds the available zonal balance.';

            if (!$errors) {
                $state['budget'] -= $budget;
                $state['spent'] += $budget;
                $state['notifications']++;
                $state['events'][] = ['id' => 'ZE-' . str_pad((string)(count($state['events']) + 101), 3, '0', STR_PAD_LEFT), 'title' => $title, 'type' => $type, 'date' => $date, 'time' => $time, 'location' => $location, 'budget' => $budget, 'audience' => $audience, 'status' => 'Pending approval'];
                $this->saveDemoState($state);
                $_SESSION['zonal_event_flash'] = 'Event submitted to the Zonal Coordinator for approval.';
                $_SESSION['zonal_event_csrf'] = bin2hex(random_bytes(32));
                $this->redirect('zonalsecretary/events');
            }
        }

        $data = $this->shell(
            'Zonal Events — YouthNexus Pulse',
            'Zonal Events',
            'Create zonal events and notify divisions and clubs.',
            'zonalsecretary/events'
        );
        $data['state'] = $state;
        $data['events'] = array_reverse($state['events']);
        $data['eventStats'] = [
            'scheduled' => count($state['events']),
            'committed' => $state['spent'],
            'available' => $state['budget'],
        ];
        $data['csrf_token'] = $this->eventCsrf();
        $data['flash'] = $_SESSION['zonal_event_flash'] ?? '';
        $data['errors'] = $errors;
        $data['old'] = $old;
        unset($_SESSION['zonal_event_flash']);

        $this->view('zonalsecretary/events', $data);
    }

    /**
     * Aggregate reports placeholder (Z5 builds division rollup table).
     */
    public function reports() {
        $this->requireZonalSecretary();
        $catalog = ['Financial' => ['Quarterly financial rollup', 'Division fund allocation summary'], 'Events' => ['Zonal programme activity'], 'Attendance' => ['Division attendance rollup'], 'Club Health' => ['Club health score rollup']];
        $reports = [
            ['id' => 'ZR-101', 'category' => 'Financial', 'type' => 'Quarterly financial rollup', 'division' => 'All divisions', 'format' => 'PDF', 'date' => 'Sep 20, 2026'],
            ['id' => 'ZR-102', 'category' => 'Attendance', 'type' => 'Division attendance rollup', 'division' => 'All divisions', 'format' => 'CSV', 'date' => 'Sep 18, 2026'],
            ['id' => 'ZR-103', 'category' => 'Events', 'type' => 'Zonal programme activity', 'division' => 'Ja-Ela Division', 'format' => 'On-screen', 'date' => 'Sep 16, 2026'],
        ];
        $category = trim((string)($_GET['category'] ?? ''));
        $division = trim((string)($_GET['division'] ?? ''));
        $search = strtolower(trim((string)($_GET['search'] ?? '')));
        $allowedDivisions = ['Gampaha Division', 'Ja-Ela Division', 'Negombo Division'];
        if ($division !== '' && !in_array($division, $allowedDivisions, true)) $division = '';
        if ($category !== '' && !isset($catalog[$category])) $category = '';
        $reports = array_values(array_filter($reports, static function ($report) use ($category, $division, $search) {
            return ($category === '' || $report['category'] === $category)
                && ($division === '' || $report['division'] === 'All divisions' || $report['division'] === $division)
                && ($search === '' || str_contains(strtolower($report['type'] . ' ' . $report['division']), $search));
        }));
        $data = $this->shell(
            'Aggregate Reports — YouthNexus Pulse',
            'Aggregate Reports',
            'Division rollups across Gampaha Zone.',
            'zonalsecretary/reports'
        );
        $data += ['catalog' => $catalog, 'reports' => $reports, 'category' => $category, 'division' => $division, 'search' => $search];
        $this->view('zonalsecretary/reports', $data);
    }

    public function reportpreview($id = null) {
        $this->requireZonalSecretary();
        $data = $this->shell('Report Preview — YouthNexus Pulse', 'Report Preview', 'Gampaha Zone aggregate report preview.', 'zonalsecretary/reports');
        $data['reportId'] = preg_match('/^ZR-10[1-3]$/', (string)$id) ? $id : 'ZR-101';
        $this->view('zonalsecretary/reportpreview', $data);
    }

    public function exportreports() {
        $this->requireZonalSecretary();
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="Gampaha_Zone_Aggregate_Reports.csv"');
        $out = fopen('php://output', 'w'); fputcsv($out, ['Division', 'Reporting clubs', 'Events', 'Attendance rate', 'Reported balance (LKR)']);
        fputcsv($out, ['Gampaha Division', 8, 7, '78%', '206000']); fputcsv($out, ['Ja-Ela Division', 6, 6, '79%', '222000']); fputcsv($out, ['Negombo Division', 5, 5, '74%', '142000']); fclose($out);
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
