<?php

/**
 * Zonal reports (D13). Catalog from ReportTypeCatalog; generated rows in
 * Report with scope_level = 'Zonal'. Snapshots are built from live zone
 * aggregates at generation time and rendered as JSON.
 */
class ZoneReportModel extends Model {

    public function getCatalog(): array {
        $rows = $this->resultSet(
            "SELECT report_type_id, category, type_name, description
             FROM ReportTypeCatalog ORDER BY category, sort_order, type_name"
        );
        $catalog = [];
        foreach ($rows as $row) {
            $catalog[$row->category][] = $row;
        }
        return $catalog;
    }

    public function getType(int $typeId) {
        return $this->single(
            "SELECT report_type_id, category, type_name, description
             FROM ReportTypeCatalog WHERE report_type_id = ? LIMIT 1",
            [$typeId]
        );
    }

    public function getReports(int $zonalId): array {
        return $this->resultSet(
            "SELECT r.report_id, r.date_range_start, r.date_range_end, r.format,
                    r.generated_at, r.status,
                    rtc.category, rtc.type_name,
                    CONCAT_WS(' ', u.first_name, u.last_name) AS generated_by_name
             FROM Report r
             INNER JOIN ReportTypeCatalog rtc ON rtc.report_type_id = r.report_type_id
             LEFT JOIN User u ON u.user_id = r.generated_by
             WHERE r.scope_level = 'Zonal' AND r.scope_id = ? AND r.status = 'Active'
             ORDER BY r.generated_at DESC, r.report_id DESC",
            [$zonalId]
        );
    }

    public function getReport(int $zonalId, int $reportId) {
        return $this->single(
            "SELECT r.*, rtc.category, rtc.type_name, rtc.description,
                    CONCAT_WS(' ', u.first_name, u.last_name) AS generated_by_name
             FROM Report r
             INNER JOIN ReportTypeCatalog rtc ON rtc.report_type_id = r.report_type_id
             LEFT JOIN User u ON u.user_id = r.generated_by
             WHERE r.report_id = ? AND r.scope_level = 'Zonal' AND r.scope_id = ?
               AND r.status = 'Active' LIMIT 1",
            [$reportId, $zonalId]
        );
    }

