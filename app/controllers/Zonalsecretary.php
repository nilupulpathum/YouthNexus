<?php

/**
 * Zonalsecretary — zonal secretary dashboards (Z0 scaffolding).
 *
 * Presentation-only: mock data mirroring the future backend contract.
 * No database reads or writes. Full pages land in Z4 (dashboard +
 * zonal-event form), Z5 (aggregate reports), Z6 (broadcast).
 *
 * Routes:
 *   zonalsecretary -> index()  (zonal secretary only)
 *   zonalsecretary/clubs -> clubs()  (monitor club health, shared Z1 build)
 *   zonalsecretary/events -> events()
 *   zonalsecretary/attendance -> attendance()
 *   zonalsecretary/reports -> reports()
 */
class Zonalsecretary extends Controller {

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
     * Secretary overview placeholder (Z4 builds shortcuts + stats).
     */
    public function index() {
        $this->requireZonalSecretary();

        $data = $this->shell(
            'Zonal Secretary Overview — YouthNexus Pulse',
            'Zonal Secretary Overview',
            'Zonal events, reports and attendance of Gampaha Zone.',
            'zonalsecretary'
        );

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
     * Zonal events placeholder (Z4 builds event form + budget mock).
     */
    public function events() {
        $this->requireZonalSecretary();

        $data = $this->shell(
            'Zonal Events — YouthNexus Pulse',
            'Zonal Events',
            'Create zonal events and notify divisions and clubs.',
            'zonalsecretary/events'
        );

        $this->view('zonalsecretary/events', $data);
    }

    /**
     * Aggregate reports placeholder (Z5 builds division rollup table).
     */
    public function reports() {
        $this->requireZonalSecretary();

        $data = $this->shell(
            'Aggregate Reports — YouthNexus Pulse',
            'Aggregate Reports',
            'Division rollups across Gampaha Zone.',
            'zonalsecretary/reports'
        );

        $this->view('zonalsecretary/reports', $data);
    }

    /**
     * Zone attendance statistics placeholder (Z4 builds the full stats —
     * added 2026-09-12 per the zonal secretary workflow step "View
     * Attendance Statistics Across Zone").
     */
    public function attendance() {
        $this->requireZonalSecretary();

        $data = $this->shell(
            'Zone Attendance — YouthNexus Pulse',
            'Zone Attendance',
            'Attendance statistics across Gampaha Zone.',
            'zonalsecretary/attendance'
        );

        $this->view('zonalsecretary/attendance', $data);
    }
}
