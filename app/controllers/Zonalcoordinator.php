<?php

/**
 * ZonalCoordinator — zonal coordinator dashboards (Z0 scaffolding, Z1 monitor).
 *
 * Presentation-only: mock data comes from ZoneHealthMock (shared with the
 * zonal secretary monitor) mirroring the future backend contract. No
 * database reads or writes. Score bands follow the divisional Figma
 * standard (owner direction 2026-09-12): Healthy 85-100, At Risk 50-84,
 * Dormant below 50 or inactivity. Verification is outside zonal scope.

 *
 * Routes:
 *   zonalcoordinator -> index()  (zonal coordinator only)
 *   zonalcoordinator/clubs -> clubs()  (monitor club health)
 */
class Zonalcoordinator extends Controller {

    private function requireZonalCoordinator() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $allowedRoles = ['ZonalCoordinator', 'zonalcoordinator', 'zonal'];
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
            'userRole'                => $_SESSION['user_role'] ?? 'ZonalCoordinator',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
        ];
    }

    /**
     * Coordinator overview (Z1): zone-health tiles, division averages and
     * club monitoring. Club cards live on clubs().
     */
    public function index() {
        $this->requireZonalCoordinator();

        $mock = $this->model('ZoneHealthMock');
        $clubs = $mock->zoneClubs();

        $data = $this->shell(
            'Zonal Coordinator Overview — YouthNexus Pulse',
            'Zonal Coordinator Overview',
            'Zone health of Gampaha Zone.',
            'zonalcoordinator'
        );
        $data['zoneHealth'] = $mock->zoneHealth($clubs);
        $data['divisions'] = $mock->divisionAverages($clubs);
        $data['announcements'] = [
            ['title' => 'Quarterly reporting window', 'summary' => 'Division coordinators should complete their zone reports by September 30.', 'age' => '1 day ago', 'is_new' => true],
            ['title' => 'NYSC leadership forum', 'summary' => 'Zone representatives should confirm attendance with the zonal secretary.', 'age' => '4 days ago', 'is_new' => false],
        ];
        $data['upcomingEvents'] = [
            ['title' => 'Zone Youth Leadership Forum', 'date' => 'Oct 10, 2026', 'location' => 'Gampaha Youth Centre', 'status' => 'Scheduled', 'status_key' => 'attending'],
            ['title' => 'Digital Skills Workshop', 'date' => 'Oct 22, 2026', 'location' => 'Ja-Ela Community Hall', 'status' => 'Scheduled', 'status_key' => 'attending'],
        ];

        $this->view('zonalcoordinator/index', $data);
    }

    /**
     * Monitor club health (Z1): divisional Figma shape — band tiles, search
     * + division filter + sort + export, division averages, club cards
     * sorted highest first with detail + flag modals. Read-only mock.
     */
    public function clubs() {
        $this->requireZonalCoordinator();

        $mock = $this->model('ZoneHealthMock');
        $clubs = $mock->zoneClubs();
        usort($clubs, static function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $data = $this->shell(
            'Monitor Club Health — YouthNexus Pulse',
            'Monitor Club Health',
            'Monitor club health scores and identify clubs requiring intervention.',
            'zonalcoordinator/clubs'
        );
        $data['zoneHealth'] = $mock->zoneHealth($clubs);
        $data['divisions'] = $mock->divisionAverages($clubs);
        $data['clubs'] = $clubs;

        $this->view('zonalcoordinator/clubs', $data);
    }

    private function programmeState() {
        if (!isset($_SESSION['zonal_secretary_demo']['gampaha'])) {
            $_SESSION['zonal_secretary_demo']['gampaha'] = ['budget' => 2500000.00, 'spent' => 425000.00, 'notifications' => 2, 'events' => [
                ['id' => 'ZE-101', 'title' => 'Zone Youth Leadership Forum', 'type' => 'Leadership', 'date' => '2026-10-10', 'time' => '09:00', 'location' => 'Gampaha Youth Centre', 'budget' => 225000.00, 'audience' => 'All divisions', 'status' => 'Approved'],
                ['id' => 'ZE-102', 'title' => 'Digital Skills Workshop', 'type' => 'Training', 'date' => '2026-10-22', 'time' => '10:00', 'location' => 'Ja-Ela Community Hall', 'budget' => 200000.00, 'audience' => 'Ja-Ela Division', 'status' => 'Pending approval'],
            ]];
        }
        return $_SESSION['zonal_secretary_demo']['gampaha'];
    }

    private function coordinatorCsrf() {
        $_SESSION['zonal_coordinator_csrf'] = $_SESSION['zonal_coordinator_csrf'] ?? bin2hex(random_bytes(32));
        return $_SESSION['zonal_coordinator_csrf'];
    }

    public function events() {
        $this->requireZonalCoordinator(); $state = $this->programmeState();
        $data = $this->shell('Approve Zonal Events — YouthNexus Pulse', 'Approve Zonal Events', 'Review events submitted by the Zonal Secretary for Gampaha Zone.', 'zonalcoordinator/events');
        $data += ['events' => array_reverse($state['events']), 'eventStats' => ['scheduled' => count($state['events']), 'committed' => $state['spent'], 'available' => $state['budget']], 'csrf_token' => $this->coordinatorCsrf(), 'flash' => $_SESSION['zonal_coordinator_flash'] ?? ''];
        unset($_SESSION['zonal_coordinator_flash']); $this->view('zonalcoordinator/events', $data);
    }

    private function decideEvent($status) {
        $this->requireZonalCoordinator();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_string($_POST['csrf_token'] ?? null) || !hash_equals($this->coordinatorCsrf(), $_POST['csrf_token'])) {
            $_SESSION['zonal_coordinator_flash'] = 'Refresh the event queue before submitting a decision.'; $this->redirect('zonalcoordinator/events');
        }
        $id = trim((string)($_POST['event_id'] ?? '')); $remark = trim((string)($_POST['remark'] ?? '')); $state = $this->programmeState(); $found = null;
        foreach ($state['events'] as $index => $event) if ($event['id'] === $id) { $found = $index; break; }
        if ($found === null || $state['events'][$found]['status'] !== 'Pending approval' || $remark === '' || mb_strlen($remark) > 1000) {
            $_SESSION['zonal_coordinator_flash'] = 'A pending event and decision remark are required.';
        } else {
            $state['events'][$found]['status'] = $status; $state['events'][$found]['coordinator_remark'] = $remark; $_SESSION['zonal_secretary_demo']['gampaha'] = $state;
            $_SESSION['zonal_coordinator_flash'] = $status === 'Approved' ? 'Event approved and the Zonal Secretary has been notified.' : 'Event returned to the Zonal Secretary with the requested changes.';
        }
        $this->redirect('zonalcoordinator/events');
    }

    public function approveevent() { $this->decideEvent('Approved'); }
    public function returnevent() { $this->decideEvent('Changes requested'); }

    public function reports() {
        $this->requireZonalCoordinator();
        $reports = [
            ['id' => 'ZCR-101', 'title' => 'Quarterly financial rollup', 'division' => 'All divisions', 'date' => 'Sep 20, 2026'],
            ['id' => 'ZCR-102', 'title' => 'Division attendance rollup', 'division' => 'All divisions', 'date' => 'Sep 18, 2026'],
            ['id' => 'ZCR-103', 'title' => 'Zonal programme activity', 'division' => 'Ja-Ela Division', 'date' => 'Sep 16, 2026'],
        ];
        $data = $this->shell('Aggregate Reports — YouthNexus Pulse', 'Aggregate Reports', 'Generate and review Gampaha Zone divisional rollups.', 'zonalcoordinator/reports');
        $data += ['reports' => $reports]; $this->view('zonalcoordinator/reports', $data);
    }

    public function reportpreview($id = null) {
        $this->requireZonalCoordinator();
        $data = $this->shell('Report Preview — YouthNexus Pulse', 'Report Preview', 'Gampaha Zone aggregate report preview.', 'zonalcoordinator/reports');
        $data['reportId'] = preg_match('/^ZCR-10[1-3]$/', (string)$id) ? $id : 'ZCR-101'; $this->view('zonalcoordinator/reportpreview', $data);
    }

    public function exportreports() {
        $this->requireZonalCoordinator(); header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="Gampaha_Zone_Coordinator_Reports.csv"');
        $out = fopen('php://output', 'w'); fputcsv($out, ['Division', 'Reporting clubs', 'Events', 'Attendance rate', 'Reported balance (LKR)']);
        fputcsv($out, ['Gampaha Division', 8, 7, '78%', '206000']); fputcsv($out, ['Ja-Ela Division', 6, 6, '79%', '222000']); fputcsv($out, ['Negombo Division', 5, 5, '74%', '142000']); fclose($out);
    }

}
