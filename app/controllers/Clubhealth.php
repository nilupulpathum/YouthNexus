<?php

class Clubhealth extends Controller {

    /**
     * Server-side role protection. Only logged-in users with role 'DivisionalCoordinator'
     * may access the Monitor Club Health dashboard.
     */
    private function requireCoordinator() {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'DivisionalCoordinator') {
            $this->redirect('auth/signin');
        }

        // Ensure division_id is available from session or populated from database
        if (empty($_SESSION['division_id']) && !empty($_SESSION['user_id'])) {
            $userModel = $this->model('UserModel');
            $user = $userModel->findByUserId((int)$_SESSION['user_id']);
            if ($user && !empty($user->division_id)) {
                $_SESSION['division_id'] = (int)$user->division_id;
            }
        }

        // Ensure user initials for avatar
        if (empty($_SESSION['user_initials']) && !empty($_SESSION['user_name'])) {
            $parts = explode(' ', trim($_SESSION['user_name']));
            $initials = '';
            foreach ($parts as $p) {
                if (!empty($p)) {
                    $initials .= strtoupper($p[0]);
                }
            }
            $_SESSION['user_initials'] = substr($initials, 0, 2) ?: 'DC';
        }
    }

    /**
     * Main dashboard action: list/grid view of club health within the coordinator's division.
     */
    public function index() {
        $this->requireCoordinator();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $divisionId  = $_SESSION['division_id'] ?? null;
        $healthModel = $this->model('ClubHealthModel');

        $clubs        = $divisionId ? $healthModel->getClubsByDivision($divisionId) : [];
        $counts       = $divisionId ? $healthModel->getHealthStatusCounts($divisionId) : ['Green' => 0, 'Yellow' => 0, 'Red' => 0, 'Total' => 0];
        $divisionName = $divisionId ? $healthModel->getDivisionName($divisionId) : 'All Divisions';
        $committees   = $divisionId ? $healthModel->getExecutiveCommitteesByDivision($divisionId) : [];

        $this->view('club-health/dashboard', [
            'title'        => 'Monitor Club Health — YouthNexus Pulse',
            'clubs'        => $clubs,
            'counts'       => $counts,
            'divisionName' => $divisionName,
            'committees'   => $committees,
            'csrf_token'   => $_SESSION['csrf_token'],
        ]);
    }

    /**
     * Detail route placeholder.
     * Note: Full health score breakdown modal / detail page is deferred to Phase 2.
     */
    public function details($id = null) {
        $this->requireCoordinator();
        $this->redirect('clubhealth');
    }
}
