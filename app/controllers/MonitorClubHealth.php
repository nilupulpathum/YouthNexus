<?php

class MonitorClubHealth extends Controller {

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
        $healthModel = $this->model('MonitorClubHealthModel');

        $clubs        = $divisionId ? $healthModel->getClubsByDivision($divisionId) : [];
        $counts       = $divisionId ? $healthModel->getHealthStatusCounts($divisionId) : ['Green' => 0, 'Yellow' => 0, 'Red' => 0, 'Total' => 0];
        $divisionName = $divisionId ? $healthModel->getDivisionName($divisionId) : 'All Divisions';
        $committees   = $divisionId ? $healthModel->getExecutiveCommitteesByDivision($divisionId) : [];

        $this->view('monitorclubhealth/monitor-club-health', [
            'title'        => 'Monitor Club Health — YouthNexus Pulse',
            'clubs'        => $clubs,
            'counts'       => $counts,
            'divisionName' => $divisionName,
            'committees'   => $committees,
            'csrf_token'   => $_SESSION['csrf_token'],
            'userName'     => $_SESSION['user_name'] ?? 'Coordinator',
            'userRole'     => $_SESSION['user_role'] ?? 'DivisionalCoordinator',
        ]);
    }

    public function details($id = null) {
        $this->requireCoordinator();
        $clubId = (int)$id;
        // scope-check: this club must belong to the coordinator's division
        $clubModel = $this->model('ClubModel');
        $club = $clubModel->findById($clubId);
        if (!$club || $club->division_id != ($_SESSION['division_id'] ?? 0)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Club not found or not in scope.']);
            exit();
        }

        $eventModel = $this->model('EventModel');

        // events this club organized, or was targeted by
        $events = $eventModel->findEventsForClub($clubId);

        // Calculate Issue A: 3-month summary stats
        $threeMonthsAgo = strtotime('-3 months');
        $conductedCount = 0;
        $sumRate = 0;
        $eventsWithAttendance = 0;

        foreach ($events as $event) {
            $eventStart = is_object($event) ? $event->start_datetime : $event['start_datetime'];
            $eventTs = strtotime($eventStart);
            if ($eventTs >= $threeMonthsAgo) {
                $conductedCount++;
                $recorded = (int)(is_object($event) ? $event->attendance_recorded_count : $event['attendance_recorded_count']);
                $present = (int)(is_object($event) ? $event->present_count : $event['present_count']);
                if ($recorded > 0) {
                    $sumRate += ($present / $recorded);
                    $eventsWithAttendance++;
                }
            }
        }

        $avgAttendanceRate = null;
        if ($conductedCount > 0) {
            if ($eventsWithAttendance > 0) {
                $avgAttendanceRate = round(($sumRate / $eventsWithAttendance) * 100, 0);
            } else {
                $avgAttendanceRate = 0;
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'club' => $club, 
            'events' => $events,
            'summary' => [
                'conducted_count' => $conductedCount,
                'avg_attendance_rate' => $avgAttendanceRate
            ]
        ]);
        exit();
    }
}
