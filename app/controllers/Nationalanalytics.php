<?php

class Nationalanalytics extends Controller {

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

        $selectedZone = !empty($_GET['zone']) ? (int)$_GET['zone'] : null;

        /** @var NationalAnalyticsModel $model */
        $model = $this->model('NationalAnalyticsModel');

        $zones          = $model->getZones();
        $kpis           = $model->getKpis($selectedZone);
        $clubStatus     = $model->getNationalClubStatus($selectedZone);
        $districts      = $model->getHealthBreakdownByDivision($selectedZone);
        $trend          = $model->getMonthlyVolunteerTrend($selectedZone);
        $topClubs       = $model->getTopClubs(5, $selectedZone);
        $divisions      = $model->getFundsAllocatedVsSpentByDivision($selectedZone);
        $voidRateData   = $model->getVoidRateData($selectedZone);
        $queueSummary   = $model->getPendingApplicationsSummary();
        $pendingClubs   = $model->getPendingApplicationsList(10);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->view('nationalanalytics/index', [
            'title'            => 'National Analytics — YouthNexus',
            'currentRoute'     => 'nationalanalytics',
            'userRole'         => 'NYSCAdministrator',
            'userName'         => $_SESSION['user_name'] ?? 'National Admin',
            'userEmail'        => $_SESSION['user_email'] ?? '',
            'selectedZone'     => $selectedZone,
            'zones'            => $zones,
            'kpis'             => $kpis,
            'clubStatus'       => $clubStatus,
            'districts'        => $districts,
            'trend'            => $trend,
            'topClubs'         => $topClubs,
            'divisions'        => $divisions,
            'voidRateData'     => $voidRateData,
            'queueSummary'     => $queueSummary,
            'pendingClubs'     => $pendingClubs,
            'flashSuccess'     => $flashSuccess,
            'flashError'       => $flashError,
            'csrf_token'       => $_SESSION['csrf_token'],
        ]);
    }

    public function sendwarnings() {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                $_SESSION['flash_error'] = 'Invalid security token.';
                $this->redirect('nationalanalytics');
                return;
            }

            /** @var NationalAnalyticsModel $model */
            $model = $this->model('NationalAnalyticsModel');
            $count = $model->sendWarningNotifications($_SESSION['user_id']);

            $_SESSION['flash_success'] = "Successfully sent {$count} administrative alerts to Divisional Coordinators.";
        }

        $this->redirect('nationalanalytics');
    }

    public function remind() {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                  || !empty($_POST['ajax']);

        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'NYSCAdministrator') {
            if ($isAjax) {
                header('Content-Type: application/json', true, 401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            $this->redirect('auth/signin');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                if ($isAjax) {
                    header('Content-Type: application/json', true, 400);
                    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                    exit;
                }
                $_SESSION['flash_error'] = 'Invalid security token.';
                $this->redirect('nationalanalytics');
                return;
            }

            $appId    = $_POST['app_id'] ?? null;
            $clubName = $_POST['club_name'] ?? null;
            $division = $_POST['division'] ?? null;

            /** @var NationalAnalyticsModel $model */
            $model = $this->model('NationalAnalyticsModel');

            if (!empty($appId) || !empty($clubName)) {
                $result = $model->remindCoordinator($appId, $_SESSION['user_id'], $clubName, $division);
                $msg = "Reminder notification sent to the responsible Divisional Coordinator.";
            } else {
                $model->sendWarningNotifications($_SESSION['user_id']);
                $msg = "Reminders sent to all responsible Divisional Coordinators.";
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $msg]);
                exit;
            }

            $_SESSION['flash_success'] = $msg;
        }

        $this->redirect('nationalanalytics');
    }

    public function export() {
        $this->requireNYSCAdmin();

        /** @var NationalAnalyticsModel $model */
        $model = $this->model('NationalAnalyticsModel');
        $kpis = $model->getKpis();
        $districts = $model->getHealthBreakdownByDivision();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="national_analytics_' . date('Y_m_d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['National Analytics Performance Export - NYSC YouthNexus']);
        fputcsv($out, ['Generated At', date('Y-m-d H:i:s')]);
        fputcsv($out, []);

        fputcsv($out, ['Metric', 'Value', 'Unit', 'Notes']);
        foreach ($kpis as $k) {
            fputcsv($out, [$k['title'], $k['value'], $k['unit'], $k['sub']]);
        }
        fputcsv($out, []);

        fputcsv($out, ['Division', 'Total Clubs', 'Active %', 'At Risk %', 'Dormant %', 'Vitality Note']);
        foreach ($districts as $d) {
            fputcsv($out, [$d['name'], $d['clubs'], $d['active'] . '%', $d['risk'] . '%', $d['dormant'] . '%', $d['note']]);
        }

        fclose($out);
        exit;
    }
}
