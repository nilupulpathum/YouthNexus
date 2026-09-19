<?php
/**
 * Database Migration Script
 *
 * Run this once to add the new tables required for the National Dashboard.
 * Usage: php database/migrate.php
 */

require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    echo "Connected to database.\n";

    // ============================================
    // 1. LEDGER
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS Ledger (
        ledger_id       INT AUTO_INCREMENT PRIMARY KEY,
        owner_type      ENUM('Club','Division','Zone') NOT NULL,
        owner_id        INT NOT NULL,
        current_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
        status          ENUM('Active','Closed') NOT NULL DEFAULT 'Active',
        created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `Ledger` ready.\n";

    // ============================================
    // 2. LEDGER ENTRY
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS LedgerEntry (
        entry_id    INT AUTO_INCREMENT PRIMARY KEY,
        ledger_id   INT NOT NULL,
        amount      DECIMAL(15,2) NOT NULL,
        type        ENUM('Income','Expense') NOT NULL,
        status      ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        date        DATE NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (ledger_id) REFERENCES Ledger(ledger_id),
        INDEX idx_ledger (ledger_id),
        INDEX idx_date (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `LedgerEntry` ready.\n";

    // ============================================
    // 3. CLUB HEALTH SCORE
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS ClubHealthScore (
        health_score_id   INT AUTO_INCREMENT PRIMARY KEY,
        club_id           INT NOT NULL,
        event_score       DECIMAL(5,2) DEFAULT 0,
        attendance_score  DECIMAL(5,2) DEFAULT 0,
        financial_score   DECIMAL(5,2) DEFAULT 0,
        total_score       DECIMAL(5,2) DEFAULT 0,
        status            ENUM('Green','Yellow','Red') DEFAULT 'Green',
        calculated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (club_id) REFERENCES Club(club_id),
        INDEX idx_club (club_id),
        INDEX idx_calc (calculated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `ClubHealthScore` ready.\n";

    // ============================================
    // 4. VOLUNTEER HISTORY
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS VolunteerHistory (
        log_id      INT AUTO_INCREMENT PRIMARY KEY,
        member_id   INT NOT NULL,
        date        DATE NOT NULL,
        hours       INT NOT NULL DEFAULT 0,
        status      ENUM('Pending','Verified','Fail') NOT NULL DEFAULT 'Pending',
        description VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (member_id) REFERENCES User(user_id),
        INDEX idx_member (member_id),
        INDEX idx_date (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `VolunteerHistory` ready.\n";

    // ============================================
    // 5. RED FLAG
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS RedFlag (
        red_flag_id   INT AUTO_INCREMENT PRIMARY KEY,
        entry_id      INT NULL,
        audit_id      INT NULL,
        reason        VARCHAR(255) NOT NULL,
        status        ENUM('Unresolved','Resolved') NOT NULL DEFAULT 'Unresolved',
        flagged_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        resolved_at   TIMESTAMP NULL,
        FOREIGN KEY (entry_id) REFERENCES LedgerEntry(entry_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `RedFlag` ready.\n";

    // ============================================
    // 6. AUDIT
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS Audit (
        audit_id       INT AUTO_INCREMENT PRIMARY KEY,
        club_id        INT NOT NULL,
        financial_year INT NOT NULL,
        audit_status   ENUM('InProgress','Completed','Overdue') NOT NULL DEFAULT 'InProgress',
        sign_off_date  DATE NULL,
        locked         BOOLEAN NOT NULL DEFAULT FALSE,
        due_date       DATE NOT NULL,
        FOREIGN KEY (club_id) REFERENCES Club(club_id),
        INDEX idx_status (audit_status),
        INDEX idx_due (due_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `Audit` ready.\n";

    // ============================================
    // 7. REPORT
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS Report (
        report_id     INT AUTO_INCREMENT PRIMARY KEY,
        type          VARCHAR(50) NOT NULL,
        scope_level   ENUM('Division','Zone','National') NOT NULL,
        scope_id      INT NOT NULL,
        generated_by  INT NULL,
        generated_at  TIMESTAMP NULL,
        status        ENUM('Submitted','Missing','Late') NOT NULL DEFAULT 'Missing',
        period        VARCHAR(20) NOT NULL,
        FOREIGN KEY (generated_by) REFERENCES User(user_id),
        INDEX idx_scope (scope_level, scope_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `Report` ready.\n";

    // ============================================
    // 8. BANK ACCOUNT
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS BankAccount (
        bank_account_id INT AUTO_INCREMENT PRIMARY KEY,
        bank_name       VARCHAR(100) NOT NULL,
        branch_name     VARCHAR(100) NOT NULL,
        account_number  VARCHAR(50)  NOT NULL UNIQUE,
        account_name    VARCHAR(200) NOT NULL,
        is_core_account BOOLEAN NOT NULL DEFAULT FALSE,
        status          ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
        created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `BankAccount` ready.\n";

    // ============================================
    // 9. FUND TRANSFER
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS FundTransfer (
        transfer_id        INT AUTO_INCREMENT PRIMARY KEY,
        reference_number   VARCHAR(50)  NOT NULL UNIQUE,
        source_account_id  INT          NOT NULL,
        target_zonal_id    INT          NOT NULL,
        amount             DECIMAL(15,2) NOT NULL,
        transfer_date      DATE         NOT NULL,
        disbursement_method ENUM('BOC_RTGS','Cheque_SLIPS') NOT NULL DEFAULT 'BOC_RTGS',
        purpose            TEXT         DEFAULT NULL,
        status             ENUM('Processing','Completed','Failed','Cancelled') NOT NULL DEFAULT 'Processing',
        authorized_by      INT          NOT NULL,
        created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completed_at       TIMESTAMP    NULL,
        FOREIGN KEY (source_account_id) REFERENCES BankAccount(bank_account_id),
        FOREIGN KEY (target_zonal_id)   REFERENCES Zone(zonal_id),
        FOREIGN KEY (authorized_by)     REFERENCES User(user_id),
        INDEX idx_status   (status),
        INDEX idx_date     (transfer_date),
        INDEX idx_zone     (target_zonal_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `FundTransfer` ready.\n";

    // ============================================
    // 10. ASSET TRANSFER
    // ============================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS AssetTransfer (
        transfer_id     INT AUTO_INCREMENT PRIMARY KEY,
        asset_id        INT NOT NULL,
        from_owner_level VARCHAR(50) NOT NULL,
        from_owner_id   INT NOT NULL,
        to_owner_level  VARCHAR(50) NOT NULL,
        to_owner_id     INT NOT NULL,
        transfer_date   DATE NOT NULL,
        status          ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        approved_by     INT NULL,
        approved_at     TIMESTAMP NULL,
        FOREIGN KEY (asset_id) REFERENCES ClubAsset(asset_id),
        FOREIGN KEY (approved_by) REFERENCES User(user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `AssetTransfer` ready.\n";

    // ============================================
    // SEED DATA — only insert if table is empty
    // ============================================

    // Zones (insert missing)
    $zoneCount = $pdo->query("SELECT COUNT(*) FROM Zone")->fetchColumn();
    if ($zoneCount == 0) {
        $pdo->exec("INSERT INTO Zone (zonal_id, zonal_name) VALUES
            (1, 'Western Zone'), (2, 'Central Zone'), (3, 'Southern Zone'),
            (4, 'Eastern Zone'), (5, 'Northern Zone')");
        echo "Seeded Zones.\n";
    } else {
        echo "Zones already populated, skipping.\n";
    }

    // Divisions (insert missing)
    $divCount = $pdo->query("SELECT COUNT(*) FROM Division")->fetchColumn();
    if ($divCount < 15) {
        $pdo->exec("INSERT IGNORE INTO Division (division_id, division_name, zonal_id) VALUES
            (1,'Colombo Division',1),(2,'Gampaha Division',1),(3,'Kalutara Division',1),
            (4,'Kandy Division',2),(5,'Galle Division',3),(6,'Matara Division',3),
            (7,'Hambantota Division',3),(8,'Trincomalee Division',4),(9,'Batticaloa Division',4),
            (10,'Ampara Division',4),(11,'Jaffna Division',5),(12,'Mannar Division',5),
            (13,'Vavuniya Division',5),(14,'Matale Division',2),(15,'Nuwara Eliya Division',2)");
        echo "Seeded additional Divisions.\n";
    }

    // Admin user (if missing)
    $adminExists = $pdo->query("SELECT COUNT(*) FROM User WHERE role = 'NYSCAdministrator'")->fetchColumn();
    if ($adminExists == 0) {
        $pdo->prepare("INSERT INTO User (username, email, password_hash, first_name, last_name, role, status)
            VALUES ('admin', 'admin@youthnexus.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'National', 'Admin', 'NYSCAdministrator', 'Active')")->execute();
        echo "Created admin user.\n";
    }

    // Seed Ledger (if empty) — uses existing club/division/zone IDs
    $ledgerCount = $pdo->query("SELECT COUNT(*) FROM Ledger")->fetchColumn();
    if ($ledgerCount == 0) {
        $clubIds = $pdo->query("SELECT club_id FROM Club WHERE status = 'Active' ORDER BY club_id")->fetchAll(PDO::FETCH_COLUMN);
        $divIds = $pdo->query("SELECT division_id FROM Division ORDER BY division_id")->fetchAll(PDO::FETCH_COLUMN);
        $zoneIds = $pdo->query("SELECT zonal_id FROM Zone ORDER BY zonal_id")->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("INSERT INTO Ledger (owner_type, owner_id, current_balance, status) VALUES (?,?,?,'Active')");
        $bal = 500000.00;
        foreach ($clubIds as $cid) {
            $stmt->execute(['Club', $cid, $bal]);
            $bal += 100000;
        }
        foreach ($divIds as $did) {
            $stmt->execute(['Division', $did, $bal]);
            $bal += 150000;
        }
        foreach ($zoneIds as $zid) {
            $stmt->execute(['Zone', $zid, $bal]);
            $bal += 200000;
        }
        echo "Seeded Ledger.\n";
    }

    // Seed LedgerEntry (if empty) — references existing ledgers
    $entryCount = $pdo->query("SELECT COUNT(*) FROM LedgerEntry")->fetchColumn();
    if ($entryCount == 0) {
        $ledgerIds = $pdo->query("SELECT ledger_id FROM Ledger WHERE status = 'Active' ORDER BY ledger_id LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        if (count($ledgerIds) >= 2) {
            $stmt = $pdo->prepare("INSERT INTO LedgerEntry (ledger_id, amount, type, status, date, description) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$ledgerIds[0], 50000.00, 'Income', 'Approved', '2026-08-01', 'Membership fees']);
            $stmt->execute([$ledgerIds[0], 25000.00, 'Expense', 'Approved', '2026-08-05', 'Event supplies']);
            $stmt->execute([$ledgerIds[1], 75000.00, 'Income', 'Approved', '2026-08-02', 'Donation received']);
            echo "Seeded LedgerEntry.\n";
        } else {
            echo "Not enough ledgers to seed LedgerEntry.\n";
        }
    }

    // Seed VolunteerHistory (if empty) — uses existing club member user_ids
    $vhCount = $pdo->query("SELECT COUNT(*) FROM VolunteerHistory")->fetchColumn();
    if ($vhCount == 0) {
        $members = $pdo->query("SELECT user_id FROM User WHERE role IN ('ClubMember','ClubPresident','ClubSecretary','ClubTreasurer') AND status = 'Active' LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        if (count($members) >= 2) {
            $stmt = $pdo->prepare("INSERT INTO VolunteerHistory (member_id, date, hours, status, description) VALUES (?,?,?,?,?)");
            $seedData = [
                [$members[0], '2026-01-15', 8, 'Verified', 'Beach cleanup'],
                [$members[0], '2026-02-20', 6, 'Verified', 'Tree planting'],
                [$members[1], '2026-03-10', 10, 'Verified', 'Blood donation camp'],
                [$members[1], '2026-04-05', 5, 'Verified', 'Community kitchen'],
                [$members[0], '2026-05-12', 7, 'Verified', 'School renovation'],
                [$members[1], '2026-06-18', 9, 'Verified', 'Elder care visit'],
            ];
            foreach ($seedData as $row) {
                $stmt->execute($row);
            }
            echo "Seeded VolunteerHistory.\n";
        } else {
            echo "Not enough club members to seed VolunteerHistory.\n";
        }
    }

    // Seed Audit (if empty) — uses existing club IDs
    $auditCount = $pdo->query("SELECT COUNT(*) FROM Audit")->fetchColumn();
    if ($auditCount == 0) {
        $clubIds = $pdo->query("SELECT club_id FROM Club WHERE status = 'Active' ORDER BY club_id")->fetchAll(PDO::FETCH_COLUMN);
        if (count($clubIds) >= 1) {
            $stmt = $pdo->prepare("INSERT INTO Audit (club_id, financial_year, audit_status, sign_off_date, locked, due_date) VALUES (?,?,?,?,?,?)");
            $statuses = ['Completed','Completed','Overdue','Completed','Completed'];
            foreach ($clubIds as $i => $cid) {
                $status = $statuses[$i % count($statuses)];
                $signOff = $status === 'Completed' ? '2026-07-15' : null;
                $locked = $status === 'Completed' ? 1 : 0;
                $due = '2026-07-31';
                $stmt->execute([$cid, 2026, $status, $signOff, $locked, $due]);
            }
            echo "Seeded Audit.\n";
        } else {
            echo "No active clubs to seed Audit.\n";
        }
    }

    // Seed RedFlag (if empty) — only reference entries/audits that exist
    $rfCount = $pdo->query("SELECT COUNT(*) FROM RedFlag")->fetchColumn();
    if ($rfCount == 0) {
        $entryIds = $pdo->query("SELECT entry_id FROM LedgerEntry LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        $auditIds = $pdo->query("SELECT audit_id FROM Audit LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("INSERT INTO RedFlag (entry_id, audit_id, reason, status, flagged_at) VALUES (?,?,?,?,?)");
        $seedData = [];
        if (count($entryIds) >= 1) {
            $seedData[] = [$entryIds[0], null, 'Unusual expense amount flagged by system', 'Unresolved', '2026-08-20 10:00:00'];
        }
        if (count($auditIds) >= 1) {
            $seedData[] = [null, $auditIds[0], 'Audit overdue by 30 days', 'Unresolved', '2026-08-15 14:00:00'];
        }
        if (count($entryIds) >= 2) {
            $seedData[] = [$entryIds[1], null, 'Missing receipt documentation', 'Unresolved', '2026-08-18 09:30:00'];
        }
        if (count($auditIds) >= 2) {
            $seedData[] = [null, $auditIds[1], 'Financial records incomplete', 'Unresolved', '2026-08-22 11:00:00'];
        }
        if (count($entryIds) >= 3) {
            $seedData[] = [$entryIds[2], null, 'Duplicate payment detected', 'Unresolved', '2026-08-25 16:00:00'];
        }
        if (count($entryIds) >= 1) {
            $seedData[] = [$entryIds[0], null, 'Unauthorized transfer flagged', 'Resolved', '2026-08-10 10:00:00'];
        }
        foreach ($seedData as $row) {
            $stmt->execute($row);
        }
        echo "Seeded RedFlag.\n";
    }

    // Seed Report (if empty) — only reference existing users
    $rptCount = $pdo->query("SELECT COUNT(*) FROM Report")->fetchColumn();
    if ($rptCount == 0) {
        $adminId = $pdo->query("SELECT user_id FROM User WHERE role = 'NYSCAdministrator' LIMIT 1")->fetchColumn();
        $coordId = $pdo->query("SELECT user_id FROM User WHERE role LIKE 'Divisional%' LIMIT 1")->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO Report (type, scope_level, scope_id, generated_by, generated_at, status, period) VALUES (?,?,?,?,?,?,?)");
        $seedData = [
            ['Monthly','Division',1,$coordId,'2026-08-01 10:00:00','Submitted','2026-08'],
            ['Monthly','Division',2,$coordId,'2026-08-02 14:00:00','Submitted','2026-08'],
            ['Monthly','Division',3,null,null,'Missing','2026-08'],
            ['Monthly','Division',4,$adminId,'2026-08-03 09:00:00','Submitted','2026-08'],
            ['Monthly','Division',5,null,null,'Missing','2026-08'],
            ['Monthly','Division',6,null,null,'Missing','2026-08'],
            ['Quarterly','Zone',1,$adminId,'2026-08-10 10:00:00','Submitted','Q2-2026'],
            ['Quarterly','Zone',2,$adminId,'2026-08-12 14:00:00','Submitted','Q2-2026'],
            ['Quarterly','Zone',3,$adminId,'2026-08-15 09:00:00','Submitted','Q2-2026'],
        ];
        foreach ($seedData as $row) {
            $stmt->execute($row);
        }
        echo "Seeded Report.\n";
    }

    // Seed ClubHealthScore (if empty) — uses existing club IDs
    $chsCount = $pdo->query("SELECT COUNT(*) FROM ClubHealthScore")->fetchColumn();
    if ($chsCount == 0) {
        $clubIds = $pdo->query("SELECT club_id FROM Club WHERE status = 'Active' ORDER BY club_id")->fetchAll(PDO::FETCH_COLUMN);
        if (count($clubIds) >= 1) {
            $stmt = $pdo->prepare("INSERT INTO ClubHealthScore (club_id, event_score, attendance_score, financial_score, total_score, status, calculated_at) VALUES (?,?,?,?,?,?,?)");
            $statuses = ['Green','Green','Red','Green','Green','Yellow','Green'];
            foreach ($clubIds as $i => $cid) {
                $status = $statuses[$i % count($statuses)];
                $score = $status === 'Green' ? 85.0 : ($status === 'Yellow' ? 65.0 : 35.0);
                $stmt->execute([$cid, $score, $score + 2, $score - 1, $score, $status, '2026-08-01']);
            }
            echo "Seeded ClubHealthScore.\n";
        } else {
            echo "No active clubs to seed ClubHealthScore.\n";
        }
    }

    // ============================================
    // Seed BankAccount (if empty)
    // ============================================
    $baCount = $pdo->query("SELECT COUNT(*) FROM BankAccount")->fetchColumn();
    if ($baCount == 0) {
        $pdo->exec("INSERT INTO BankAccount (bank_name, branch_name, account_number, account_name, is_core_account, status) VALUES
            ('Bank of Ceylon', 'Colombo Fort', '8620-0012-3456-7890', 'NYSC Colombo Operational Fund', 1, 'Active'),
            ('Bank of Ceylon', 'Kandy City', '8620-0023-4567-8901', 'NYSC Central Province Fund', 0, 'Active'),
            ('Bank of Ceylon', 'Galle Main', '8620-0034-5678-9012', 'NYSC Southern Province Fund', 0, 'Active')");
        echo "Seeded BankAccount.\n";
    }

    // ============================================
    // Seed FundTransfer (if empty)
    // ============================================
    $ftCount = $pdo->query("SELECT COUNT(*) FROM FundTransfer")->fetchColumn();
    if ($ftCount == 0) {
        $adminId = $pdo->query("SELECT user_id FROM User WHERE role = 'NYSCAdministrator' LIMIT 1")->fetchColumn();
        $baId    = $pdo->query("SELECT bank_account_id FROM BankAccount WHERE is_core_account = 1 LIMIT 1")->fetchColumn();
        $zones   = $pdo->query("SELECT zonal_id FROM Zone ORDER BY zonal_id LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        if ($adminId && $baId && count($zones) >= 2) {
            $stmt = $pdo->prepare("INSERT INTO FundTransfer
                (reference_number, source_account_id, target_zonal_id, amount, transfer_date, disbursement_method, purpose, status, authorized_by, created_at)
                VALUES (?,?,?,?,?,?,?,?,?,?)");
            $seedTransfers = [
                ['TRF-2026-0048', $baId, $zones[0], 750000.00, '2026-10-28', 'BOC_RTGS',     'Q4 Zonal Youth Club Grants Western Province Phase 1', 'Processing', $adminId, '2026-10-28 10:42:00'],
                ['CHQ-2026-0045', $baId, $zones[1], 500000.00, '2026-10-25', 'Cheque_SLIPS', 'Quarterly Sports Allocation Central Province',          'Completed',  $adminId, '2026-10-25 09:00:00'],
                ['TRF-2026-0042', $baId, $zones[0], 1200000.00,'2026-10-20', 'BOC_RTGS',     'National Skills Camp 2026 Colombo Division',            'Completed',  $adminId, '2026-10-20 08:30:00'],
                ['CHQ-2026-0039', $baId, $zones[2], 600000.00, '2026-10-14', 'Cheque_SLIPS', 'Eco Youth Initiatives Southern Province Coastal Hub',   'Completed',  $adminId, '2026-10-14 11:00:00'],
                ['TRF-2026-0031', $baId, $zones[1], 450000.00, '2026-10-08', 'BOC_RTGS',     'Agro Youth Seed Capital Central Province',              'Completed',  $adminId, '2026-10-08 14:15:00'],
            ];
            foreach ($seedTransfers as $row) {
                $stmt->execute($row);
            }
            echo "Seeded FundTransfer.\n";
        } else {
            echo "Skipping FundTransfer seed — missing admin/bank/zone data.\n";
        }
    }

    // ============================================
    // Ensure NYSC Admin user for priyadarshanir67@gmail.com
    // ============================================
    $nyscEmail = 'priyadarshanir67@gmail.com';
    $existingNysc = $pdo->prepare("SELECT user_id FROM User WHERE email = ?");
    $existingNysc->execute([$nyscEmail]);
    if (!$existingNysc->fetchColumn()) {
        $hash = password_hash('Admin@1234', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO User (username, email, password_hash, first_name, last_name, role, status)
            VALUES (?, ?, ?, 'NYSC', 'Administrator', 'NYSCAdministrator', 'Active')")
            ->execute(['nysc_admin_' . substr(md5($nyscEmail), 0, 6), $nyscEmail, $hash]);
        echo "Created NYSCAdministrator user: $nyscEmail (password: Admin@1234)\n";
    } else {
        $pdo->prepare("UPDATE User SET role = 'NYSCAdministrator', status = 'Active' WHERE email = ?")
            ->execute([$nyscEmail]);
        echo "Updated existing user $nyscEmail to NYSCAdministrator.\n";
    }

    // Seed AuditLog recent entries (if empty) — uses existing user_ids
    $alCount = $pdo->query("SELECT COUNT(*) FROM AuditLog")->fetchColumn();
    if ($alCount == 0) {
        $adminId = $pdo->query("SELECT user_id FROM User WHERE role = 'NYSCAdministrator' LIMIT 1")->fetchColumn();
        $anyUser = $pdo->query("SELECT user_id FROM User WHERE status = 'Active' LIMIT 1")->fetchColumn();
        if ($adminId && $anyUser) {
            $stmt = $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, `timestamp`, details) VALUES (?,?,?,?,?,?)");
            $now = time();
            $seedData = [
                [$anyUser, 'APPROVE', 'AssetTransfer', 1, date('Y-m-d H:i:s', $now - 720), 'Zonal Coordinator approved 3 asset transfer requests'],
                [$adminId, 'FLAG', 'Audit', 3, date('Y-m-d H:i:s', $now - 3600), 'Club flagged for overdue audit'],
                [$anyUser, 'SUBMIT', 'Report', 7, date('Y-m-d H:i:s', $now - 10800), 'Divisional Treasurer submitted Q2 fund ledger'],
                [$anyUser, 'SUBMIT', 'ClubApplication', 7, date('Y-m-d H:i:s', $now - 86400), 'New club registration submitted'],
                [$adminId, 'APPROVE', 'Event', 2, date('Y-m-d H:i:s', $now - 172800), 'Divisional Coordinator approved youth sports championship'],
                [$adminId, 'CREATE', 'Event', 4, date('Y-m-d H:i:s', $now - 259200), 'National Youth Day event created by Administrator'],
                [$anyUser, 'UPDATE', 'Club', 2, date('Y-m-d H:i:s', $now - 345600), 'Colombo Central Youth updated member count'],
                [$adminId, 'ASSIGN', 'User', 25, date('Y-m-d H:i:s', $now - 432000), 'New member assigned to cycling club'],
            ];
            foreach ($seedData as $row) {
                $stmt->execute($row);
            }
            echo "Seeded AuditLog.\n";
        } else {
            echo "Not enough users to seed AuditLog.\n";
        }
    }

    echo "\n=== Migration completed successfully ===\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
