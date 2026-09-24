<?php
/** Idempotent schema migration for the Broadcast Announcements workflow. */
require_once __DIR__ . '/../app/core/config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "Connected to database.\n";

    $tableExists = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    foreach (['User', 'Club', 'Division', 'Zone'] as $table) {
        $tableExists->execute([$table]);
        if ((int) $tableExists->fetchColumn() === 0) {
            throw new RuntimeException("Required table `$table` does not exist.");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Announcement` (
        `announcement_id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(150) NOT NULL,
        `body` TEXT NOT NULL,
        `level` ENUM('Club','Divisional','Zonal','NYSC') NOT NULL DEFAULT 'Divisional',
        `organizer_club_id` INT NULL,
        `organizer_division_id` INT NULL,
        `organizer_zonal_id` INT NULL,
        `target_audience` ENUM('AllDivisionalClubs','ClubPresidentsSecretaries','AllMembers') NULL,
        `category` VARCHAR(100) NULL,
        `priority` ENUM('Normal','Urgent') NOT NULL DEFAULT 'Normal',
        `status` ENUM('Draft','Published') NOT NULL DEFAULT 'Draft',
        `view_count` INT NOT NULL DEFAULT 0,
        `created_by` INT NOT NULL,
        `published_at` DATETIME NULL,
        `content_edited_at` DATETIME NULL DEFAULT NULL,
        `last_edited_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `deleted_at` DATETIME NULL,
        CONSTRAINT `fk_announcement_club` FOREIGN KEY (`organizer_club_id`) REFERENCES `Club` (`club_id`),
        CONSTRAINT `fk_announcement_division` FOREIGN KEY (`organizer_division_id`) REFERENCES `Division` (`division_id`),
        CONSTRAINT `fk_announcement_zonal` FOREIGN KEY (`organizer_zonal_id`) REFERENCES `Zone` (`zonal_id`),
        CONSTRAINT `fk_announcement_creator` FOREIGN KEY (`created_by`) REFERENCES `User` (`user_id`),
        INDEX `idx_announcement_club` (`organizer_club_id`),
        INDEX `idx_announcement_division` (`organizer_division_id`),
        INDEX `idx_announcement_zonal` (`organizer_zonal_id`),
        INDEX `idx_announcement_status` (`status`),
        INDEX `idx_announcement_level_status` (`level`, `status`),
        INDEX `idx_announcement_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $announcementColumns = $pdo->query("SHOW COLUMNS FROM `Announcement`")->fetchAll(PDO::FETCH_COLUMN);
    $columnDefinitions = [
        'organizer_club_id' => "INT NULL AFTER `level`",
        'organizer_division_id' => "INT NULL AFTER `organizer_club_id`",
        'organizer_zonal_id' => "INT NULL AFTER `organizer_division_id`",
        'content_edited_at' => "DATETIME NULL DEFAULT NULL AFTER `published_at`",
        'deleted_at' => "DATETIME NULL AFTER `created_at`",
    ];
    foreach ($columnDefinitions as $column => $definition) {
        if (!in_array($column, $announcementColumns, true)) {
            $pdo->exec("ALTER TABLE `Announcement` ADD COLUMN `$column` $definition");
            echo "Added Announcement.$column.\n";
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `AnnouncementAudience` (
        `audience_id` INT AUTO_INCREMENT PRIMARY KEY,
        `announcement_id` INT NOT NULL,
        `target_role` VARCHAR(50) NOT NULL,
        `selection_mode` ENUM('All','Selected') NOT NULL DEFAULT 'All',
        UNIQUE KEY `uq_announcement_target_role` (`announcement_id`, `target_role`),
        CONSTRAINT `fk_announcement_audience` FOREIGN KEY (`announcement_id`)
            REFERENCES `Announcement` (`announcement_id`) ON DELETE CASCADE,
        INDEX `idx_announcement_audience_role` (`target_role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $audienceColumns = $pdo->query("SHOW COLUMNS FROM `AnnouncementAudience`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('selection_mode', $audienceColumns, true)) {
        $pdo->exec("ALTER TABLE `AnnouncementAudience` ADD COLUMN `selection_mode`
                    ENUM('All','Selected') NOT NULL DEFAULT 'All' AFTER `target_role`");
        echo "Added AnnouncementAudience.selection_mode.\n";
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `AnnouncementAudienceUser` (
        `audience_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        PRIMARY KEY (`audience_id`, `user_id`),
        CONSTRAINT `fk_announcement_audience_user_audience` FOREIGN KEY (`audience_id`)
            REFERENCES `AnnouncementAudience` (`audience_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_announcement_audience_user_user` FOREIGN KEY (`user_id`)
            REFERENCES `User` (`user_id`),
        INDEX `idx_announcement_audience_user_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `AnnouncementAttachment` (
        `attachment_id` INT AUTO_INCREMENT PRIMARY KEY,
        `announcement_id` INT NOT NULL,
        `file_name` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(255) NOT NULL,
        `file_size` INT NOT NULL,
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_announcement_attachment` FOREIGN KEY (`announcement_id`)
            REFERENCES `Announcement` (`announcement_id`) ON DELETE CASCADE,
        INDEX `idx_announcement_attachment` (`announcement_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `AnnouncementRead` (
        `read_id` INT AUTO_INCREMENT PRIMARY KEY,
        `announcement_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_announcement_read` (`announcement_id`, `user_id`),
        CONSTRAINT `fk_announcement_read_announcement` FOREIGN KEY (`announcement_id`)
            REFERENCES `Announcement` (`announcement_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_announcement_read_user` FOREIGN KEY (`user_id`) REFERENCES `User` (`user_id`),
        INDEX `idx_announcement_read_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Broadcast Announcement schema ready.\n";
    echo "\n=== Broadcast Announcements Migration Completed Successfully ===\n";
} catch (Throwable $exception) {
    die('Migration error: ' . $exception->getMessage() . "\n");
}
