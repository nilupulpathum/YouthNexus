<?php

require_once __DIR__ . '/DivisionalClubHealthModel.php';

/**
 * Zonal club health monitoring. Same scoring formula, snapshot workflow and
 * concern lifecycle as the divisional tier, scoped to every club in the
 * actor's zone (across its divisions). Flags notify NYSC administrators.
 */
class ZoneClubHealthModel extends DivisionalClubHealthModel {

    public function getZone(int $zonalId) {
        return $this->single(
            "SELECT zonal_id, zonal_name FROM Zone WHERE zonal_id = ? LIMIT 1",
            [$zonalId]
        );
    }

    public function refreshZone(int $zonalId): array {
        $clubs = $this->resultSet(
            "SELECT c.club_id, c.registration_date
             FROM Club c INNER JOIN Division d ON d.division_id = c.division_id
             WHERE d.zonal_id = ? AND c.status IN ('Active','Flagged') ORDER BY c.club_id",
            [$zonalId]
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

    public function getZoneClubs(int $zonalId, array $scores): array {
        $clubs = $this->resultSet(
            "SELECT c.club_id, c.club_name, c.description, c.registration_date, c.status,
                    c.club_code, c.overall_health_score, c.health_status, c.flagged,
                    d.division_name, ca.club_logo_path,
                    COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.user_id END) AS active_members,
                    COUNT(DISTINCT CASE WHEN hf.status IN ('Open','UnderReview') THEN hf.health_flag_id END) AS open_flags
             FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             LEFT JOIN ClubApplication ca ON ca.club_name COLLATE utf8mb4_unicode_ci = c.club_name COLLATE utf8mb4_unicode_ci
             LEFT JOIN User u ON u.club_id = c.club_id
             LEFT JOIN ClubHealthFlag hf ON hf.club_id = c.club_id
             WHERE d.zonal_id = ? AND c.status IN ('Active','Flagged')
             GROUP BY c.club_id
             ORDER BY c.overall_health_score DESC, c.club_name",
            [$zonalId]
        );
        foreach ($clubs as $club) {
            $club->score = $scores[(int) $club->club_id] ?? $this->calculateScore((int) $club->club_id, new DateTimeImmutable('today'));
        }
        return $clubs;
    }

    public function getZoneClubDetails(int $zonalId, int $clubId): ?array {
        $club = $this->single(
            "SELECT c.*, d.division_name, z.zonal_name, ca.club_logo_path,
                    ca.category AS club_category, ca.city AS club_city,
                    ca.date_establishment,
                    COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.user_id END) AS active_members
             FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             INNER JOIN Zone z ON z.zonal_id = d.zonal_id
             LEFT JOIN ClubApplication ca ON ca.club_name COLLATE utf8mb4_unicode_ci = c.club_name COLLATE utf8mb4_unicode_ci
             LEFT JOIN User u ON u.club_id = c.club_id
             WHERE c.club_id = ? AND d.zonal_id = ? AND c.status IN ('Active','Flagged')
             GROUP BY c.club_id",
            [$clubId, $zonalId]
        );
        if (!$club) return null;

        $end = new DateTimeImmutable('today');
        $score = $this->calculateScore($clubId, $end);
        $events = $this->getEventDetails($clubId, $score['window_start'], $score['window_end']);
        $finance = $this->getFinanceDetails($clubId, $score['window_start'], $score['window_end']);

        return [
            'club' => $club,
            'score' => $score,
            'executives' => $this->resultSet(
                "SELECT user_id, first_name, last_name, role, email, phone_number,
                        profile_picture_url
                 FROM User WHERE club_id = ? AND role IN ('ClubPresident','ClubSecretary','ClubTreasurer')
                   AND status = 'Active'
                 ORDER BY FIELD(role, 'ClubPresident','ClubSecretary','ClubTreasurer')",
                [$clubId]
            ),
            'events' => $events,
            'finance' => $finance,
            'flags' => $this->resultSet(
                "SELECT hf.*, CONCAT_WS(' ', raiser.first_name, raiser.last_name) AS raised_by_name,
                        raiser.role AS raised_by_role
                 FROM ClubHealthFlag hf
                 LEFT JOIN User raiser ON raiser.user_id = hf.raised_by
                 WHERE hf.club_id = ? ORDER BY hf.raised_at DESC",
                [$clubId]
            ),
            'history' => $this->resultSet(
                "SELECT score_month, event_score, finance_score, attendance_score, overall_score, health_status
                 FROM ClubHealthSnapshot WHERE club_id = ? ORDER BY score_month DESC LIMIT 6",
                [$clubId]
            ),
        ];
    }

    public function getZonalFlagCategories(string $role): array {
        $categoriesByRole = [
            'ZonalTreasurer' => ['FinancialConcern'],
            'ZonalSecretary' => ['EventAttendanceConcern'],
            'ZonalCoordinator' => [
                'FinancialConcern',
                'EventAttendanceConcern',
                'GovernanceConcern',
            ],
        ];

        return $categoriesByRole[$role] ?? [];
    }

    public function raiseZoneFlag(
        int $zonalId,
        int $clubId,
        int $userId,
        string $role,
        string $requestedCategory,
        string $reason
    ): void {
        $allowedCategories = $this->getZonalFlagCategories($role);
        if (!$allowedCategories) throw new RuntimeException('Your role cannot raise a club health concern.');
        if ($requestedCategory === '' && count($allowedCategories) === 1) {
            $requestedCategory = $allowedCategories[0];
        }
        if (!in_array($requestedCategory, $allowedCategories, true)) {
            throw new RuntimeException('Select a permitted concern type.');
        }
        $club = $this->single(
            "SELECT c.club_id, c.club_name FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             WHERE c.club_id = ? AND d.zonal_id = ? AND c.status IN ('Active','Flagged')",
            [$clubId, $zonalId]
        );
        if (!$club) throw new RuntimeException('The selected club is outside your zone.');
        $reason = trim($reason);
        $reasonLength = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if ($reasonLength < 10 || $reasonLength > 1000) throw new InvalidArgumentException('Provide a clear reason between 10 and 1000 characters.');

        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                "INSERT INTO ClubHealthFlag (club_id, flag_category, source, reason, status, raised_by)
                 VALUES (?, ?, 'Manual', ?, 'Open', ?)"
            );
            $insert->execute([$clubId, $requestedCategory, $reason, $userId]);
            $flagId = (int) $pdo->lastInsertId();
            $pdo->prepare("UPDATE Club SET flagged = 1 WHERE club_id = ?")->execute([$clubId]);

            $recipients = $pdo->query("SELECT user_id FROM User WHERE role = 'NYSCAdministrator' AND status = 'Active'")->fetchAll(PDO::FETCH_COLUMN);
            $notify = $pdo->prepare(
                "INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status)
                 VALUES (?, 'ClubHealthFlag', ?, 'ClubHealthFlag', ?, 0)"
            );
            foreach ($recipients as $recipientId) {
                $notify->execute([$recipientId, "A zonal health concern was raised for {$club->club_name}.", $flagId]);
            }
            $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details) VALUES (?, 'ClubHealthFlagRaised', 'ClubHealthFlag', ?, ?)")
                ->execute([$userId, $flagId, "{$requestedCategory} raised for club {$clubId}."]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function exportZoneClubs(int $zonalId, array $scores): array {
        return $this->getZoneClubs($zonalId, $scores);
    }
}