    public function createReport(int $zonalId, int $userId, int $typeId, string $start, string $end, string $format): int {
        $type = $this->getType($typeId);
        if (!$type) {
            throw new InvalidArgumentException('Select a valid report type.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) || $end < $start) {
            throw new InvalidArgumentException('Select a valid date range.');
        }
        if (!in_array($format, ['PDF', 'CSV', 'OnScreen'], true)) {
            $format = 'OnScreen';
        }
        $snapshot = $this->buildSnapshot($zonalId, $type->category, $start, $end);
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO Report
                (report_type_id, scope_level, scope_id, date_range_start, date_range_end,
                 format, data_snapshot, status, generated_by)
             VALUES (?, 'Zonal', ?, ?, ?, ?, ?, 'Active', ?)"
        );
        $stmt->execute([$typeId, $zonalId, $start, $end, $format, json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), $userId]);
        return (int) $pdo->lastInsertId();
    }

    public function buildSnapshot(int $zonalId, string $category, string $start, string $end): array {
        switch ($category) {
            case 'Financial':
                return $this->financialSnapshot($zonalId, $start, $end);
            case 'Events':
                return $this->eventsSnapshot($zonalId, $start, $end);
            case 'Attendance':
                return $this->attendanceSnapshot($zonalId, $start, $end);
            case 'Club Health':
                return $this->healthSnapshot($zonalId, $start, $end);
            case 'Assets':
                return $this->assetsSnapshot($zonalId, $start, $end);
            case 'User Interactions':
                return $this->usersSnapshot($zonalId, $start, $end);
            default:
                return $this->financialSnapshot($zonalId, $start, $end);
        }
    }

    private function divisions(int $zonalId): array {
        return $this->resultSet(
            "SELECT division_id, division_name FROM Division
             WHERE zonal_id = ? ORDER BY division_name",
            [$zonalId]
        );
    }

    private function financialSnapshot(int $zonalId, string $start, string $end): array {
        $rows = [];
        foreach ($this->divisions($zonalId) as $d) {
            $divId = (int) $d->division_id;
            $alloc = $this->single(
                "SELECT COALESCE(SUM(amount), 0) AS total FROM FundAllocation
                 WHERE to_level = 'Divisional' AND to_id = ? AND status = 'Completed'
                   AND transfer_date BETWEEN ? AND ?",
                [$divId, $start, $end]
            );
            $led = $this->single(
                "SELECT COALESCE(SUM(CASE WHEN le.type = 'Income' AND le.status = 'Approved' THEN le.amount ELSE 0 END), 0) AS income,
                        COALESCE(SUM(CASE WHEN le.type = 'Expense' AND le.status = 'Approved' THEN le.amount ELSE 0 END), 0) AS expenses
                 FROM Ledger l LEFT JOIN LedgerEntry le ON le.ledger_id = l.ledger_id
                 WHERE l.owner_type = 'Division' AND l.owner_id = ?",
                [$divId]
            );
            $rows[] = [
                'Division' => $d->division_name,
                'Allocated' => (float) ($alloc->total ?? 0),
                'Income' => (float) ($led->income ?? 0),
                'Expenses' => (float) ($led->expenses ?? 0),
            ];
        }
        return ['title' => 'Zonal financial summary', 'range' => [$start, $end], 'columns' => ['Division', 'Allocated', 'Income', 'Expenses'], 'rows' => $rows];
    }

    private function eventsSnapshot(int $zonalId, string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT e.title, e.event_type, e.status, e.start_datetime,
                    COALESCE(c.club_name, d.division_name, z.zonal_name, '—') AS organizer
             FROM Event e
             LEFT JOIN Club c ON c.club_id = e.organizer_club_id
             LEFT JOIN Division d ON d.division_id = e.organizer_division_id
             LEFT JOIN Zone z ON z.zonal_id = e.organizer_zonal_id
             WHERE (e.organizer_zonal_id = ?
                    OR e.organizer_division_id IN (SELECT division_id FROM Division WHERE zonal_id = ?)
                    OR c.division_id IN (SELECT division_id FROM Division WHERE zonal_id = ?))
               AND DATE(e.start_datetime) BETWEEN ? AND ?
             ORDER BY e.start_datetime DESC",
            [$zonalId, $zonalId, $zonalId, $start, $end]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['Event' => $r->title, 'Type' => $r->event_type, 'Status' => $r->status, 'Date' => substr((string) $r->start_datetime, 0, 10), 'Organizer' => $r->organizer];
        }
        return ['title' => 'Zonal events summary', 'range' => [$start, $end], 'columns' => ['Event', 'Type', 'Status', 'Date', 'Organizer'], 'rows' => $out];
    }

    private function attendanceSnapshot(int $zonalId, string $start, string $end): array {
        $rows = [];
        foreach ($this->divisions($zonalId) as $d) {
            $divId = (int) $d->division_id;
            $row = $this->single(
                "SELECT COALESCE(SUM(a.status = 'Present'), 0) AS present_count,
                        COUNT(a.attendance_id) AS recorded_count,
                        COUNT(DISTINCT e.event_id) AS sessions
                 FROM Event e
                 INNER JOIN Attendance a ON a.event_id = e.event_id
                 LEFT JOIN Club c ON c.club_id = e.organizer_club_id
                 WHERE (e.organizer_division_id = ? OR c.division_id = ?)
                   AND DATE(e.start_datetime) BETWEEN ? AND ?",
                [$divId, $divId, $start, $end]
            );
            $recorded = (int) ($row->recorded_count ?? 0);
            $rows[] = [
                'Division' => $d->division_name,
                'Sessions' => (int) ($row->sessions ?? 0),
                'Present' => (int) ($row->present_count ?? 0),
                'Recorded' => $recorded,
                'Rate' => $recorded > 0 ? round((int) ($row->present_count ?? 0) * 100 / $recorded, 1) . '%' : '—',
            ];
        }
        return ['title' => 'Zonal attendance summary', 'range' => [$start, $end], 'columns' => ['Division', 'Sessions', 'Present', 'Recorded', 'Rate'], 'rows' => $rows];
    }

    private function healthSnapshot(int $zonalId, string $start, string $end): array {
        require_once __DIR__ . '/DivisionalClubHealthModel.php';
        $health = new DivisionalClubHealthModel();
        $out = [];
        $clubs = $this->resultSet(
            "SELECT c.club_id, c.club_name, d.division_name FROM Club c
             INNER JOIN Division d ON d.division_id = c.division_id
             WHERE d.zonal_id = ? AND c.status IN ('Active', 'Flagged')
             ORDER BY c.club_name",
            [$zonalId]
        );
        foreach ($clubs as $c) {
            $score = $health->scoreClub((int) $c->club_id);
            $out[] = [
                'Club' => $c->club_name,
                'Division' => $c->division_name,
                'Score' => $score['overall_score'],
                'Band' => $score['health_status'],
            ];
        }
        return ['title' => 'Zonal club health summary', 'range' => [$start, $end], 'columns' => ['Club', 'Division', 'Score', 'Band'], 'rows' => $out];
    }

    private function assetsSnapshot(int $zonalId, string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT c.category, COALESCE(SUM(s.quantity), 0) AS units, COUNT(*) AS lines
             FROM AssetStock s
             INNER JOIN AssetCatalogItem c ON c.catalog_item_id = s.catalog_item_id
             WHERE (s.owner_level = 'Zonal' AND s.owner_id = ?)
                OR (s.owner_level = 'Club' AND s.owner_id IN (SELECT club_id FROM Club WHERE division_id IN (SELECT division_id FROM Division WHERE zonal_id = ?)))
             GROUP BY c.category ORDER BY c.category",
            [$zonalId, $zonalId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['Category' => $r->category, 'Units' => (int) $r->units, 'Lines' => (int) $r->lines];
        }
        return ['title' => 'Zonal asset inventory summary', 'range' => [$start, $end], 'columns' => ['Category', 'Units', 'Lines'], 'rows' => $out];
    }

    private function usersSnapshot(int $zonalId, string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT u.role, COUNT(*) AS total,
                    COALESCE(SUM(u.last_login_at IS NOT NULL), 0) AS ever_logged_in
             FROM User u
             LEFT JOIN Club c ON c.club_id = u.club_id
             LEFT JOIN Division d ON d.division_id = COALESCE(u.division_id, c.division_id)
             WHERE COALESCE(u.zonal_id, d.zonal_id) = ?
             GROUP BY u.role ORDER BY total DESC",
            [$zonalId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['Role' => $r->role, 'Users' => (int) $r->total, 'Ever logged in' => (int) $r->ever_logged_in];
        }
        return ['title' => 'Zonal user summary', 'range' => [$start, $end], 'columns' => ['Role', 'Users', 'Ever logged in'], 'rows' => $out];
    }
}
