<?php
/**
 * Seed script: Attendance and Events across Zones, Divisions, and Clubs
 * Non-destructive: only adds missing demo records if not present.
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

    // 1. Ensure Attendance table has location and photo_url if missing
    $attCols = $pdo->query("SHOW COLUMNS FROM `Attendance`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('location', $attCols)) {
        $pdo->exec("ALTER TABLE `Attendance` ADD COLUMN `location` VARCHAR(255) NULL AFTER `remark`");
        echo "Added `location` column to Attendance.\n";
    }
    if (!in_array('photo_url', $attCols)) {
        $pdo->exec("ALTER TABLE `Attendance` ADD COLUMN `photo_url` VARCHAR(255) NULL AFTER `location`");
        echo "Added `photo_url` column to Attendance.\n";
    }

    // 2. Fetch admin user
    $adminId = $pdo->query("SELECT user_id FROM User WHERE role = 'NYSCAdministrator' LIMIT 1")->fetchColumn();
    if (!$adminId) {
        $adminId = $pdo->query("SELECT user_id FROM User LIMIT 1")->fetchColumn();
    }

    // 3. Ensure sample clubs exist across multiple divisions
    $divisions = $pdo->query("SELECT division_id, division_name, zonal_id FROM Division ORDER BY division_id LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

    $sampleClubs = [
        ['name' => 'Colombo Central Youth Tech Club',    'divId' => 1, 'code' => 'CLB-COL-2026-001'],
        ['name' => 'Gampaha Green Pioneers Youth Club',  'divId' => 2, 'code' => 'CLB-GAM-2026-002'],
        ['name' => 'Kalutara Coastal Youth Community',   'divId' => 3, 'code' => 'CLB-KAL-2026-003'],
        ['name' => 'Kandy Hills Vocational Youth Club',   'divId' => 4, 'code' => 'CLB-KAN-2026-004'],
        ['name' => 'Galle Southern Eco Youth Club',       'divId' => 5, 'code' => 'CLB-GAL-2026-005'],
    ];

    $insClub = $pdo->prepare("INSERT INTO Club (club_name, description, division_id, registration_date, status, no_of_members, club_code)
                              VALUES (?, 'Empowering regional youth through skill and service', ?, NOW(), 'Active', 25, ?)");

    foreach ($sampleClubs as $sc) {
        $chk = $pdo->prepare("SELECT club_id FROM Club WHERE club_name = ?");
        $chk->execute([$sc['name']]);
        if (!$chk->fetchColumn()) {
            $insClub->execute([$sc['name'], $sc['divId'], $sc['code']]);
            echo "Created club: {$sc['name']}\n";
        }
    }

    // 4. Ensure sample members exist in these clubs
    $clubs = $pdo->query("SELECT club_id, club_name, division_id FROM Club WHERE status = 'Active'")->fetchAll(PDO::FETCH_ASSOC);
    $dummyMembers = [
        ['fn' => 'Kavinda',   'ln' => 'Perera',    'email' => 'kavinda.p@example.lk'],
        ['fn' => 'Sanduni',   'ln' => 'Jayawardena','email' => 'sanduni.j@example.lk'],
        ['fn' => 'Dilshan',   'ln' => 'Silva',     'email' => 'dilshan.s@example.lk'],
        ['fn' => 'Tharushi',  'ln' => 'Fernando',  'email' => 'tharushi.f@example.lk'],
        ['fn' => 'Nuwan',     'ln' => 'Gamage',    'email' => 'nuwan.g@example.lk'],
        ['fn' => 'Anuki',     'ln' => 'Rajapakse', 'email' => 'anuki.r@example.lk'],
        ['fn' => 'Kasun',     'ln' => 'Bandara',   'email' => 'kasun.b@example.lk'],
        ['fn' => 'Bhanuka',   'ln' => 'Gunaratne', 'email' => 'bhanuka.g@example.lk'],
    ];

    $insMember = $pdo->prepare("INSERT INTO User (username, email, password_hash, first_name, last_name, role, club_id, division_id, status, membership_status)
                               VALUES (?, ?, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', ?, ?, 'ClubMember', ?, ?, 'Active', 'Active')");

    $chkUser = $pdo->prepare("SELECT user_id FROM User WHERE email = ?");

    foreach ($clubs as $ci => $c) {
        foreach ($dummyMembers as $mi => $dm) {
            $userEmail = str_replace('@', "+c{$c['club_id']}@", $dm['email']);
            $chkUser->execute([$userEmail]);
            if (!$chkUser->fetchColumn()) {
                $username = 'member_' . $c['club_id'] . '_' . ($mi + 1);
                $insMember->execute([$username, $userEmail, $dm['fn'], $dm['ln'], $c['club_id'], $c['division_id']]);
            }
        }
    }
    echo "Sample members verified/seeded for clubs.\n";

    // 5. Seed diverse events across levels (National, Zonal, Division, Club)
    $sampleEvents = [
        [
            'title'        => 'National Youth Leadership Summit 2026',
            'desc'         => 'Annual national gathering of youth leaders from all 9 provinces',
            'type'         => 'Workshop',
            'start'        => '2026-10-15 09:00:00',
            'end'          => '2026-10-15 17:00:00',
            'location'     => 'BMICH Colombo, Main Hall',
            'club_id'      => null,
            'div_id'       => null,
            'zone_id'      => null, // National
            'target_scope' => 'AllInScope',
            'max_att'      => 200,
        ],
        [
            'title'        => 'Western Province Zonal Innovation Camp',
            'desc'         => 'Zonal hackathon and vocational project showcase',
            'type'         => 'Training',
            'start'        => '2026-10-22 08:30:00',
            'end'          => '2026-10-22 16:30:00',
            'location'     => 'Youth Centre Maharagama',
            'club_id'      => null,
            'div_id'       => null,
            'zone_id'      => 4, // Colombo/Western Zone
            'target_scope' => 'AllInScope',
            'max_att'      => 100,
        ],
        [
            'title'        => 'Central Province Cultural & Heritage Showcase',
            'desc'         => 'Traditional youth artistic and cultural celebration',
            'type'         => 'Cultural',
            'start'        => '2026-10-18 10:00:00',
            'end'          => '2026-10-18 15:00:00',
            'location'     => 'Kandy Municipal Auditorium',
            'club_id'      => null,
            'div_id'       => null,
            'zone_id'      => 6, // Kandy Zone
            'target_scope' => 'AllInScope',
            'max_att'      => 80,
        ],
        [
            'title'        => 'Colombo District Vocational Skills Drive',
            'desc'         => 'Hands-on IT and digital literacy program for school leavers',
            'type'         => 'Workshop',
            'start'        => '2026-10-10 09:00:00',
            'end'          => '2026-10-10 14:00:00',
            'location'     => 'Divisional Secretariat Colombo Central',
            'club_id'      => null,
            'div_id'       => 1, // Colombo Division
            'zone_id'      => null,
            'target_scope' => 'AllInScope',
            'max_att'      => 50,
        ],
        [
            'title'        => 'Galle Beach Coastal Preservation Campaign',
            'desc'         => 'Eco youth coastal clean-up and mangrove seedling drive',
            'type'         => 'Community Service',
            'start'        => '2026-10-05 07:30:00',
            'end'          => '2026-10-05 12:00:00',
            'location'     => 'Galle Fort Coastal Park',
            'club_id'      => 5, // Galle Club
            'div_id'       => null,
            'zone_id'      => null,
            'target_scope' => 'AllInScope',
            'max_att'      => 40,
        ],
    ];

    $insEvt = $pdo->prepare("INSERT INTO Event
        (title, description, event_type, start_datetime, end_datetime, location, organizer_club_id, organizer_division_id, organizer_zonal_id, target_scope, status, max_attendance, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved', ?, ?, NOW())");

    $chkEvt = $pdo->prepare("SELECT event_id FROM Event WHERE title = ?");

    foreach ($sampleEvents as $se) {
        $chkEvt->execute([$se['title']]);
        if (!$chkEvt->fetchColumn()) {
            $insEvt->execute([
                $se['title'],
                $se['desc'],
                $se['type'],
                $se['start'],
                $se['end'],
                $se['location'],
                $se['club_id'],
                $se['div_id'],
                $se['zone_id'],
                $se['target_scope'],
                $se['max_att'],
                $adminId,
            ]);
            echo "Created approved event: {$se['title']}\n";
        }
    }

    // 6. Seed Attendance records for approved events
    $allApprovedEvents = $pdo->query("SELECT event_id, title, organizer_club_id, organizer_division_id FROM Event WHERE status = 'Approved'")->fetchAll(PDO::FETCH_ASSOC);

    $insAtt = $pdo->prepare("INSERT INTO Attendance (event_id, user_id, status, check_in_time, check_out_time, remark, recorded_by, recorded_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

    $chkAtt = $pdo->prepare("SELECT attendance_id FROM Attendance WHERE event_id = ? AND user_id = ?");

    foreach ($allApprovedEvents as $evt) {
        // Pick members from clubs
        if (!empty($evt['organizer_club_id'])) {
            $members = $pdo->query("SELECT user_id FROM User WHERE club_id = {$evt['organizer_club_id']} LIMIT 6")->fetchAll(PDO::FETCH_COLUMN);
        } elseif (!empty($evt['organizer_division_id'])) {
            $members = $pdo->query("SELECT user_id FROM User WHERE division_id = {$evt['organizer_division_id']} LIMIT 6")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $members = $pdo->query("SELECT user_id FROM User WHERE club_id IS NOT NULL LIMIT 8")->fetchAll(PDO::FETCH_COLUMN);
        }

        foreach ($members as $idx => $uid) {
            $chkAtt->execute([$evt['event_id'], $uid]);
            if (!$chkAtt->fetchColumn()) {
                $status = ($idx % 5 === 4) ? 'Absent' : 'Present';
                $checkIn = ($status === 'Present') ? date('Y-m-d 09:15:00') : null;
                $remark = ($status === 'Present') ? 'On time' : 'Excused medical absence';
                $insAtt->execute([$evt['event_id'], $uid, $status, $checkIn, null, $remark, $adminId]);
            }
        }
    }

    echo "Attendance records verified/seeded.\n";
    echo "\n=== ATTENDANCE SEEDING COMPLETE ===\n";

} catch (Exception $e) {
    die("Seeding failed: " . $e->getMessage() . "\n");
}
