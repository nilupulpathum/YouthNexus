<?php
/**
 * Local demo seed for the Social CV (skills, timeline, endorsements).
 *
 * LOCAL DEMO DB ONLY. Never run against a real database: every row it
 * creates is synthetic and labelled [DEMO-CV]. Re-run safe (skips pairs
 * that already have attendance; endorsements upsert by unique pair).
 *
 * Usage:
 *   c:\xampp\php\php.exe database/seed_cv_demo.php seed    (default)
 *   c:\xampp\php\php.exe database/seed_cv_demo.php clean   (removes [DEMO-CV] rows)
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/../app/core/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/UserModel.php';
require_once __DIR__ . '/../app/models/EventModel.php';
require_once __DIR__ . '/../app/models/AttendanceModel.php';
require_once __DIR__ . '/../app/models/EndorsementModel.php';

const DEMO_CV_MARK = '[DEMO-CV]';

$mode = ($argv[1] ?? 'seed') === 'clean' ? 'clean' : 'seed';

try {
    $pdo = Database::getInstance()->getConnection();

    foreach (['Skill', 'Endorsement', 'Attendance', 'Event', 'User'] as $table) {
        $exists = $pdo->query("SHOW TABLES LIKE '{$table}'")->fetchColumn();
        if (!$exists) {
            throw new RuntimeException("Table `{$table}` missing — run the migrations first.");
        }
    }

    $users = new UserModel();
    $events = new EventModel();
    $attendance = new AttendanceModel();
    $endorsements = new EndorsementModel();

    if ($mode === 'clean') {
        $att = $pdo->prepare("DELETE FROM Attendance WHERE remark = ?");
        $att->execute([DEMO_CV_MARK . ' seed']);
        $end = $pdo->prepare("DELETE FROM Endorsement WHERE text LIKE ?");
        $end->execute([DEMO_CV_MARK . '%']);
        $ev = $pdo->prepare("DELETE FROM Event WHERE title LIKE ?");
        $ev->execute([DEMO_CV_MARK . '%']);
        echo "Cleaned {$att->rowCount()} attendance + {$end->rowCount()} endorsement + {$ev->rowCount()} event [DEMO-CV] rows.\n";
        exit(0);
    }

    $byEmail = static function ($email) use ($users) {
        $row = $users->findByEmail($email);
        if (!$row) {
            throw new RuntimeException("Demo user missing: {$email}");
        }
        return $row;
    };
    $president = $byEmail('club.gampaha@youthnexus.com');
    $secretary = $byEmail('secretary.gampaha@youthnexus.com');
    $treasurer = $byEmail('treasurer.gampaha@youthnexus.com');
    $member = $byEmail('member.gampaha@youthnexus.com');
    $clubId = (int) $member->club_id;

    // Two labelled past events (different categories for skill variety).
    // Created only when absent; real rows are never modified.
    $demoEvents = [
        ['title' => DEMO_CV_MARK . ' Community Cleanup', 'event_type' => 'Community Service',
         'location' => 'Gampaha Park', 'start_datetime' => '2026-09-05 08:00:00', 'end_datetime' => '2026-09-05 12:00:00'],
        ['title' => DEMO_CV_MARK . ' Youth Leadership Talk', 'event_type' => 'Leadership',
         'location' => 'Gampaha Town Hall', 'start_datetime' => '2026-09-12 14:00:00', 'end_datetime' => '2026-09-12 17:00:00'],
    ];
    $findEv = $pdo->prepare("SELECT event_id FROM Event WHERE title = ? LIMIT 1");
    $addEv = $pdo->prepare(
        "INSERT INTO Event (title, event_type, location, start_datetime, end_datetime,
                            organizer_club_id, organizer_division_id, target_scope, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'AllInScope', 'Completed', ?)"
    );
    $demoIds = [];
    foreach ($demoEvents as $demo) {
        $findEv->execute([$demo['title']]);
        $existing = $findEv->fetchColumn();
        if ($existing) {
            $demoIds[] = (int) $existing;
            continue;
        }
        $addEv->execute([
            $demo['title'], $demo['event_type'], $demo['location'],
            $demo['start_datetime'], $demo['end_datetime'],
            $clubId, (int) $member->division_id, (int) $president->user_id,
        ]);
        $demoIds[] = (int) $pdo->lastInsertId();
        echo "Event: {$demo['title']}\n";
    }

    // Past club events with usable datetimes, Completed first, demos included.
    $pool = $pdo->prepare(
        "SELECT event_id, title, start_datetime, end_datetime, status FROM Event
         WHERE organizer_club_id = ? AND start_datetime < NOW()
         ORDER BY status = 'Completed' DESC, start_datetime DESC LIMIT 6"
    );
    $pool->execute([$clubId]);
    $pool = $pool->fetchAll(PDO::FETCH_OBJ);
    if (count($pool) === 0) {
        throw new RuntimeException("No past club events to seed attendance against.");
    }

    // Attendance: demos first (skip pairs that already have a row — real
    // attendance is never overwritten).
    $byId = static function ($id) use ($pdo) {
        $stmt = $pdo->prepare("SELECT event_id, title, start_datetime, end_datetime FROM Event WHERE event_id = ? LIMIT 1");
        $stmt->execute([(int) $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    };
    $plan = [
        [$member, array_map($byId, $demoIds)],
        [$treasurer, array_map($byId, $demoIds)],
        [$secretary, array_map($byId, array_slice($demoIds, 0, 1))],
    ];
    $marked = 0;
    $check = $pdo->prepare("SELECT 1 FROM Attendance WHERE event_id = ? AND user_id = ? LIMIT 1");
    foreach ($plan as [$person, $list]) {
        foreach ($list as $ev) {
            $check->execute([(int) $ev->event_id, (int) $person->user_id]);
            if ($check->fetchColumn()) {
                continue;
            }
            $ok = $attendance->saveClubAttendance(
                $clubId,
                (int) $ev->event_id,
                (int) $person->user_id,
                'Present',
                $ev->start_datetime ? date('H:i:s', strtotime($ev->start_datetime)) : null,
                $ev->end_datetime ? date('H:i:s', strtotime($ev->end_datetime)) : null,
                DEMO_CV_MARK . ' seed',
                (int) $president->user_id
            );
            if ($ok) {
                $marked++;
                echo "Present: {$person->email} @ {$ev->title}\n";
            }
        }
    }

    // Sample endorsements (upsert by unique pair, visibly labelled).
    $endorsements->upsert(
        (int) $member->user_id,
        (int) $president->user_id,
        'President',
        DEMO_CV_MARK . ' Dilini never misses a cleanup drive and keeps the attendance sheet honest.'
    );
    echo "Endorsement: president -> member\n";
    $endorsements->upsert(
        (int) $treasurer->user_id,
        (int) $secretary->user_id,
        'Secretary',
        DEMO_CV_MARK . ' Kasun reconciles the ledger to the last cent before every review.'
    );
    echo "Endorsement: secretary -> treasurer\n";

    echo PHP_EOL . "Seeded {$marked} attendance rows + 2 endorsements. Remove with: seed_cv_demo.php clean" . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, "SEED FAILED: " . $exception->getMessage() . PHP_EOL);
    exit(1);
}
