<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ClubRegistration extends Controller {

    private function requireCoordinator() {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'DivisionalCoordinator') {
            $this->redirect('auth/signin');
        }
    }

    // ---------------------------------------------------------------
    // LIST
    // ---------------------------------------------------------------
    public function index() {
        $this->requireCoordinator();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $applicationModel = $this->model('ClubApplicationModel');
        $divisionId        = $_SESSION['division_id'] ?? null;

        $applications = $divisionId ? $applicationModel->findPendingByDivision($divisionId) : [];
        $counts       = $divisionId ? $applicationModel->countsByDivision($divisionId) : ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];

        foreach ($applications as $app) {
            $missing = $applicationModel->missingDocuments($app);
            $app->documents_complete = empty($missing);
            $app->missing_summary    = empty($missing) ? 'Complete' : ('Missing ' . $missing[0]);
        }

        $this->view('clubregistration/index', [
            'title'        => 'Approve Club Registration — YouthNexus Pulse',
            'applications' => $applications,
            'counts'       => $counts,
            'csrf_token'   => $_SESSION['csrf_token'],
        ]);
    }

    // ---------------------------------------------------------------
    // REVIEW — full application detail as JSON, fetched by the modal
    // ---------------------------------------------------------------
    public function review($id = null) {
        $this->requireCoordinator();

        $applicationModel = $this->model('ClubApplicationModel');
        $nomineeModel      = $this->model('ExecutiveNomineeModel');
        $assetModel         = $this->model('ClubAssetModel');
        $photoModel           = $this->model('ClubApplicationPhotoModel');
        $auditModel             = $this->model('AuditLogModel');

        $application = $id ? $applicationModel->findById((int)$id) : false;

        header('Content-Type: application/json');
        if (!$application) {
            http_response_code(404);
            echo json_encode(['error' => 'Application not found.']);
            exit();
        }

        echo json_encode([
            'application' => $application,
            'nominees'    => $nomineeModel->findByApplication($application->application_id),
            'assets'      => $assetModel->findByApplication($application->application_id),
            'photos'      => $photoModel->findByApplication($application->application_id),
            'history'     => $auditModel->findForTarget('ClubApplication', $application->application_id),
        ]);
        exit();
    }

    // ---------------------------------------------------------------
    // APPROVE — creates the Club, promotes nominees to User accounts,
    // emails credentials (or a role-assigned notice for linked accounts).
    // ---------------------------------------------------------------
    public function approve($id = null) {
        $this->requireCoordinator();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('clubregistration/index');
        }
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->jsonError('Invalid request. Please refresh the page.');
        }

        $applicationModel = $this->model('ClubApplicationModel');
        $clubModel          = $this->model('ClubModel');
        $nomineeModel         = $this->model('ExecutiveNomineeModel');
        $auditModel             = $this->model('AuditLogModel');

        $application = $applicationModel->findById((int)$id);
        if (!$application || $application->status !== 'Pending') {
            $this->jsonError('Application not found or already processed.');
        }

        // 1. Create the real Club row.
        $divisionName = $clubModel->getDivisionName($application->proposed_division_id);
        $clubCode     = $clubModel->generateClubCode($application->proposed_division_id, $divisionName);
        $clubId       = $clubModel->createFromApplication($application, $clubCode);

        // 2. Promote each nominee — new account, or link an existing one by NIC.
        $roleMap = ['President' => 'ClubPresident', 'Secretary' => 'ClubSecretary', 'Treasurer' => 'ClubTreasurer'];
        $nominees = $nomineeModel->findByApplication($application->application_id);

        foreach ($nominees as $nominee) {
            $role = $roleMap[$nominee->role_type] ?? 'ClubMember';

            try {
                $result = $nomineeModel->promoteToUser($nominee, $clubId, $application->proposed_division_id, $role);
            } catch (PDOException $e) {
                // e.g. duplicate email collision that NIC-check didn't catch — skip this nominee, keep going.
                continue;
            }

            $indexNumber = $nomineeModel->generateIndexNumber();
            $nomineeModel->setIndexNumber($nominee->nominee_id, $indexNumber);

            if ($result['linked']) {
                $this->sendRoleAssignedEmail($nominee->email, $nominee->name, $result['username'], $nominee->role_type, $application->club_name);
            } else {
                $this->sendCredentialsEmail($nominee->email, $nominee->name, $result['username'], $result['password'], $nominee->role_type, $application->club_name);
            }
        }

        // 3. Mark the application Approved and log it.
        $applicationModel->markApproved($application->application_id, $_SESSION['user_id']);
        $auditModel->log($_SESSION['user_id'], 'APPROVE_CLUB', 'ClubApplication', $application->application_id, "Approved '{$application->club_name}', club_code={$clubCode}");

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'club_code' => $clubCode]);
        exit();
    }

    // ---------------------------------------------------------------
    // REJECT — requires remarks, emails the proposer, logs the decision.
    // ---------------------------------------------------------------
    public function reject($id = null) {
        $this->requireCoordinator();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('clubregistration/index');
        }
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->jsonError('Invalid request. Please refresh the page.');
        }

        $remarks = trim($_POST['remarks'] ?? '');
        if (empty($remarks)) {
            $this->jsonError('Please provide a reason for rejecting this application.');
        }

        $applicationModel = $this->model('ClubApplicationModel');
        $auditModel         = $this->model('AuditLogModel');

        $application = $applicationModel->findById((int)$id);
        if (!$application || $application->status !== 'Pending') {
            $this->jsonError('Application not found or already processed.');
        }

        $applicationModel->markRejected($application->application_id, $_SESSION['user_id'], $remarks);
        $auditModel->log($_SESSION['user_id'], 'REJECT_CLUB', 'ClubApplication', $application->application_id, $remarks);

        $this->sendRejectionEmail($application->proposer_email, $application->proposer_name, $application->club_name, $remarks);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    // ---------------------------------------------------------------
    // PRIVATE HELPERS
    // ---------------------------------------------------------------

    private function jsonError($message) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => $message]);
        exit();
    }

    /** Duplicated from Auth.php's pattern rather than reused — that method is private to Auth. */
    private function configureMailer($recipientEmail) {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        return $mail;
    }

    private function sendCredentialsEmail($email, $name, $username, $tempPassword, $roleType, $clubName) {
        try {
            $mail          = $this->configureMailer($email);
            $mail->Subject = 'Your YouthNexus Account — ' . $clubName;
            $mail->Body    = "<div style='font-family:Arial,sans-serif;max-width:500px;margin:0 auto;background:#f4f7fb;padding:20px;'>
                <div style='background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);'>
                <h2 style='color:#002d72;margin-top:0;'>YouthNexus Pulse</h2>
                <p>Hello " . htmlspecialchars($name, ENT_QUOTES) . ",</p>
                <p><strong>" . htmlspecialchars($clubName, ENT_QUOTES) . "</strong> has been approved, and you've been registered as <strong>" . htmlspecialchars($roleType, ENT_QUOTES) . "</strong>. Your login:</p>
                <p><strong>Username:</strong> " . htmlspecialchars($username, ENT_QUOTES) . "<br><strong>Temporary Password:</strong> " . htmlspecialchars($tempPassword, ENT_QUOTES) . "</p>
                <p style='color:#666;font-size:13px;'>Please sign in and change your password as soon as possible.</p>
                </div></div>";
            $mail->AltBody = "Username: $username\nTemporary Password: $tempPassword";
            $mail->send();
        } catch (Exception $e) { /* swallow — approval already succeeded, don't fail the whole flow */ }
    }

    private function sendRoleAssignedEmail($email, $name, $username, $roleType, $clubName) {
        try {
            $mail          = $this->configureMailer($email);
            $mail->Subject = 'New Role Assigned — ' . $clubName;
            $mail->Body    = "<div style='font-family:Arial,sans-serif;max-width:500px;margin:0 auto;background:#f4f7fb;padding:20px;'>
                <div style='background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);'>
                <h2 style='color:#002d72;margin-top:0;'>YouthNexus Pulse</h2>
                <p>Hello " . htmlspecialchars($name, ENT_QUOTES) . ",</p>
                <p><strong>" . htmlspecialchars($clubName, ENT_QUOTES) . "</strong> has been approved, and your existing account (username: <strong>" . htmlspecialchars($username, ENT_QUOTES) . "</strong>) has been assigned the role of <strong>" . htmlspecialchars($roleType, ENT_QUOTES) . "</strong>. Sign in with your existing password.</p>
                </div></div>";
            $mail->AltBody = "$clubName approved. Your existing account ($username) is now $roleType.";
            $mail->send();
        } catch (Exception $e) { /* swallow */ }
    }

    private function sendRejectionEmail($email, $proposerName, $clubName, $remarks) {
        try {
            $mail          = $this->configureMailer($email);
            $mail->Subject = 'Update on Your Club Registration — ' . $clubName;
            $mail->Body    = "<div style='font-family:Arial,sans-serif;max-width:500px;margin:0 auto;background:#f4f7fb;padding:20px;'>
                <div style='background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);'>
                <h2 style='color:#002d72;margin-top:0;'>YouthNexus Pulse</h2>
                <p>Hello " . htmlspecialchars($proposerName, ENT_QUOTES) . ",</p>
                <p>Thank you for submitting a registration application for <strong>" . htmlspecialchars($clubName, ENT_QUOTES) . "</strong>. After review, the Divisional Coordinator was unable to approve it at this time.</p>
                <p><strong>Reason provided:</strong></p>
                <p style='background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px;color:#b91c1c;'>" . nl2br(htmlspecialchars($remarks, ENT_QUOTES)) . "</p>
                <p style='color:#666;font-size:13px;'>You're welcome to address the points above and resubmit.</p>
                </div></div>";
            $mail->AltBody = "Your application for $clubName was not approved.\nReason: $remarks";
            $mail->send();
        } catch (Exception $e) { /* swallow */ }
    }
}
