<?php
/**
 * Migration Script: National Analytics Feature
 *
 * Creates tables:
 *  - AnalyticsSnapshot
 *  - AssetAudit
 *  - ClubHealthScore (ensures exists)
 *  - VolunteerHistory (ensures exists)
 *  - Notification (ensures exists)
 *
 * Seeds baseline data for realistic dashboard representation.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_national_analytics.php
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

    // 1. CREATE AnalyticsSnapshot TABLE
    echo "Creating AnalyticsSnapshot table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `AnalyticsSnapshot` (
        `snapshot_id`   INT AUTO_INCREMENT PRIMARY KEY,
        `metric_name`   VARCHAR(100) NOT NULL,
        `scope_level`   ENUM('National','Zonal','Divisional') NOT NULL DEFAULT 'National',
        `scope_id`      INT NULL,
        `value`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `snapshot_date` DATE NOT NULL,
        `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_metric_scope (`metric_name`, `scope_level`, `scope_id`, `snapshot_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 2. CREATE AssetAudit TABLE
    echo "Creating AssetAudit table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `AssetAudit` (
        `asset_audit_id` INT AUTO_INCREMENT PRIMARY KEY,
        `club_id`        INT NOT NULL,
        `audit_year`     INT NOT NULL,
        `due_date`       DATE NOT NULL,
        `status`         ENUM('Pending','Submitted','Approved','Overdue') NOT NULL DEFAULT 'Pending',
        `submitted_at`   TIMESTAMP NULL DEFAULT NULL,
        `reviewed_by`    INT NULL,
        `reviewed_at`    TIMESTAMP NULL DEFAULT NULL,
        `notes`          TEXT NULL,
        FOREIGN KEY (`club_id`) REFERENCES `Club`(`club_id`) ON DELETE CASCADE,
        INDEX idx_club_year (`club_id`, `audit_year`),
        INDEX idx_status (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 3. ENSURE ClubHealthScore TABLE
    echo "Checking / Creating ClubHealthScore table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ClubHealthScore` (
        `score_id`         INT AUTO_INCREMENT PRIMARY KEY,
        `club_id`          INT NOT NULL,
        `calculated_date`  DATE NOT NULL,
        `health_status`    ENUM('Green','Yellow','Red') NOT NULL DEFAULT 'Green',
        `overall_score`    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `governance_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `activity_score`   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `finance_score`    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `reporting_score`  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (`club_id`) REFERENCES `Club`(`club_id`) ON DELETE CASCADE,
        INDEX idx_club_date (`club_id`, `calculated_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 4. ENSURE VolunteerHistory TABLE
    echo "Checking / Creating VolunteerHistory table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `VolunteerHistory` (
        `history_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`    INT NOT NULL,
        `club_id`    INT NULL,
        `event_id`   INT NULL,
        `hours`      DECIMAL(8,2) NOT NULL DEFAULT 0.00,
        `date`       DATE NOT NULL,
        `status`     ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Verified',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (`user_id`),
        INDEX idx_club (`club_id`),
        INDEX idx_date (`date`),
        INDEX idx_status (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 5. ENSURE Notification TABLE
    echo "Checking / Creating Notification table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Notification` (
        `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`         INT NOT NULL,
        `type`            VARCHAR(50) NOT NULL,
        `title`           VARCHAR(255) NOT NULL,
        `message`         TEXT NOT NULL,
        `is_read`         TINYINT(1) NOT NULL DEFAULT 0,
        `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_read (`user_id`, `is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Check Club columns for overall_health_score & health_status
    echo "Checking Club table columns...\n";
    $clubCols = $pdo->query("SHOW COLUMNS FROM `Club`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('overall_health_score', $clubCols)) {
        $pdo->exec("ALTER TABLE `Club` ADD COLUMN `overall_health_score` DECIMAL(5,2) NOT NULL DEFAULT 75.00");
    }
    if (!in_array('health_status', $clubCols)) {
        $pdo->exec("ALTER TABLE `Club` ADD COLUMN `health_status` ENUM('Green','Yellow','Red') NOT NULL DEFAULT 'Green'");
    }

    // 6. SEED BASELINE DATA IF NEEDED
    echo "Seeding baseline data if empty...\n";

    // Volunteer History Seeding
    $volCount = (int)$pdo->query("SELECT COUNT(*) FROM `VolunteerHistory`")->fetchColumn();
    if ($volCount === 0) {
        echo "Seeding VolunteerHistory for 2026...\n";
        // Get any user and clubs
        $firstUser = (int)$pdo->query("SELECT user_id FROM `User` LIMIT 1")->fetchColumn();
        if (!$firstUser) {
            $firstUser = 1;
        }
        $clubs = $pdo->query("SELECT club_id FROM `Club` LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);

        $monthHours = [
            '2026-01-15' => 14200,
            '2026-02-18' => 18400,
            '2026-03-20' => 22100,
            '2026-04-12' => 25800,
            '2026-05-25' => 29400,
            '2026-06-18' => 33200,
            '2026-07-22' => 38100,
            '2026-08-14' => 42500,
            '2026-09-19' => 46800,
            '2026-10-15' => 51200,
            '2026-11-20' => 58000,
        ];

        $stmtVol = $pdo->prepare("INSERT INTO `VolunteerHistory` (`user_id`, `club_id`, `hours`, `date`, `status`) VALUES (?, ?, ?, ?, 'Verified')");
        foreach ($monthHours as $d => $hrs) {
            $clubId = !empty($clubs) ? $clubs[array_rand($clubs)] : null;
            $stmtVol->execute([$firstUser, $clubId, $hrs, $d]);
        }
        echo "VolunteerHistory seeded.\n";
    }

    // AssetAudit Seeding
    $auditCount = (int)$pdo->query("SELECT COUNT(*) FROM `AssetAudit`")->fetchColumn();
    if ($auditCount === 0) {
        echo "Seeding AssetAudit records...\n";
        $clubs = $pdo->query("SELECT club_id FROM `Club` LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($clubs)) {
            $stmtAsset = $pdo->prepare("INSERT INTO `AssetAudit` (`club_id`, `audit_year`, `due_date`, `status`, `notes`) VALUES (?, ?, ?, ?, ?)");
            $statuses = ['Approved', 'Submitted', 'Pending', 'Overdue'];
            $i = 0;
            foreach ($clubs as $cid) {
                $status = $statuses[$i % count($statuses)];
                $due = ($status === 'Overdue') ? '2026-08-01' : '2026-12-31';
                $stmtAsset->execute([$cid, 2026, $due, $status, "Annual equipment audit for FY 2026"]);
                $i++;
            }
        }
        echo "AssetAudit seeded.\n";
    }

    // AnalyticsSnapshot Seeding
    $snapCount = (int)$pdo->query("SELECT COUNT(*) FROM `AnalyticsSnapshot`")->fetchColumn();
    if ($snapCount === 0) {
        echo "Seeding AnalyticsSnapshot baseline records...\n";
        $stmtSnap = $pdo->prepare("INSERT INTO `AnalyticsSnapshot` (`metric_name`, `scope_level`, `scope_id`, `value`, `snapshot_date`) VALUES (?, 'National', NULL, ?, CURDATE())");
        $stmtSnap->execute(['TotalRegisteredYouth', 148250]);
        $stmtSnap->execute(['TotalActiveClubs', 1420]);
        $stmtSnap->execute(['NationalVolunteerHours', 342800]);
        $stmtSnap->execute(['TotalFundsCirculating', 84500000]);
        echo "AnalyticsSnapshot seeded.\n";
    }

    // Ensure some clubs have realistic scores and statuses for leaderboard & health breakdown
    $clubsCount = (int)$pdo->query("SELECT COUNT(*) FROM `Club`")->fetchColumn();
    if ($clubsCount > 0) {
        $sampleScores = [96.00, 94.00, 91.00, 89.00, 87.00, 78.00, 65.00, 58.00, 38.00, 32.00];
        $allClubs = $pdo->query("SELECT club_id FROM `Club` ORDER BY club_id ASC LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
        $upd = $pdo->prepare("UPDATE `Club` SET `overall_health_score` = ?, `health_status` = ? WHERE `club_id` = ?");
        foreach ($allClubs as $idx => $cid) {
            $score = $sampleScores[$idx % count($sampleScores)];
            $status = ($score >= 70) ? 'Green' : (($score >= 40) ? 'Yellow' : 'Red');
            $upd->execute([$score, $status, $cid]);
        }
        echo "Club health scores refreshed.\n";
    }

    echo "\nMigration complete.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
