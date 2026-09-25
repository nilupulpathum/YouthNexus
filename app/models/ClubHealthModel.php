<?php

require_once __DIR__ . '/DivisionalClubHealthModel.php';
require_once __DIR__ . '/../core/phpmailer/Exception.php';
require_once __DIR__ . '/../core/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../core/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ClubHealthModel extends DivisionalClubHealthModel {

    public function getAllProvinces(): array {
        return $this->resultSet(
            "SELECT DISTINCT province
             FROM Zone
             WHERE province IS NOT NULL AND province <> ''
             ORDER BY province ASC"
        );
    }

    public function getAllZones(): array {
        return $this->resultSet(
            "SELECT zonal_id, zonal_name, province, hub_name
             FROM Zone
             ORDER BY zonal_name ASC"
        );
    }

    public function getAllDivisions(?string $province = null): array {
        if ($province !== null && $province !== '') {
            return $this->resultSet(
                "SELECT d.division_id, d.division_name, d.zonal_id, z.zonal_name, z.province
                 FROM Division d
                 INNER JOIN Zone z ON z.zonal_id = d.zonal_id
                 WHERE z.province = ?
                 ORDER BY d.division_name ASC",
                [$province]
            );
        }

        return $this->resultSet(
            "SELECT d.division_id, d.division_name, d.zonal_id, z.zonal_name, z.province
             FROM Division d
             INNER JOIN Zone z ON z.zonal_id = d.zonal_id
             ORDER BY z.province ASC, d.division_name ASC"
        );
    }

    public function refreshNationwide(): array {
        $clubs = $this->resultSet(
            "SELECT c.club_id, c.registration_date, c.division_id
             FROM Club c
             WHERE c.status IN ('Active','Flagged')
             ORDER BY c.club_id"
        );
        $pdo = Database::getInstance()->getConnection();
        $currentScores = [];

        foreach ($clubs as $club) {
            $history = [];
            $currentMonth = new DateTimeImmutable('first day of this month');
            for ($offset = 5; $offset >= 0; $offset--) {
                $periodEnd = $offset === 0
                    ? new DateTimeImmutable('today')
                    : $currentMonth->modify("-$offset months")->modify('last day of this month');
                $scoreMonth = $periodEnd->modify('first day of this month')->format('Y-m-d');
                $score = $this->calculateScore((int) $club->club_id, $periodEnd);
                $history[] = $score;
                $this->upsertSnapshot($pdo, (int) $club->club_id, $scoreMonth, $score);
            }

            $current = $history[count($history) - 1];
            $currentScores[(int) $club->club_id] = $current;
            $pdo->prepare("UPDATE Club SET overall_health_score = ?, health_status = ? WHERE club_id = ?")
                ->execute([$current['overall_score'], $current['health_status'], $club->club_id]);

            $registeredLongEnough = strtotime((string) $club->registration_date) <= strtotime($history[0]['window_start']);
            $sixDormantMonths = $registeredLongEnough && count(array_filter($history, fn($score) => $score['overall_score'] < 30)) === 6;
            if ($sixDormantMonths) {
                $this->createAutomaticDormancyFlag($pdo, (int) $club->club_id);
            }
        }

        return $currentScores;
    }

    public function getNationwideClubs(array $scores = []): array {
        $clubs = $this->resultSet(
            "SELECT c.club_id, c.club_name, c.description, c.registration_date, c.status,
                    c.club_code, c.overall_health_score, c.health_status, c.flagged,
                    c.disband_reason, c.disbanded_at,
                    d.division_id, d.division_name,
                    z.zonal_id, z.zonal_name, z.province,
                    ca.club_logo_path,
                    COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.user_id END) AS active_members,
                    COUNT(DISTINCT CASE WHEN hf.status IN ('Open','UnderReview') THEN hf.health_flag_id END) AS open_flags,
                    COALESCE(l.current_balance, 0.00) AS ledger_balance,
                    (SELECT CONCAT_WS(' ', u2.first_name, u2.last_name)
                     FROM User u2
                     WHERE u2.club_id = c.club_id AND u2.role = 'ClubPresident' AND u2.status = 'Active'
                     LIMIT 1) AS president_name,
                    (SELECT COUNT(*)
                     FROM Event e
                     WHERE e.organizer_club_id = c.club_id AND e.status = 'Completed') AS completed_events_count,
                    ((SELECT COALESCE(SUM(s.quantity), 0)
                      FROM AssetStock s
                      WHERE s.owner_level = 'Club' AND s.owner_id = c.club_id)
                     +
                     COALESCE((SELECT SUM(ca2.quantity)
                      FROM ClubAsset ca2
                      WHERE ca2.application_id = ca.application_id), 0)
                    ) AS total_asset_count
             FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             INNER JOIN Zone z ON z.zonal_id = d.zonal_id
             LEFT JOIN ClubApplication ca ON ca.club_name COLLATE utf8mb4_unicode_ci = c.club_name COLLATE utf8mb4_unicode_ci
             LEFT JOIN User u ON u.club_id = c.club_id
             LEFT JOIN ClubHealthFlag hf ON hf.club_id = c.club_id
             LEFT JOIN Ledger l ON l.owner_type = 'Club' AND l.owner_id = c.club_id
             WHERE c.status IN ('Active','Flagged')
             GROUP BY c.club_id
             ORDER BY c.overall_health_score DESC, c.club_name ASC"
        );

        foreach ($clubs as $club) {
            $club->score = $scores[(int) $club->club_id]
                ?? $this->calculateScore((int) $club->club_id, new DateTimeImmutable('today'));
        }

        return $clubs;
    }

    public function getNationwideClubDetails(int $clubId): ?array {
        $club = $this->single(
            "SELECT c.*, d.division_name, z.zonal_name, z.zonal_id, ca.club_logo_path,
                    ca.category AS club_category, ca.city AS club_city,
                    ca.date_establishment,
                    COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.user_id END) AS active_members,
                    COALESCE(l.current_balance, 0.00) AS ledger_balance,
                    (SELECT CONCAT_WS(' ', u2.first_name, u2.last_name)
                     FROM User u2
                     WHERE u2.club_id = c.club_id AND u2.role = 'ClubPresident' AND u2.status = 'Active'
                     LIMIT 1) AS president_name,
                    (SELECT COUNT(*)
                     FROM Event e
                     WHERE e.organizer_club_id = c.club_id AND e.status = 'Completed') AS completed_events_count,
                    ((SELECT COALESCE(SUM(s.quantity), 0)
                      FROM AssetStock s
                      WHERE s.owner_level = 'Club' AND s.owner_id = c.club_id)
                     +
                     COALESCE((SELECT SUM(ca2.quantity)
                      FROM ClubAsset ca2
                      WHERE ca2.application_id = ca.application_id), 0)
                    ) AS total_asset_count
             FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             INNER JOIN Zone z ON z.zonal_id = d.zonal_id
             LEFT JOIN ClubApplication ca ON ca.club_name COLLATE utf8mb4_unicode_ci = c.club_name COLLATE utf8mb4_unicode_ci
             LEFT JOIN User u ON u.club_id = c.club_id
             LEFT JOIN Ledger l ON l.owner_type = 'Club' AND l.owner_id = c.club_id
             WHERE c.club_id = ?
             GROUP BY c.club_id",
            [$clubId]
        );

        if (!$club) return null;

        $end = new DateTimeImmutable('today');
        $score = $this->calculateScore($clubId, $end);
        $events = $this->getEventDetails($clubId, $score['window_start'], $score['window_end']);
        $finance = $this->getFinanceDetails($clubId, $score['window_start'], $score['window_end']);

        $executives = $this->resultSet(
            "SELECT user_id, first_name, last_name, role, email, phone_number,
                    profile_picture_url
             FROM User
             WHERE club_id = ? AND role IN ('ClubPresident','ClubSecretary','ClubTreasurer')
               AND status = 'Active'
             ORDER BY FIELD(role, 'ClubPresident','ClubSecretary','ClubTreasurer')",
            [$clubId]
        );

        $flags = $this->resultSet(
            "SELECT hf.*, CONCAT_WS(' ', raiser.first_name, raiser.last_name) AS raised_by_name,
                    raiser.role AS raised_by_role
             FROM ClubHealthFlag hf
             LEFT JOIN User raiser ON raiser.user_id = hf.raised_by
             WHERE hf.club_id = ?
             ORDER BY hf.raised_at DESC",
            [$clubId]
        );

        $history = $this->resultSet(
            "SELECT score_month, event_score, finance_score, attendance_score, overall_score, health_status
             FROM ClubHealthSnapshot
             WHERE club_id = ?
             ORDER BY score_month DESC
             LIMIT 6",
            [$clubId]
        );

        return [
            'club' => $club,
            'score' => $score,
            'executives' => $executives,
            'events' => $events,
            'finance' => $finance,
            'flags' => $flags,
            'history' => $history,
        ];
    }

    protected function getFinanceDetails(int $clubId, string $start, string $end): array {
        $ledger = $this->single("SELECT ledger_id, current_balance, status FROM Ledger WHERE owner_type = 'Club' AND owner_id = ? LIMIT 1", [$clubId]);
        if (!$ledger) return ['ledger' => null, 'totals' => null, 'entries' => [], 'audits' => [], 'red_flags' => []];
        $totals = $this->single(
            "SELECT COALESCE(SUM(CASE WHEN type = 'Income' AND status = 'Approved' THEN amount ELSE 0 END), 0) AS income,
                    COALESCE(SUM(CASE WHEN type = 'Expense' AND status = 'Approved' THEN amount ELSE 0 END), 0) AS expenses,
                    COUNT(CASE WHEN status = 'Approved' THEN 1 END) AS approved_entries,
                    COUNT(CASE WHEN status = 'Approved' AND type = 'Expense' AND attachment_url IS NOT NULL AND attachment_url <> '' THEN 1 END) AS receipted_expenses,
                    COUNT(CASE WHEN status = 'Approved' AND reconciled = 1 THEN 1 END) AS reconciled_entries
             FROM LedgerEntry WHERE ledger_id = ? AND date BETWEEN ? AND ?",
            [$ledger->ledger_id, $start, $end]
        );
        $entries = $this->resultSet(
            "SELECT entry_id, date, type, category, description, amount, status, attachment_url, reconciled
             FROM LedgerEntry WHERE ledger_id = ? AND date BETWEEN ? AND ?
             ORDER BY date DESC, entry_id DESC LIMIT 10",
            [$ledger->ledger_id, $start, $end]
        );
        $audits = $this->resultSet(
            "SELECT audit_id, financial_year, audit_status, math_check_status,
                    expected_closing_balance, actual_closing_balance
             FROM Audit WHERE (scope_level = 'Club' AND scope_id = ?) OR club_id = ?
             ORDER BY audit_id DESC LIMIT 6",
            [$clubId, $clubId]
        );
        $redFlags = $this->resultSet(
            "SELECT rf.red_flag_id, rf.flag_type, rf.description, rf.status, rf.flagged_at
             FROM RedFlag rf INNER JOIN Audit a ON a.audit_id = rf.audit_id
             WHERE (a.scope_level = 'Club' AND a.scope_id = ?) OR a.club_id = ?
             ORDER BY rf.flagged_at DESC LIMIT 10",
            [$clubId, $clubId]
        );
        return ['ledger' => $ledger, 'totals' => $totals, 'entries' => $entries, 'audits' => $audits, 'red_flags' => $redFlags];
    }

    public function retrieveRemainingFunds(int $clubId, int $adminUserId): float {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $stmtClub = $pdo->prepare(
                "SELECT c.club_id, c.club_name, c.club_code, l.ledger_id, l.current_balance
                 FROM Club c
                 INNER JOIN Ledger l ON l.owner_type = 'Club' AND l.owner_id = c.club_id
                 WHERE c.club_id = ?
                 FOR UPDATE"
            );
            $stmtClub->execute([$clubId]);
            $club = $stmtClub->fetch(PDO::FETCH_OBJ);

            if (!$club) {
                throw new RuntimeException("Club ledger record was not found.");
            }

            $amount = (float) $club->current_balance;
            if ($amount <= 0) {
                throw new RuntimeException("Club fund balance is already 0.00. No funds to retrieve.");
            }

            $stmtNat = $pdo->query("SELECT ledger_id, current_balance FROM Ledger WHERE owner_level = 'National' LIMIT 1 FOR UPDATE");
            $natLedger = $stmtNat->fetch(PDO::FETCH_OBJ);

            if (!$natLedger) {
                $pdo->exec(
                    "INSERT INTO Ledger (owner_type, owner_level, owner_id, current_balance, status, created_at)
                     VALUES ('Zone', 'National', 0, 100000000.00, 'Active', NOW())"
                );
                $natLedgerId = (int) $pdo->lastInsertId();
            } else {
                $natLedgerId = (int) $natLedger->ledger_id;
            }

            $today = date('Y-m-d');

            // 1. Club Outflow (Expense entry in Club Ledger)
            $stmtClubEntry = $pdo->prepare(
                "INSERT INTO LedgerEntry (ledger_id, amount, type, category, description, status, reconciled, date, created_by, created_at)
                 VALUES (?, ?, 'Expense', 'Fund Retrieval / Sweep', ?, 'Approved', 1, ?, ?, NOW())"
            );
            $stmtClubEntry->execute([
                $club->ledger_id,
                $amount,
                "Fund retrieval to NYSC National Ledger upon club health review (Dormancy / Pre-Disband)",
                $today,
                $adminUserId,
            ]);

            // Set Club Ledger Balance -> 0
            $pdo->prepare("UPDATE Ledger SET current_balance = 0.00 WHERE ledger_id = ?")
                ->execute([$club->ledger_id]);

            // 2. National Inflow (Income entry in NYSC National Ledger)
            $stmtNatEntry = $pdo->prepare(
                "INSERT INTO LedgerEntry (ledger_id, amount, type, category, description, status, reconciled, date, created_by, created_at)
                 VALUES (?, ?, 'Income', 'Fund Allocation In', ?, 'Approved', 1, ?, ?, NOW())"
            );
            $stmtNatEntry->execute([
                $natLedgerId,
                $amount,
                "Fund retrieval from {$club->club_name} ({$club->club_code}) — Dormancy retrieval to National Treasury",
                $today,
                $adminUserId,
            ]);

            // Increment National Ledger Balance
            $pdo->prepare("UPDATE Ledger SET current_balance = current_balance + ? WHERE ledger_id = ?")
                ->execute([$amount, $natLedgerId]);

            // Audit log
            $pdo->prepare(
                "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
                 VALUES (?, 'ClubFundsRetrieved', 'Club', ?, ?)"
            )->execute([
                $adminUserId,
                $clubId,
                "Retrieved remaining balance of LKR " . number_format($amount, 2) . " from {$club->club_name} into NYSC National Ledger.",
            ]);

            $pdo->commit();
            return $amount;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function issueDisbandWarning(int $clubId, int $adminUserId, string $subject, string $message): array {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $stmtClub = $pdo->prepare(
                "SELECT c.club_id, c.club_name, c.club_code, d.division_name, z.zonal_name
                 FROM Club c
                 INNER JOIN Division d ON d.division_id = c.division_id
                 INNER JOIN Zone z ON z.zonal_id = d.zonal_id
                 WHERE c.club_id = ? AND c.status IN ('Active','Flagged')"
            );
            $stmtClub->execute([$clubId]);
            $club = $stmtClub->fetch(PDO::FETCH_OBJ);

            if (!$club) {
                throw new RuntimeException("Club not found or not in active/flagged status.");
            }

            $subject = trim($subject) ?: 'Urgent: NYSC Disband Warning Notice';
            $message = trim($message);
            if (mb_strlen($message) < 10) {
                throw new InvalidArgumentException("Warning notification message must be at least 10 characters.");
            }

            // Flag the club
            $pdo->prepare("UPDATE Club SET flagged = 1 WHERE club_id = ?")->execute([$clubId]);

            // Record flag
            $pdo->prepare(
                "INSERT INTO ClubHealthFlag (club_id, flag_category, source, reason, status, raised_by)
                 VALUES (?, 'GovernanceConcern', 'Manual', ?, 'Open', ?)"
            )->execute([$clubId, "Disband Warning: {$subject} — {$message}", $adminUserId]);

            // Get President and Secretary
            $stmtExecs = $pdo->prepare(
                "SELECT user_id, first_name, last_name, email, role
                 FROM User
                 WHERE club_id = ? AND role IN ('ClubPresident','ClubSecretary') AND status = 'Active'"
            );
            $stmtExecs->execute([$clubId]);
            $executives = $stmtExecs->fetchAll(PDO::FETCH_OBJ);

            $notifiedCount = 0;
            $notifyStmt = $pdo->prepare(
                "INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
                 VALUES (?, 'UrgentDisbandWarning', ?, 'Club', ?, 0, NOW())"
            );

            foreach ($executives as $exec) {
                $urgentMsg = "URGENT DISBAND WARNING [{$club->club_name}]: {$subject}. {$message}";
                $notifyStmt->execute([$exec->user_id, mb_substr($urgentMsg, 0, 490), $clubId]);
                $notifiedCount++;

                // Send email
                $this->sendWarningEmail($exec->email, "{$exec->first_name} {$exec->last_name}", $club->club_name, $subject, $message);
            }

            // AuditLog
            $pdo->prepare(
                "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
                 VALUES (?, 'DisbandWarningIssued', 'Club', ?, ?)"
            )->execute([
                $adminUserId,
                $clubId,
                "Issued official disband warning to {$notifiedCount} executives of {$club->club_name}.",
            ]);

            $pdo->commit();
            return [
                'club' => $club,
                'notified_count' => $notifiedCount,
                'executives' => $executives,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function executeDisband(int $clubId, int $adminUserId, string $reason): void {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $stmtClub = $pdo->prepare(
                "SELECT c.club_id, c.club_name, c.club_code, c.status, l.current_balance, l.ledger_id
                 FROM Club c
                 LEFT JOIN Ledger l ON l.owner_type = 'Club' AND l.owner_id = c.club_id
                 WHERE c.club_id = ?
                 FOR UPDATE"
            );
            $stmtClub->execute([$clubId]);
            $club = $stmtClub->fetch(PDO::FETCH_OBJ);

            if (!$club) {
                throw new RuntimeException("The selected club does not exist.");
            }

            if ($club->status === 'Disbanded') {
                throw new RuntimeException("This club has already been disbanded.");
            }

            // Must have zero fund balance before disbanding
            $balance = (float) ($club->current_balance ?? 0);
            if ($balance > 0) {
                throw new RuntimeException("Cannot disband club with active funds (LKR " . number_format($balance, 2) . "). Please retrieve remaining funds first.");
            }

            $reason = trim($reason);
            if (mb_strlen($reason) < 10) {
                throw new InvalidArgumentException("Please provide a valid disbandment justification (at least 10 characters).");
            }

            // 1. Mark Club as Disbanded and remove from active filters
            $pdo->prepare(
                "UPDATE Club
                 SET status = 'Disbanded',
                     flagged = 0,
                     disband_reason = ?,
                     disbanded_at = NOW(),
                     disbanded_by = ?
                 WHERE club_id = ?"
            )->execute([$reason, $adminUserId, $clubId]);

            // 2. Revoke club executives - change role to 'UnassignedUser' and club_id = NULL
            $pdo->prepare(
                "UPDATE User
                 SET role = 'UnassignedUser',
                     club_id = NULL
                 WHERE club_id = ? AND role IN ('ClubPresident','ClubSecretary','ClubTreasurer','ClubMember')"
            )->execute([$clubId]);

            // 3. Close the club ledger
            if ($club->ledger_id) {
                $pdo->prepare("UPDATE Ledger SET status = 'Closed' WHERE ledger_id = ?")
                    ->execute([$club->ledger_id]);
            }

            // 4. Resolve open health flags
            $pdo->prepare(
                "UPDATE ClubHealthFlag
                 SET status = 'Resolved',
                     resolved_by = ?,
                     resolved_at = NOW(),
                     resolution_notes = ?
                 WHERE club_id = ? AND status IN ('Open','UnderReview')"
            )->execute([$adminUserId, "Resolved upon disbandment: {$reason}", $clubId]);

            // 5. Audit log
            $pdo->prepare(
                "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
                 VALUES (?, 'ClubDisbanded', 'Club', ?, ?)"
            )->execute([
                $adminUserId,
                $clubId,
                "Club {$club->club_name} ({$club->club_code}) was disbanded by NYSC Admin. Reason: {$reason}",
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function sendWarningEmail(string $recipientEmail, string $recipientName, string $clubName, string $subject, string $message): void {
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($recipientEmail, $recipientName);
            $mail->isHTML(true);

            $mail->Subject = "NYSC Official Warning: {$subject} — {$clubName}";
            $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
            $safeClub = htmlspecialchars($clubName, ENT_QUOTES, 'UTF-8');
            $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
            $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8fafc; padding: 24px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                    <div style='background: #b91c1c; color: #fff; padding: 16px 20px; border-radius: 6px 6px 0 0;'>
                        <h2 style='margin: 0; font-size: 18px;'>National Youth Services Council (NYSC)</h2>
                        <p style='margin: 4px 0 0; font-size: 13px; opacity: 0.9;'>OFFICIAL EXECUTIVE DISBAND WARNING</p>
                    </div>
                    <div style='background: #ffffff; padding: 24px; border-radius: 0 0 6px 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);'>
                        <p style='color: #334155; font-size: 14px;'>Dear {$safeName},</p>
                        <p style='color: #334155; font-size: 14px;'>This is a formal communication from NYSC Administration regarding <strong>{$safeClub}</strong>.</p>
                        <div style='background: #fef2f2; border-left: 4px solid #ef4444; padding: 14px; margin: 16px 0;'>
                            <strong style='color: #991b1b; display: block; margin-bottom: 6px;'>Notice: {$safeSubject}</strong>
                            <p style='color: #7f1d1d; margin: 0; font-size: 13px; line-height: 1.5;'>{$safeMessage}</p>
                        </div>
                        <p style='color: #475569; font-size: 13px;'>Failure to address this health review or provide the required corrective actions may lead to the revocation of club credentials and execution of permanent disbandment procedures.</p>
                        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                        <p style='color: #94a3b8; font-size: 11px; margin: 0;'>National Youth Services Council • YouthNexus Administrative Portal</p>
                    </div>
                </div>";

            $mail->AltBody = "NYSC Official Warning to {$clubName}\n\nNotice: {$subject}\n\n{$message}\n\nPlease sign in to your YouthNexus dashboard to take immediate corrective action.";
            $mail->send();
        } catch (Throwable $e) {
            // Swallow SMTP failure in local/offline environments to avoid halting business logic
        }
    }
}
