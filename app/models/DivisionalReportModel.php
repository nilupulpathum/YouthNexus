<?php

class DivisionalReportModel extends Model {
    private const TYPES = [
        'Divisional Financial Summary',
        'Club Fund Allocation Report',
        'Void Request Activity',
        'Club Audit Compliance Report',
        'Asset Transfer Report',
        'Club Health Summary',
    ];

    public function getDivision(int $divisionId) {
        return $this->single('SELECT division_id, division_name FROM Division WHERE division_id = ? LIMIT 1', [$divisionId]);
    }

    public function getCatalog(): array {
        $rows = $this->resultSet(
            "SELECT report_type_id, category, type_name, description
             FROM ReportTypeCatalog WHERE type_name IN (" . implode(',', array_fill(0, count(self::TYPES), '?')) . ")
             ORDER BY category, sort_order, type_name",
            self::TYPES
        );
        $catalog = [];
        foreach ($rows as $row) $catalog[$row->category][] = $row;
        return $catalog;
    }

    public function getReports(int $divisionId): array {
        return $this->resultSet(
            "SELECT r.report_id, r.date_range_start, r.date_range_end, r.format, r.generated_at,
                    rtc.category, rtc.type_name, CONCAT_WS(' ', u.first_name, u.last_name) AS generated_by_name
             FROM Report r
             INNER JOIN ReportTypeCatalog rtc ON rtc.report_type_id = r.report_type_id
             LEFT JOIN User u ON u.user_id = r.generated_by
             WHERE r.scope_level = 'Divisional' AND r.scope_id = ? AND r.status = 'Active'
               AND rtc.type_name IN (" . implode(',', array_fill(0, count(self::TYPES), '?')) . ")
             ORDER BY r.generated_at DESC, r.report_id DESC",
            array_merge([$divisionId], self::TYPES)
        );
    }

    public function getSummary(array $reports): array {
        $month = date('Y-m');
        $financial = 0;
        $compliance = 0;
        $thisMonth = 0;
        foreach ($reports as $report) {
            if (substr((string) $report->generated_at, 0, 7) === $month) $thisMonth++;
            if ($report->category === 'Financial') $financial++;
            if (in_array($report->category, ['Club Health', 'Assets'], true) || str_contains($report->type_name, 'Audit')) $compliance++;
        }
        return ['total' => count($reports), 'month' => $thisMonth, 'financial' => $financial, 'compliance' => $compliance];
    }

