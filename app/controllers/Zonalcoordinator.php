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

}
