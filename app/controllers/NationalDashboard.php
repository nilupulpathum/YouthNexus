<?php

class NationalDashboard extends Controller {

    private function requireNYSCAdmin() {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'NYSCAdministrator') {
            $this->redirect('auth/signin');
        }
    }

    public function index() {
        $this->requireNYSCAdmin();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $model = $this->model('NationalDashboardModel');

        $totalYouth       = $model->getTotalYouth();
        $totalClubs       = $model->getTotalClubs();
        $totalHours       = $model->getTotalVolunteerHours();
        $totalFunds       = $model->getTotalFunds();
        $health           = $model->getHealthDistribution();
        $pendingApps      = $model->getPendingApplications();
        $overdueAudits    = $model->getOverdueAudits();
        $unresolvedFlags  = $model->getUnresolvedFlags();
        $missingReports   = $model->getMissingReports();
        $activities       = $model->getRecentActivity();

        $this->view('nationaldashboard/index', [
            'title'            => 'National Dashboard — YouthNexus',
            'pageTitle'        => 'Welcome back, Administrator',
            'pageDescription'  => 'National governance overview — all zones, divisions and clubs',
            'currentRoute'     => 'nationaldashboard',
            'userRole'         => 'NYSCAdministrator',
            'userName'         => $_SESSION['user_name'] ?? 'National Admin',
            'userEmail'        => $_SESSION['user_email'] ?? '',
            'totalYouth'       => $totalYouth,
            'totalClubs'       => $totalClubs,
            'totalHours'       => $totalHours,
            'totalFunds'       => $totalFunds,
            'health'           => $health,
            'pendingApps'      => $pendingApps,
            'overdueAudits'    => $overdueAudits,
            'unresolvedFlags'  => $unresolvedFlags,
            'missingReports'   => $missingReports,
            'activities'       => $activities,
            'csrf_token'       => $_SESSION['csrf_token'],
        ]);
    }
}
