<?php

/**
 * Zonal coordinator monitor (D8). Zone-scoped reads over Division / Club /
 * User / Event / Attendance plus ClubHealthFlag writes. Scores come from
 * DivisionalClubHealthModel::scoreClub (same formula as divisional).
 */
class ZoneMonitorModel extends Model {

    public function getZone(int $zonalId) {
        return $this->single(
            "SELECT zonal_id, zonal_name FROM Zone WHERE zonal_id = ? LIMIT 1",
            [$zonalId]
        );
    }

    public function getDivisions(int $zonalId): array {
        return $this->resultSet(
            "SELECT division_id, division_name FROM Division
             WHERE zonal_id = ? ORDER BY division_name",
            [$zonalId]
        );
    }

    public function getClubs(int $zonalId): array {
        return $this->resultSet(
            "SELECT c.club_id, c.club_name, c.description, c.registration_date,
                    c.status, c.club_code, d.division_id, d.division_name,
                    COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.user_id END) AS active_members,
                    COUNT(DISTINCT CASE WHEN hf.status IN ('Open','UnderReview') THEN hf.health_flag_id END) AS open_flags
             FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             LEFT JOIN User u ON u.club_id = c.club_id
             LEFT JOIN ClubHealthFlag hf ON hf.club_id = c.club_id
             WHERE d.zonal_id = ? AND c.status IN ('Active', 'Flagged')
             GROUP BY c.club_id
             ORDER BY c.club_name",
            [$zonalId]
        );
    }

    public function getExecutives(int $clubId): array {
        return $this->resultSet(
            "SELECT user_id, first_name, last_name, role FROM User
             WHERE club_id = ? AND status = 'Active'
               AND role IN ('ClubPresident', 'ClubSecretary', 'ClubTreasurer')
             ORDER BY FIELD(role, 'ClubPresident', 'ClubSecretary', 'ClubTreasurer')",
            [(int) $clubId]
        );
    }

    public function getRecentEvents(int $clubId, int $limit = 3): array {
        return $this->resultSet(
            "SELECT event_id, title, start_datetime, status FROM Event
             WHERE organizer_club_id = ?
             ORDER BY start_datetime DESC LIMIT ?",
            [(int) $clubId, (int) $limit]
        );
    }

    public function getAttendanceRate(int $clubId): ?float {
        $row = $this->single(
            "SELECT COALESCE(SUM(a.status = 'Present'), 0) AS present_count,
                    COUNT(a.attendance_id) AS recorded_count
             FROM Event e
             INNER JOIN Attendance a ON a.event_id = e.event_id
             WHERE e.organizer_club_id = ? AND e.status = 'Completed'",
            [(int) $clubId]
        );
        $recorded = (int) ($row->recorded_count ?? 0);
        if ($recorded === 0) {
            return null;
        }
        return round((int) ($row->present_count ?? 0) * 100 / $recorded, 1);
    }

    /**
     * Zonal flag on a club in the coordinator's zone. Throws on violations.
     */
    public function raiseZoneFlag(int $zonalId, int $clubId, int $userId, string $category, string $reason): int {
        $allowed = ['FinancialConcern', 'EventAttendanceConcern', 'GovernanceConcern'];
        if (!in_array($category, $allowed, true)) {
            throw new InvalidArgumentException('Select a permitted concern type.');
        }
        $club = $this->single(
            "SELECT c.club_id FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             WHERE c.club_id = ? AND d.zonal_id = ? AND c.status IN ('Active', 'Flagged') LIMIT 1",
            [$clubId, $zonalId]
        );
        if (!$club) {
            throw new InvalidArgumentException('Club not found in your zone.');
        }
        $reason = trim($reason);
        $len = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if ($len < 10 || $len > 1000) {
            throw new InvalidArgumentException('Provide a clear reason between 10 and 1000 characters.');
        }
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                "INSERT INTO ClubHealthFlag (club_id, flag_category, source, reason, status, raised_by)
                 VALUES (?, ?, 'Manual', ?, 'Open', ?)"
            );
            $insert->execute([$clubId, $category, $reason, $userId]);
            $flagId = (int) $pdo->lastInsertId();
            $admins = $pdo->prepare(
                "SELECT user_id FROM User WHERE role = 'NYSCAdministrator' AND status = 'Active'"
            );
            $admins->execute();
            $notify = $pdo->prepare(
                "INSERT INTO Notification
                    (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
                 VALUES (?, 'ClubHealthFlag', ?, 'ClubHealthFlag', ?, 0, NOW())"
            );
            foreach ($admins->fetchAll() as $admin) {
                $notify->execute([(int) $admin->user_id, 'A zonal coordinator flagged a club for review.', $flagId]);
            }
            $audit = $pdo->prepare(
                "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
                 VALUES (?, 'FLAG_CLUB', 'Club', ?, ?)"
            );
            $audit->execute([$userId, $clubId, substr($reason, 0, 200)]);
            $pdo->commit();
            return $flagId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
