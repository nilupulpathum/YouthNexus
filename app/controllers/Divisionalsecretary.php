<?php

class Divisionalsecretary extends Controller {
    private function requireSecretary(): void {
        if (empty($_SESSION['user_id'])) $this->redirect('auth/signin');
        if (($_SESSION['user_role'] ?? '') !== 'DivisionalSecretary') $this->redirect('home');
        if ((int) ($_SESSION['division_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a division.');
        }
    }

    public function index(): void {
        $this->requireSecretary();
        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalDashboardModel');

        try {
            $division = $model->getDivision($divisionId);
            if (!$division) throw new RuntimeException('Division not found.');
            $this->view('divisionalsecretary/index', [
                'division' => $division,
                'summary' => $model->getSecretarySummary($divisionId),
                'events' => $model->getUpcomingEvents($divisionId),
                'attendanceFollowUps' => $model->getAttendanceFollowUps($divisionId),
                'clubHealth' => $model->getClubHealthOverview($divisionId),
                'userName' => $_SESSION['user_name'] ?? 'Divisional Secretary',
                'userRole' => $_SESSION['user_role'],
                'userEmail' => $_SESSION['user_email'] ?? '',
            ]);
        } catch (Throwable $exception) {
            http_response_code(500);
            exit('The divisional secretary dashboard could not be loaded.');
        }
    }
}
