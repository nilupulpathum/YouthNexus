<?php

class Divisionalcoordinator extends Controller {
    private function requireCoordinator(): void {
        if (empty($_SESSION['user_id'])) $this->redirect('auth/signin');
        if (($_SESSION['user_role'] ?? '') !== 'DivisionalCoordinator') $this->redirect('home');
        if ((int) ($_SESSION['division_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a division.');
        }
    }

    public function index(): void {
        $this->requireCoordinator();
        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalDashboardModel');

        try {
            $division = $model->getDivision($divisionId);
            if (!$division) throw new RuntimeException('Division not found.');
            $this->view('divisionalcoordinator/index', [
                'division' => $division,
                'summary' => $model->getCoordinatorSummary($divisionId),
                'applications' => $model->getPendingClubApplications($divisionId),
                'events' => $model->getPendingEventApprovals($divisionId),
                'clubHealth' => $model->getClubHealthOverview($divisionId),
                'userName' => $_SESSION['user_name'] ?? 'Divisional Coordinator',
                'userRole' => $_SESSION['user_role'],
                'userEmail' => $_SESSION['user_email'] ?? '',
            ]);
        } catch (Throwable $exception) {
            http_response_code(500);
            exit('The divisional coordinator dashboard could not be loaded.');
        }
    }
}