    public function createReport(int $divisionId, int $userId, int $typeId, string $start, string $end, string $format): int {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $type = $this->single(
                "SELECT report_type_id, type_name FROM ReportTypeCatalog
                 WHERE report_type_id = ? AND type_name IN (" . implode(',', array_fill(0, count(self::TYPES), '?')) . ")",
                array_merge([$typeId], self::TYPES)
            );
            if (!$type) throw new InvalidArgumentException('Select a valid divisional report type.');

            $snapshot = $this->buildReport($divisionId, (object) [
                'type_name' => $type->type_name,
                'date_range_start' => $start,
                'date_range_end' => $end,
            ]);
            $snapshotJson = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $statement = $pdo->prepare(
                "INSERT INTO Report
                    (report_type_id, scope_level, scope_id, date_range_start, date_range_end,
                     format, data_snapshot, status, generated_by)
                 VALUES (?, 'Divisional', ?, ?, ?, ?, ?, 'Active', ?)"
            );
            $statement->execute([$typeId, $divisionId, $start, $end, $format, $snapshotJson, $userId]);
            $reportId = (int) $pdo->lastInsertId();
            $pdo->commit();
            return $reportId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function getReport(int $divisionId, int $reportId) {
        return $this->single(
            "SELECT r.*, rtc.category, rtc.type_name, rtc.description,
                    CONCAT_WS(' ', u.first_name, u.last_name) AS generated_by_name
             FROM Report r INNER JOIN ReportTypeCatalog rtc ON rtc.report_type_id = r.report_type_id
             LEFT JOIN User u ON u.user_id = r.generated_by
             WHERE r.report_id = ? AND r.scope_level = 'Divisional' AND r.scope_id = ?
               AND r.status = 'Active' LIMIT 1",
            [$reportId, $divisionId]
        );
    }

    public function archiveReport(int $divisionId, int $reportId): bool {
        $stmt = $this->query(
            "UPDATE Report SET status = 'Archived'
             WHERE report_id = ? AND scope_level = 'Divisional' AND scope_id = ? AND status = 'Active'",
            [$reportId, $divisionId]
        );
        return $stmt->rowCount() === 1;
    }

    public function buildReport(int $divisionId, object $report): array {
        $start = (string) $report->date_range_start;
        $end = (string) $report->date_range_end;
        return match ($report->type_name) {
            'Divisional Financial Summary' => $this->financial($divisionId, $start, $end),
            'Club Fund Allocation Report' => $this->allocations($divisionId, $start, $end),
            'Void Request Activity' => $this->voids($divisionId, $start, $end),
            'Club Audit Compliance Report' => $this->audits($divisionId, $start, $end),
            'Asset Transfer Report' => $this->assets($divisionId, $start, $end),
            'Club Health Summary' => $this->health($divisionId, $start, $end),
            default => ['kpis' => [], 'columns' => [], 'rows' => []],
        };
    }

    public function getReportData(int $divisionId, object $report): array {
        $snapshot = trim((string) ($report->data_snapshot ?? ''));
        if ($snapshot !== '') {
            try {
                $decoded = json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)
                    && isset($decoded['kpis'], $decoded['columns'], $decoded['rows'])
                    && is_array($decoded['kpis']) && is_array($decoded['columns']) && is_array($decoded['rows'])) {
                    return $decoded;
                }
            } catch (JsonException $exception) {
                // Older or damaged snapshots fall back to a scoped database rebuild.
            }
        }
        return $this->buildReport($divisionId, $report);
    }

    private function financial(int $divisionId, string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT COALESCE(le.category, 'Uncategorised') AS category,
                    SUM(CASE WHEN le.type = 'Income' THEN le.amount ELSE 0 END) AS income,
                    SUM(CASE WHEN le.type = 'Expense' THEN le.amount ELSE 0 END) AS expenses,
                    SUM(le.reconciled = 1) AS reconciled, COUNT(*) AS entries
             FROM Ledger l INNER JOIN LedgerEntry le ON le.ledger_id = l.ledger_id
             WHERE l.owner_type = 'Division' AND l.owner_id = ? AND le.status = 'Approved'
               AND le.date BETWEEN ? AND ? GROUP BY COALESCE(le.category, 'Uncategorised') ORDER BY category",
            [$divisionId, $start, $end]
        );
        $data = []; $income = 0.0; $expenses = 0.0; $entries = 0; $reconciled = 0;
        foreach ($rows as $row) {
            $income += (float) $row->income; $expenses += (float) $row->expenses;
            $entries += (int) $row->entries; $reconciled += (int) $row->reconciled;
            $data[] = ['category' => $row->category, 'income' => $this->money($row->income), 'expenses' => $this->money($row->expenses), 'entries' => $row->entries, 'reconciled' => $row->reconciled];
        }
        return $this->pack([
            ['label' => 'Total income', 'value' => $this->money($income)],
            ['label' => 'Total expenses', 'value' => $this->money($expenses)],
            ['label' => 'Net movement', 'value' => $this->money($income - $expenses)],
            ['label' => 'Reconciled entries', 'value' => $entries ? round($reconciled * 100 / $entries) . '%' : '100%'],
        ], ['category' => 'Category', 'income' => 'Income', 'expenses' => 'Expenses', 'entries' => 'Entries', 'reconciled' => 'Reconciled'], $data);
    }

    private function allocations(int $divisionId, string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT fa.reference_no, c.club_name, fa.fund_category, fa.amount, fa.transfer_date, fa.status,
                    fa.disbursement_method, fa.purpose_description
             FROM FundAllocation fa INNER JOIN Club c ON c.club_id = fa.to_id AND c.division_id = ?
             WHERE fa.from_level = 'Divisional' AND fa.from_id = ? AND fa.to_level = 'Club'
               AND fa.transfer_date BETWEEN ? AND ? ORDER BY fa.transfer_date DESC, fa.allocation_id DESC",
            [$divisionId, $divisionId, $start, $end]
        );
        $data = []; $total = 0.0; $completed = 0; $pending = 0;
        foreach ($rows as $row) {
            $total += (float) $row->amount; if ($row->status === 'Completed') $completed++; if ($row->status === 'PendingApproval') $pending++;
            $data[] = ['reference' => $row->reference_no, 'club' => $row->club_name, 'category' => $row->fund_category, 'amount' => $this->money($row->amount), 'date' => $row->transfer_date, 'method' => $row->disbursement_method, 'status' => $row->status];
        }
        return $this->pack([
            ['label' => 'Allocated amount', 'value' => $this->money($total)],
            ['label' => 'Allocation records', 'value' => count($rows)],
            ['label' => 'Completed', 'value' => $completed],
            ['label' => 'Pending approval', 'value' => $pending],
        ], ['reference' => 'Reference', 'club' => 'Club', 'category' => 'Fund category', 'amount' => 'Amount', 'date' => 'Date', 'method' => 'Method', 'status' => 'Status'], $data);
    }

    private function voids(int $divisionId, string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT vr.void_request_id, c.club_name, le.description, le.amount, vr.scope_direction,
                    vr.reason, vr.status, vr.requested_at, vr.decided_at
             FROM VoidRequest vr INNER JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             INNER JOIN Ledger l ON l.ledger_id = le.ledger_id
             LEFT JOIN Club c ON l.owner_type = 'Club' AND c.club_id = l.owner_id
             WHERE ((l.owner_type = 'Division' AND l.owner_id = ? AND vr.scope_direction = 'DivisionToZonal')
                OR (l.owner_type = 'Club' AND c.division_id = ? AND vr.scope_direction = 'ClubToDivision'))
               AND DATE(vr.requested_at) BETWEEN ? AND ? ORDER BY vr.requested_at DESC",
            [$divisionId, $divisionId, $start, $end]
        );
        $data = []; $approved = 0; $pending = 0; $rejected = 0;
        foreach ($rows as $row) {
            if ($row->status === 'Approved') $approved++; elseif ($row->status === 'Pending') $pending++; else $rejected++;
            $data[] = ['request' => 'VR-' . str_pad((string) $row->void_request_id, 4, '0', STR_PAD_LEFT), 'club' => $row->club_name ?: 'Division ledger', 'description' => $row->description, 'amount' => $this->money($row->amount), 'direction' => $row->scope_direction === 'ClubToDivision' ? 'Club to division' : 'Division to zonal', 'requested' => substr($row->requested_at, 0, 10), 'status' => $row->status];
        }
        return $this->pack([
            ['label' => 'Void requests', 'value' => count($rows)], ['label' => 'Approved', 'value' => $approved],
            ['label' => 'Pending', 'value' => $pending], ['label' => 'Rejected', 'value' => $rejected],
        ], ['request' => 'Request', 'club' => 'Source', 'description' => 'Ledger entry', 'amount' => 'Amount', 'direction' => 'Direction', 'requested' => 'Requested', 'status' => 'Status'], $data);
    }

    private function audits(int $divisionId, string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT a.audit_id, c.club_name, a.audit_type, a.period_start, a.period_end, a.audit_status,
                    a.math_check_status, COUNT(rf.red_flag_id) AS findings,
                    SUM(rf.status IN ('Open','ClarificationRequested','Unresolved')) AS open_findings
             FROM Audit a INNER JOIN Club c ON a.scope_level = 'Club' AND a.scope_id = c.club_id
             LEFT JOIN RedFlag rf ON rf.audit_id = a.audit_id
             WHERE c.division_id = ? AND COALESCE(a.period_end, DATE(a.initiated_at)) BETWEEN ? AND ?
             GROUP BY a.audit_id, c.club_name ORDER BY COALESCE(a.period_end, DATE(a.initiated_at)) DESC",
            [$divisionId, $start, $end]
        );
        $data = []; $completed = 0; $open = 0; $findings = 0;
        foreach ($rows as $row) {
            if ($row->audit_status === 'Completed') $completed++; $open += (int) $row->open_findings; $findings += (int) $row->findings;
            $data[] = ['audit' => 'AUD-' . str_pad((string) $row->audit_id, 4, '0', STR_PAD_LEFT), 'club' => $row->club_name, 'cycle' => $row->audit_type, 'period' => ($row->period_start ?: '-') . ' to ' . ($row->period_end ?: '-'), 'math' => $row->math_check_status, 'findings' => $row->findings, 'open' => $row->open_findings, 'status' => $row->audit_status];
        }
        return $this->pack([
            ['label' => 'Audits in period', 'value' => count($rows)], ['label' => 'Completed', 'value' => $completed],
            ['label' => 'Total findings', 'value' => $findings], ['label' => 'Open findings', 'value' => $open],
        ], ['audit' => 'Audit', 'club' => 'Club', 'cycle' => 'Cycle', 'period' => 'Period', 'math' => 'Math check', 'findings' => 'Findings', 'open' => 'Open', 'status' => 'Status'], $data);
    }

    private function assets(int $divisionId, string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT t.transfer_id, c.club_name, item.item_name, item.category, t.quantity,
                    t.transfer_date, t.status, t.notes
             FROM AssetTransfer t INNER JOIN Club c ON c.club_id = t.to_owner_id AND c.division_id = ?
             INNER JOIN AssetCatalogItem item ON item.catalog_item_id = t.catalog_item_id
             WHERE t.from_owner_level = 'Divisional' AND t.from_owner_id = ? AND t.to_owner_level = 'Club'
               AND t.transfer_date BETWEEN ? AND ? ORDER BY t.transfer_date DESC",
            [$divisionId, $divisionId, $start, $end]
        );
        $data = []; $units = 0; $clubs = [];
        foreach ($rows as $row) {
            $units += (int) $row->quantity; $clubs[$row->club_name] = true;
            $data[] = ['transfer' => 'TRF-' . str_pad((string) $row->transfer_id, 4, '0', STR_PAD_LEFT), 'club' => $row->club_name, 'item' => $row->item_name, 'category' => $row->category, 'quantity' => $row->quantity, 'date' => $row->transfer_date, 'status' => $row->status, 'notes' => $row->notes ?: '-'];
        }
        return $this->pack([
            ['label' => 'Transfers', 'value' => count($rows)], ['label' => 'Units transferred', 'value' => $units],
            ['label' => 'Recipient clubs', 'value' => count($clubs)], ['label' => 'Completed', 'value' => count(array_filter($rows, fn($r) => $r->status === 'Completed'))],
        ], ['transfer' => 'Transfer', 'club' => 'Club', 'item' => 'Item', 'category' => 'Category', 'quantity' => 'Quantity', 'date' => 'Date', 'status' => 'Status', 'notes' => 'Notes'], $data);
    }

    private function health(int $divisionId, string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT c.club_name, s.score_month, s.event_score, s.finance_score, s.attendance_score,
                    s.overall_score, s.health_status, s.calculated_at
             FROM ClubHealthSnapshot s INNER JOIN Club c ON c.club_id = s.club_id
             WHERE c.division_id = ? AND s.score_month BETWEEN DATE_FORMAT(?, '%Y-%m-01') AND DATE_FORMAT(?, '%Y-%m-01')
               AND s.snapshot_id = (SELECT s2.snapshot_id FROM ClubHealthSnapshot s2 WHERE s2.club_id = s.club_id
                    AND s2.score_month BETWEEN DATE_FORMAT(?, '%Y-%m-01') AND DATE_FORMAT(?, '%Y-%m-01')
                    ORDER BY s2.score_month DESC LIMIT 1)
             ORDER BY s.overall_score DESC, c.club_name",
            [$divisionId, $start, $end, $start, $end]
        );
        $data = []; $green = 0; $yellow = 0; $red = 0; $sum = 0.0;
        foreach ($rows as $row) {
            $green += $row->health_status === 'Green' ? 1 : 0; $yellow += $row->health_status === 'Yellow' ? 1 : 0; $red += $row->health_status === 'Red' ? 1 : 0; $sum += (float) $row->overall_score;
            $data[] = ['club' => $row->club_name, 'month' => $row->score_month, 'events' => $row->event_score, 'finance' => $row->finance_score, 'attendance' => $row->attendance_score, 'overall' => $row->overall_score, 'status' => $row->health_status];
        }
        return $this->pack([
            ['label' => 'Clubs assessed', 'value' => count($rows)], ['label' => 'Average score', 'value' => count($rows) ? round($sum / count($rows), 1) : 0],
            ['label' => 'Healthy clubs', 'value' => $green], ['label' => 'Clubs requiring attention', 'value' => $yellow + $red],
        ], ['club' => 'Club', 'month' => 'Score month', 'events' => 'Event score', 'finance' => 'Finance score', 'attendance' => 'Attendance score', 'overall' => 'Overall score', 'status' => 'Status'], $data);
    }

    private function pack(array $kpis, array $columns, array $rows): array {
        return ['kpis' => $kpis, 'columns' => $columns, 'rows' => $rows];
    }

    private function money($amount): string {
        return 'Rs. ' . number_format((float) $amount, 2);
    }
}
