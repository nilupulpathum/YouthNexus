<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../app/core/phpmailer/Exception.php';
require_once '../app/core/phpmailer/PHPMailer.php';
require_once '../app/core/phpmailer/SMTP.php';

/**
 * Clubhealth — Monitor Club Health (NYSC Administration)
 *
 * Route: /clubhealth
 * Actions: index | divisions | flag | warn | disband | export
 *
 * Mirrors the divisional UI (DivisionalClubhealthController) but adds:
 *   - zone / division cascading filter
 *   - dormant-only filter (bucket=dormant)
 *   - search across all clubs nationally
 *   - issue disband warning + execute disband for dormant clubs
 */
class Clubhealth extends Controller {

    /* ---------------------------------------------------------------
     * Guards & helpers
     * --------------------------------------------------------------- */
    private function requireNYSCAdmin(): void {
        if (empty($_SESSION['user_id'])) $this->redirect('auth/signin');
        if (($_SESSION['user_role'] ?? '') !== 'NYSCAdministrator') $this->redirect('auth/signin');
    }

    private function verifyCsrf(): bool {
        $token = (string) ($_POST['csrf_token'] ?? '');
        return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['ch_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['ch_flash'] ?? null;
        unset($_SESSION['ch_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function readFilters(): array {
        return [
            'zone'     => !empty($_GET['zone'])     ? (int)$_GET['zone']     : null,
            'division' => !empty($_GET['division']) ? (int)$_GET['division'] : null,
            'q'        => isset($_GET['q'])         ? trim($_GET['q'])       : '',
            'bucket'   => in_array($_GET['bucket'] ?? '', ['green', 'yellow', 'dormant'], true)
                          ? $_GET['bucket'] : '',
        ];
    }

    private function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /* ---------------------------------------------------------------
     * GET /clubhealth  — main page
     * --------------------------------------------------------------- */
    public function index(): void {
        $this->requireNYSCAdmin();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $filters = $this->readFilters();

        /** @var ClubHealthModel $model */
        $model     = $this->model('ClubHealthModel');
        $zones     = $model->getZones();
        $divisions = !empty($filters['zone']) ? $model->getDivisionsByZone($filters['zone']) : [];
        $summary   = $model->getSummary($filters);
        $clubs     = $model->getClubs($filters);

        // Build the per-club details JSON blob (same pattern as divisional view)
        $details = [];
        foreach ($clubs as $club) {
            $details[(int)$club->club_id] = $model->getClubDetails((int)$club->club_id);
        }
        $detailsJson = json_encode($details, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $this->view('clubhealth/index', [
            'title'        => 'Monitor Club Health — YouthNexus',
            'currentRoute' => 'clubhealth',
            'userRole'     => 'NYSCAdministrator',
            'userName'     => $_SESSION['user_name']  ?? 'National Admin',
            'userEmail'    => $_SESSION['user_email'] ?? '',
            'filters'      => $filters,
            'zones'        => $zones,
            'divisions'    => $divisions,
            'summary'      => $summary,
            'clubs'        => $clubs,
            'detailsJson'  => $detailsJson,
            'flash'        => $this->pullFlash(),
            'csrfToken'    => $_SESSION['csrf_token'],
        ]);
    }

    /* ---------------------------------------------------------------
     * GET /clubhealth/divisions?zone_id=N
     * Cascading dropdown — returns JSON list of divisions for a zone.
     * --------------------------------------------------------------- */
    public function divisions(): void {
        $this->requireNYSCAdmin();

        $zoneId = !empty($_GET['zone_id']) ? (int)$_GET['zone_id'] : 0;
        /** @var ClubHealthModel $model */
        $model = $this->model('ClubHealthModel');
        $rows  = $zoneId ? $model->getDivisionsByZone($zoneId) : [];

        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => (int)$r->division_id, 'name' => $r->division_name];
        }
        $this->json(['success' => true, 'divisions' => $out]);
    }

    /* ---------------------------------------------------------------
     * POST /clubhealth/warn/{id}
     * Issue a disband warning: in-app notification to every executive +
     * email to Club President & Secretary.
     * --------------------------------------------------------------- */
    public function warn($id = ''): void {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        if (!$this->verifyCsrf())                  $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);

        $clubId = (int)($id ?: ($_POST['club_id'] ?? 0));
        if ($clubId <= 0) $this->json(['success' => false, 'message' => 'Invalid club id.'], 400);

        /** @var ClubHealthModel $model */
        $model      = $this->model('ClubHealthModel');
        $notified   = $model->markWarning($clubId, (int)$_SESSION['user_id']);
        $recipients = $model->getWarningRecipients($clubId);
        $clubName   = $model->getClubName($clubId) ?? 'your club';

        $emailed = 0;
        foreach ($recipients as $r) {
            if ($this->sendWarningEmail($r->email, $r->first_name, $clubName)) $emailed++;
        }

        /** @var AuditLogModel $audit */
        $audit = $this->model('AuditLogModel');
        $audit->log($_SESSION['user_id'], 'WARN_DISBAND_CLUB', 'Club', $clubId,
            "NYSC Admin issued a disband warning for club ID {$clubId}: {$notified} notification(s), {$emailed} email(s).");

        $this->json([
            'success'  => true,
            'message'  => "Disband warning issued — {$notified} executive notification(s) and {$emailed} email(s) sent.",
            'notified' => $notified,
            'emailed'  => $emailed,
        ]);
    }

    /* ---------------------------------------------------------------
     * POST /clubhealth/disband/{id}
     * Execute disband: archive club, clear fund balance, revoke users.
     * --------------------------------------------------------------- */
    public function disband($id = ''): void {
        $this->requireNYSCAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        if (!$this->verifyCsrf())                  $this->json(['success' => false, 'message' => 'Invalid security token.'], 403);

        $clubId = (int)($id ?: ($_POST['club_id'] ?? 0));
        $reason = trim($_POST['reason'] ?? '');

        if ($clubId <= 0)          $this->json(['success' => false, 'message' => 'Invalid club id.'], 400);
        if (mb_strlen($reason) < 10) $this->json(['success' => false, 'message' => 'A disband reason of at least 10 characters is required.'], 422);

        /** @var ClubHealthModel $model */
        $model  = $this->model('ClubHealthModel');
        $result = $model->disbandClub($clubId, (int)$_SESSION['user_id'], $reason);

        if (!$result['success']) $this->json($result, 409);

        /** @var AuditLogModel $audit */
        $audit = $this->model('AuditLogModel');
        $audit->log($_SESSION['user_id'], 'DISBAND_CLUB', 'Club', $clubId,
            "NYSC Admin disbanded club '{$result['clubName']}' (ID {$clubId}). Reason: {$reason}. " .
            "Balance cleared: {$result['balanceCleared']}. Users revoked: {$result['usersRevoked']}.");

        $this->json([
            'success'        => true,
            'message'        => "Club '{$result['clubName']}' has been disbanded. " .
                                number_format($result['balanceCleared'], 2) .
                                " LKR cleared and {$result['usersRevoked']} member account(s) set to Unassigned.",
            'balanceCleared' => $result['balanceCleared'],
            'usersRevoked'   => $result['usersRevoked'],
        ]);
    }

    /* ---------------------------------------------------------------
     * GET /clubhealth/export
     * CSV download of the currently filtered club list.
     * --------------------------------------------------------------- */
    public function export(): void {
        $this->requireNYSCAdmin();

        /** @var ClubHealthModel $model */
        $model = $this->model('ClubHealthModel');
        $clubs = $model->getClubs($this->readFilters(), 2000);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="club-health-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Club Health Export — YouthNexus NYSC Administration']);
        fputcsv($out, ['Generated At', date('Y-m-d H:i:s')]);
        fputcsv($out, []);
        fputcsv($out, ['Club', 'Code', 'Zone', 'Division', 'Members', 'Overall Score',
                       'Events Score', 'Finance Score', 'Attendance Score', 'Status', 'Club Status', 'Disbanded At']);

        foreach ($clubs as $c) {
            fputcsv($out, [
                $c->club_name, $c->club_code, $c->zonal_name, $c->division_name,
                (int)$c->no_of_members, (float)($c->score['overall_score'] ?? $c->score),
                (float)($c->score['event_score'] ?? ''), (float)($c->score['finance_score'] ?? ''),
                (float)($c->score['attendance_score'] ?? ''),
                $c->health_status ?? ($c->score['health_status'] ?? ''),
                $c->club_status, $c->disbanded_at ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    /* ---------------------------------------------------------------
     * Email helper — reuses project SMTP config
     * --------------------------------------------------------------- */
    private function sendWarningEmail(string $to, string $name, string $clubName): bool {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = 'Disband Warning — ' . $clubName . ' (YouthNexus)';
            $mail->Body    = '
            <div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;background:#f4f7fb;padding:20px;">
              <div style="background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);">
                <h2 style="color:#b91c1c;margin-top:0;">Disband Warning Issued</h2>
                <p>Hello ' . htmlspecialchars($name, ENT_QUOTES) . ',</p>
                <p>Your club <strong>' . htmlspecialchars($clubName, ENT_QUOTES) . '</strong> is currently in the
                <strong style="color:#b91c1c;">Dormant (Red) health band</strong> of the national club health monitor.</p>
                <p>Improve event participation, member attendance and financial integrity immediately.
                If the club remains dormant, the NYSC administration may proceed to <strong>disband the club</strong>,
                archive its records and revoke all executive access.</p>
                <p style="color:#666;font-size:13px;">This is an automated notice from YouthNexus.</p>
              </div>
            </div>';
            $mail->AltBody = "DISBAND WARNING: {$clubName} is in the Dormant (Red) health band. Improve activity or the club may be disbanded.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
