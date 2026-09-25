<?php

class Clubhealth extends Controller {

    private function requireNYSCAdmin(): void {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SESSION['user_role'] ?? '') !== 'NYSCAdministrator') {
            $this->redirect('home');
        }
    }

    private function verifyCsrf(): bool {
        $token = (string) ($_POST['csrf_token'] ?? '');
        return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['nysc_club_health_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['nysc_club_health_flash'] ?? null;
        unset($_SESSION['nysc_club_health_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function index(): void {
        $this->requireNYSCAdmin();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $model = $this->model('ClubHealthModel');

        try {
            $scores = $model->refreshNationwide();
            $clubs = $model->getNationwideClubs($scores);
            $provinces = $model->getAllProvinces();
            $divisions = $model->getAllDivisions();

            $clubDetails = [];
            foreach ($clubs as $club) {
                $clubDetails[(int) $club->club_id] = $model->getNationwideClubDetails((int) $club->club_id);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            exit('Failed to load club health monitoring data: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }

        $summary = $model->getSummary($clubs);

        $this->view('clubhealth/index', [
            'title'            => 'Monitor Club Health — NYSC Administrator',
            'pageTitle'        => 'Monitor Club Health',
            'pageDescription'  => 'National club governance, health scoring, compliance review and disbandment management',
            'currentRoute'     => 'clubhealth',
            'userRole'         => 'NYSCAdministrator',
            'userName'         => $_SESSION['user_name'] ?? 'National Administrator',
            'userEmail'        => $_SESSION['user_email'] ?? '',
            'provinces'        => $provinces,
            'divisions'        => $divisions,
            'clubs'            => $clubs,
            'summary'          => $summary,
            'clubDetails'      => $clubDetails,
            'csrfToken'        => $_SESSION['csrf_token'],
            'flash'            => $this->pullFlash(),
        ]);
    }

    public function retrievefunds($clubId = null): void {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || (int) $clubId < 1) {
            $this->setFlash('error', 'Invalid request or session expired. Please try again.');
            $this->redirect('clubhealth');
        }

        try {
            $model = $this->model('ClubHealthModel');
            $retrieved = $model->retrieveRemainingFunds((int) $clubId, (int) $_SESSION['user_id']);
            $this->setFlash(
                'success',
                'Successfully retrieved remaining funds of LKR ' . number_format($retrieved, 2) . ' into NYSC National Ledger. Club ledger balance is now zero.'
            );
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('clubhealth');
    }

    public function warning($clubId = null): void {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || (int) $clubId < 1) {
            $this->setFlash('error', 'Invalid request or session expired. Please try again.');
            $this->redirect('clubhealth');
        }

        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        try {
            $model = $this->model('ClubHealthModel');
            $result = $model->issueDisbandWarning((int) $clubId, (int) $_SESSION['user_id'], $subject, $message);
            $notified = $result['notified_count'];
            $clubName = $result['club']->club_name;
            $this->setFlash(
                'success',
                "Official Disband Warning sent to {$notified} executive(s) of \"{$clubName}\". System notifications posted and email dispatches queued."
            );
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('clubhealth');
    }

    public function disband($clubId = null): void {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || (int) $clubId < 1) {
            $this->setFlash('error', 'Invalid request or session expired. Please try again.');
            $this->redirect('clubhealth');
        }

        $reason = trim((string) ($_POST['reason'] ?? ''));

        try {
            $model = $this->model('ClubHealthModel');
            $model->executeDisband((int) $clubId, (int) $_SESSION['user_id'], $reason);
            $this->setFlash(
                'success',
                'Club has been disbanded. Club data has been archived, executive roles revoked to Unassigned, and active zonal/divisional listings removed.'
            );
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('clubhealth');
    }

    public function divisions($province = null): void {
        $this->requireNYSCAdmin();
        header('Content-Type: application/json; charset=UTF-8');

        $model = $this->model('ClubHealthModel');
        $provinceName = $province ? urldecode((string) $province) : null;
        $divisions = $model->getAllDivisions($provinceName ?: null);
        echo json_encode($divisions);
        exit;
    }

    public function export(): void {
        $this->requireNYSCAdmin();

        $model = $this->model('ClubHealthModel');
        $scores = $model->refreshNationwide();
        $clubs = $model->getNationwideClubs($scores);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="nysc-national-club-health-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Club Code',
            'Club Name',
            'Zone',
            'Division',
            'Overall Health Score',
            'Health Status',
            'Flagged',
            'President',
            'Ledger Balance (LKR)',
            'Total Assets',
            'Completed Events',
            'Event Score',
            'Finance Score',
            'Attendance Score',
            'Open Flags'
        ]);

        foreach ($clubs as $club) {
            fputcsv($output, [
                CsvSecurity::cell($club->club_code),
                CsvSecurity::cell($club->club_name),
                CsvSecurity::cell($club->zonal_name),
                CsvSecurity::cell($club->division_name),
                $club->score['overall_score'],
                CsvSecurity::cell($club->score['health_status']),
                $club->flagged ? 'Yes' : 'No',
                CsvSecurity::cell($club->president_name ?? 'N/A'),
                number_format((float)$club->ledger_balance, 2, '.', ''),
                (int)$club->total_asset_count,
                (int)$club->completed_events_count,
                $club->score['event_score'],
                $club->score['finance_score'],
                $club->score['attendance_score'],
                (int)$club->open_flags,
            ]);
        }

        fclose($output);
        exit;
    }
}
