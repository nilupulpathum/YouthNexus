<?php

class Divisionalclubhealth extends Controller {
    private const ALLOWED_ROLES = ['DivisionalCoordinator', 'DivisionalSecretary', 'DivisionalTreasurer'];

    private function requireDivisionalActor(): void {
        if (empty($_SESSION['user_id'])) $this->redirect('auth/signin');
        if (!in_array($_SESSION['user_role'] ?? '', self::ALLOWED_ROLES, true)) $this->redirect('home');
        if ((int) ($_SESSION['division_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a division.');
        }
    }

    private function verifyCsrf(): bool {
        $token = (string) ($_POST['csrf_token'] ?? '');
        return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['divisional_club_health_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['divisional_club_health_flash'] ?? null;
        unset($_SESSION['divisional_club_health_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function index(): void {
        $this->requireDivisionalActor();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalClubHealthModel');
        $division = $model->getDivision($divisionId);
        if (!$division) {
            http_response_code(500);
            exit('The division profile could not be loaded.');
        }

        try {
            $scores = $model->refreshDivision($divisionId);
            $clubs = $model->getClubs($divisionId, $scores);
            $details = [];
            foreach ($clubs as $club) {
                $details[(int) $club->club_id] = $model->getClubDetails($divisionId, (int) $club->club_id);
            }
        } catch (Throwable $exception) {
            http_response_code(500);
            exit('Club health data could not be calculated. Run the club health migration and try again.');
        }

        $this->view('divisionalclubhealth/index', [
            'division' => $division,
            'clubs' => $clubs,
            'summary' => $model->getSummary($clubs),
            'clubDetails' => $details,
            'csrfToken' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
            'actorRole' => $_SESSION['user_role'],
            'userName' => $_SESSION['user_name'] ?? 'Divisional Officer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function flag($clubId = null): void {
        $this->requireDivisionalActor();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || (int) $clubId < 1) {
            $this->setFlash('error', 'The concern could not be verified. Please try again.');
            $this->redirect('divisionalclubhealth');
        }
        try {
            $this->model('DivisionalClubHealthModel')->raiseFlag(
                (int) $_SESSION['division_id'],
                (int) $clubId,
                (int) $_SESSION['user_id'],
                (string) $_SESSION['user_role'],
                trim((string) ($_POST['reason'] ?? ''))
            );
            $this->setFlash('success', 'The club health concern was recorded for administrative review.');
        } catch (Throwable $exception) {
            $allowed = [
                'Your role cannot raise a club health concern.',
                'The selected club is outside your division.',
                'Provide a clear reason between 10 and 1000 characters.',
            ];
            $this->setFlash('error', in_array($exception->getMessage(), $allowed, true)
                ? $exception->getMessage() : 'The concern could not be recorded.');
        }
        $this->redirect('divisionalclubhealth');
    }

    public function export(): void {
        $this->requireDivisionalActor();
        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalClubHealthModel');
        $scores = $model->refreshDivision($divisionId);
        $clubs = $model->getClubs($divisionId, $scores);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="club-health-' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Club Code', 'Club', 'Overall Score', 'Status', 'Event Score', 'Finance Score', 'Attendance Score', 'Completed Events', 'Attendance Recorded', 'Approved Ledger Entries', 'Open Flags']);
        foreach ($clubs as $club) {
            fputcsv($output, [
                CsvSecurity::cell($club->club_code), CsvSecurity::cell($club->club_name), $club->score['overall_score'],
                CsvSecurity::cell($club->score['health_status']), $club->score['event_score'], $club->score['finance_score'],
                $club->score['attendance_score'], $club->score['completed_events'],
                $club->score['attendance_recorded'], $club->score['approved_entries'], (int) $club->open_flags,
            ]);
        }
        fclose($output);
        exit;
    }
}
