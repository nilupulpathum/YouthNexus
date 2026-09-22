<?php

class Divisionaltreasurer extends Controller {
    private function requireTreasurer(): void {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SESSION['user_role'] ?? '') !== 'DivisionalTreasurer') {
            $this->redirect('home');
        }
        if ((int) ($_SESSION['division_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a division.');
        }
    }

    public function index(): void {
        $this->requireTreasurer();

        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalDashboardModel');

        try {
            $division = $model->getDivision($divisionId);
            if (!$division) {
                throw new RuntimeException('Division not found.');
            }

            $this->view('divisionaltreasurer/index', [
                'division' => $division,
                'summary' => $model->getSummary($divisionId),
                'pendingVoidRequests' => $model->getPendingVoidRequests($divisionId),
                'recentAllocations' => $model->getRecentAllocations($divisionId),
                'clubHealth' => $model->getClubHealthOverview($divisionId),
                'auditReminders' => $model->getAuditReminders($divisionId),
                'userName' => $_SESSION['user_name'] ?? 'Divisional Treasurer',
                'userRole' => $_SESSION['user_role'],
                'userEmail' => $_SESSION['user_email'] ?? '',
            ]);
        } catch (Throwable $exception) {
            http_response_code(500);
            exit('The divisional finance dashboard could not be loaded. Run the divisional workflow migrations and try again.');
        }
    }
}
