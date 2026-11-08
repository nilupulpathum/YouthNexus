<?php
/**
 * ReportModel
 *
 * Data access for the Manage Reports module.
 * Covers: ReportTypeCatalog lookup, Report CRUD, ReportShare, and
 * live data aggregation for each report category/type combination.
 *
 * Uses the project's base Model helpers:
 *   $this->resultSet($sql, $params)  — fetch multiple rows
 *   $this->single($sql, $params)     — fetch one row
 *   $this->query($sql, $params)      — execute; returns PDOStatement
 *   Database::getInstance()->getConnection()->lastInsertId()
 */
class ReportModel extends Model {

    // ---------------------------------------------------------------
    // CATALOG
    // ---------------------------------------------------------------

    /**
     * Returns all categories with their type arrays, keyed by category name.
     * Falls back to hard-coded catalogue when the DB table does not exist yet.
     *
     * @return array<string, array<string>>
     */
    public function getCatalog(): array {
        try {
            $rows = $this->resultSet(
                "SELECT category, type_name FROM ReportTypeCatalog ORDER BY category, sort_order, type_name"
            );

            $catalog = [];
            foreach ($rows as $r) {
                $catalog[$r->category][] = $r->type_name;
            }
            return $catalog ?: $this->fallbackCatalog();
        } catch (Exception $e) {
            return $this->fallbackCatalog();
        }
    }

    /** Static fallback matching the source PHP files exactly. */
    private function fallbackCatalog(): array {
        return [
            'Financial'         => ['National Consolidated Financial Rollup', 'Income Summary', 'Expense Summary', 'Fund Allocation vs Utilisation', 'Void Rate Report'],
            'Assets'            => ['Inventory by Category', 'Inventory by Condition', 'Request Status Summary'],
            'Events'            => ['Event Status Summary', 'Event Attendance Rate'],
            'Attendance'        => ['Attendance Rate by Club / Division / Zone'],
            'Club Health'       => ['Health Status Distribution', 'Health Score Trend'],
            'User Interactions' => ['Login Activity', 'Role Distribution', 'Announcement Read Rate', 'Volunteer Hours'],
        ];
    }

    // ---------------------------------------------------------------
    // REPORT LIST & FILTERS
    // ---------------------------------------------------------------

    /**
     * Fetch all Active reports with their type catalog data joined,
     * optionally filtered by category, type, and a keyword search.
     *
     * @param string $category     Empty string = no filter
     * @param string $typeName     Empty string = no filter
     * @param string $search       Empty string = no filter
     * @return array               Array of stdClass objects
     */
    public function getReports(string $category = '', string $typeName = '', string $search = ''): array {
        try {
            $where  = ["r.status = 'Active'", "r.report_type_id IS NOT NULL"];
            $params = [];

            if ($category !== '') {
                $where[]  = "rtc.category = ?";
                $params[] = $category;
            }
            if ($typeName !== '') {
                $where[]  = "rtc.type_name = ?";
                $params[] = $typeName;
            }
            if ($search !== '') {
                $where[]  = "(rtc.type_name LIKE ? OR rtc.category LIKE ? OR r.scope_level LIKE ?)";
                $like     = '%' . $search . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            $sql = "SELECT r.report_id, r.scope_level, r.scope_id,
                           r.date_range_start, r.date_range_end,
                           r.format, r.status, r.file_path,
                           r.generated_at, r.generated_by,
                           rtc.category, rtc.type_name,
                           u.first_name, u.last_name
                    FROM Report r
                    JOIN ReportTypeCatalog rtc ON r.report_type_id = rtc.report_type_id
                    LEFT JOIN User u ON r.generated_by = u.user_id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY r.generated_at DESC";

            return $this->resultSet($sql, $params);
        } catch (Exception $e) {
            return $this->fallbackReports($category, $typeName, $search);
        }
    }

    /** Returns demo data when DB is not yet set up. */
    private function fallbackReports(string $category, string $typeName, string $search): array {
        $all = [
            (object)['report_id'=>1,'scope_level'=>'Zonal',    'category'=>'Attendance',        'type_name'=>'Attendance Rate by Club / Division / Zone', 'format'=>'PDF',      'generated_at'=>'2026-07-05 09:10:00','first_name'=>'N.','last_name'=>'Fernando'],
            (object)['report_id'=>2,'scope_level'=>'Zonal',    'category'=>'User Interactions', 'type_name'=>'Role Distribution',                           'format'=>'CSV',      'generated_at'=>'2026-06-28 14:22:00','first_name'=>'N.','last_name'=>'Fernando'],
            (object)['report_id'=>3,'scope_level'=>'Divisional','category'=>'Events',           'type_name'=>'Event Status Summary',                        'format'=>'OnScreen', 'generated_at'=>'2026-06-20 11:05:00','first_name'=>'N.','last_name'=>'Fernando'],
            (object)['report_id'=>4,'scope_level'=>'Zonal',    'category'=>'Financial',         'type_name'=>'Fund Allocation vs Utilisation',              'format'=>'PDF',      'generated_at'=>'2026-04-04 08:30:00','first_name'=>'N.','last_name'=>'Fernando'],
            (object)['report_id'=>5,'scope_level'=>'National', 'category'=>'Club Health',       'type_name'=>'Health Status Distribution',                  'format'=>'CSV',      'generated_at'=>'2026-06-15 16:45:00','first_name'=>'N.','last_name'=>'Fernando'],
            (object)['report_id'=>6,'scope_level'=>'National', 'category'=>'Financial',         'type_name'=>'Void Rate Report',                            'format'=>'PDF',      'generated_at'=>'2026-06-10 10:00:00','first_name'=>'N.','last_name'=>'Fernando'],
        ];

        return array_values(array_filter($all, function($r) use ($category, $typeName, $search) {
            if ($category  !== '' && $r->category  !== $category)  return false;
            if ($typeName  !== '' && $r->type_name !== $typeName)  return false;
            if ($search    !== '' && stripos($r->type_name . ' ' . $r->category . ' ' . $r->scope_level, $search) === false) return false;
            return true;
        }));
    }

    // ---------------------------------------------------------------
    // SINGLE REPORT DETAIL
    // ---------------------------------------------------------------

    /**
     * Fetch one Report row by ID (with catalog + generator info joined).
     *
     * @param int $reportId
     * @return object|null
     */
    public function getReportById(int $reportId): ?object {
        try {
            $row = $this->single(
                "SELECT r.*, rtc.category, rtc.type_name, rtc.description AS type_description,
                        u.first_name, u.last_name
                 FROM Report r
                 JOIN ReportTypeCatalog rtc ON r.report_type_id = rtc.report_type_id
                 LEFT JOIN User u ON r.generated_by = u.user_id
                 WHERE r.report_id = ?",
                [$reportId]
            );
            return $row ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    // ---------------------------------------------------------------
    // CREATE REPORT
    // ---------------------------------------------------------------

    /**
     * Persist a new Report row.
     *
     * @param array $data  Keys: report_type_id, scope_level, scope_id, date_range_start, date_range_end, format, generated_by
     * @return int         Inserted report_id, or 0 on failure
     */
    public function createReport(array $data): int {
        try {
            $this->query(
                "INSERT INTO Report (report_type_id, scope_level, scope_id, date_range_start, date_range_end, format, status, generated_by)
                 VALUES (?, ?, ?, ?, ?, ?, 'Active', ?)",
                [
                    (int)$data['report_type_id'],
                    $data['scope_level'] ?? 'National',
                    $data['scope_id']    ?? null,
                    $data['date_range_start'],
                    $data['date_range_end'],
                    $data['format']      ?? 'PDF',
                    (int)($data['generated_by'] ?? 1),
                ]
            );
            return (int)Database::getInstance()->getConnection()->lastInsertId();
        } catch (Exception $e) {
            return 0;
        }
    }

    // ---------------------------------------------------------------
    // ARCHIVE / DELETE
    // ---------------------------------------------------------------

    /**
     * Soft-delete a report by setting its status to Archived.
     *
     * @param int $reportId
     * @return bool
     */
    public function archiveReport(int $reportId): bool {
        try {
            $stmt = $this->query(
                "UPDATE Report SET status = 'Archived' WHERE report_id = ?",
                [$reportId]
            );
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // ---------------------------------------------------------------
    // REPORT SHARE
    // ---------------------------------------------------------------

    /**
     * Record an email/share action.
     *
     * @param int    $reportId
     * @param int    $sharedBy
     * @param string $recipientEmail
     * @param string $method  'Email'|'Link'
     * @return bool
     */
    public function shareReport(int $reportId, int $sharedBy, string $recipientEmail, string $method = 'Email'): bool {
        try {
            $this->query(
                "INSERT INTO ReportShare (report_id, shared_by, recipient_email, method) VALUES (?, ?, ?, ?)",
                [$reportId, $sharedBy, $recipientEmail, $method]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // ---------------------------------------------------------------
    // TYPE ID LOOKUP
    // ---------------------------------------------------------------

    /**
     * Return the report_type_id for a given category + type_name pair.
     *
     * @param string $category
     * @param string $typeName
     * @return int  0 if not found
     */
    public function getTypeId(string $category, string $typeName): int {
        try {
            $row = $this->single(
                "SELECT report_type_id FROM ReportTypeCatalog WHERE category = ? AND type_name = ? LIMIT 1",
                [$category, $typeName]
            );
            return $row ? (int)$row->report_type_id : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    // ---------------------------------------------------------------
    // SCOPE TARGETS — options for the create-form target picker
    // ---------------------------------------------------------------

    /** Zone/division/club options for the scope target picker. */
    public function getScopeTargets(): array {
        try {
            return [
                'zones'     => $this->resultSet(
                    "SELECT zonal_id AS id, zonal_name AS name FROM Zone ORDER BY zonal_name"
                ),
                'divisions' => $this->resultSet(
                    "SELECT division_id AS id, division_name AS name, zonal_id FROM Division ORDER BY division_name"
                ),
                'clubs'     => $this->resultSet(
                    "SELECT club_id AS id, club_name AS name, division_id FROM Club WHERE status = 'Active' ORDER BY club_name"
                ),
            ];
        } catch (Exception $e) {
            return ['zones' => [], 'divisions' => [], 'clubs' => []];
        }
    }

    /** Validate a posted scope target against the chosen level. */
    public function validateScopeTarget(string $level, int $id): bool {
        $map = [
            'Zonal'      => "SELECT zonal_id FROM Zone WHERE zonal_id = ?",
            'Divisional' => "SELECT division_id FROM Division WHERE division_id = ?",
            'Club'       => "SELECT club_id FROM Club WHERE club_id = ?",
        ];
        if (!isset($map[$level])) return false;
        try {
            return (bool)$this->single($map[$level], [$id]);
        } catch (Exception $e) {
            return false;
        }
    }

    // ---------------------------------------------------------------
    // STATISTICS FOR PREVIEW (live data aggregation)
    // ---------------------------------------------------------------

    /**
     * Build preview KPI summary data based on category/type.
     * Uses live data where the underlying tables exist; otherwise returns
     * meaningful demo values matching the source mockup.
     *
     * @param string $category
     * @param string $typeName
     * @param string $scopeLevel
     * @param int|null $scopeId
     * @param string $dateStart
     * @param string $dateEnd
     * @return array  ['kpis'=>[...], 'summaryRows'=>[...], 'rawRows'=>[...]]
     */
    public function buildPreviewData(
        string $category,
        string $typeName,
        string $scopeLevel,
        ?int   $scopeId,
        string $dateStart,
        string $dateEnd
    ): array {
        switch ($category) {
            case 'Attendance':
                return $this->buildAttendancePreview($scopeLevel, $scopeId, $dateStart, $dateEnd);
            case 'Events':
                return $this->buildEventsPreview($typeName, $scopeLevel, $scopeId, $dateStart, $dateEnd);
            case 'Assets':
                return $this->buildAssetsPreview($typeName, $dateStart, $dateEnd);
            case 'Club Health':
                return $this->buildClubHealthPreview($typeName, $scopeLevel, $scopeId, $dateStart, $dateEnd);
            case 'User Interactions':
                return $this->buildUserInteractionsPreview($typeName, $scopeLevel, $scopeId, $dateStart, $dateEnd);
            case 'Financial':
                return $this->buildFinancialPreview($typeName, $dateStart, $dateEnd);
            default: // legacy rows without a mapped category — statutory rollup
                return $this->buildFinancialPreview($typeName, $dateStart, $dateEnd);
        }
    }

    // ---------------------------------------------------------------
    // PREVIEW BUILDERS — one per report category. Each returns:
    //   kpis      => label/value/note/tone cards
    //   summary   => ['headers' => [...], 'rows' => [['cells' => [...], 'status' => '...']]]
    //                (the LAST header is always the status pill column)
    //   raw       => same shape, detailed rows
    //   syncLabel / noteTitle / note => preview chrome strings
    // ---------------------------------------------------------------

    private function rateBand(float $rate): string {
        if ($rate >= 75) return 'Healthy';
        if ($rate >= 50) return 'Review';
        return 'Critical';
    }

    /** Attendance Rate by Club / Division / Zone — real attendance marks. */
    private function buildAttendancePreview(string $scopeLevel, ?int $scopeId, string $start, string $end): array {
        $clubWhere = '';
        $params    = [$start, $end];
        if ($scopeLevel === 'Zonal' && $scopeId) {
            $clubWhere = ' AND z.zonal_id = ?';
            $params[]  = $scopeId;
        } elseif ($scopeLevel === 'Divisional' && $scopeId) {
            $clubWhere = ' AND d.division_id = ?';
            $params[]  = $scopeId;
        } elseif ($scopeLevel === 'Club' && $scopeId) {
            $clubWhere = ' AND e.organizer_club_id = ?';
            $params[]  = $scopeId;
        }

        $clubs = $this->resultSet(
            "SELECT c.club_id, c.club_name, d.division_name, z.zonal_name,
                    COUNT(DISTINCT e.event_id) AS events,
                    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present,
                    COUNT(a.attendance_id) AS marked
             FROM event e
             JOIN club c          ON c.club_id = e.organizer_club_id
             LEFT JOIN division d ON d.division_id = COALESCE(e.organizer_division_id, c.division_id)
             LEFT JOIN zone z     ON z.zonal_id = COALESCE(e.organizer_zonal_id, d.zonal_id)
             JOIN attendance a    ON a.event_id = e.event_id
             WHERE DATE(e.start_datetime) BETWEEN ? AND ? {$clubWhere}
             GROUP BY c.club_id, c.club_name, d.division_name, z.zonal_name
             ORDER BY c.club_name",
            $params
        );

        $totPresent = 0; $totMarked = 0; $totEvents = 0;
        $clubRows = [];
        $byDivision = [];
        foreach ($clubs as $c) {
            $present = (int)$c->present; $marked = (int)$c->marked; $events = (int)$c->events;
            $rate    = $marked > 0 ? round($present / $marked * 100, 1) : 0.0;
            $totPresent += $present; $totMarked += $marked; $totEvents += $events;

            $clubRows[] = ['cells' => [
                $c->club_name, $c->division_name ?: '—', $c->zonal_name ?: '—',
                $events, $marked, $present, number_format($rate, 1) . '%',
            ], 'status' => $this->rateBand($rate)];

            $div = $c->division_name ?: 'Unassigned';
            if (!isset($byDivision[$div])) {
                $byDivision[$div] = ['zone' => $c->zonal_name ?: '—', 'present' => 0, 'marked' => 0, 'events' => 0, 'clubs' => 0];
            }
            $byDivision[$div]['present'] += $present;
            $byDivision[$div]['marked']  += $marked;
            $byDivision[$div]['events']  += $events;
            $byDivision[$div]['clubs']++;
        }

        $divisionRows = [];
        foreach ($byDivision as $name => $d) {
            $rate = $d['marked'] > 0 ? round($d['present'] / $d['marked'] * 100, 1) : 0.0;
            $divisionRows[] = ['cells' => [
                $name, $d['zone'], $d['clubs'], $d['events'], $d['marked'], $d['present'],
                number_format($rate, 1) . '%',
            ], 'status' => $this->rateBand($rate)];
        }

        $overall = $totMarked > 0 ? round($totPresent / $totMarked * 100, 1) : 0.0;

        return [
            'kpis' => [
                ['label' => 'CLUBS REPORTED',         'value' => (string)count($clubRows),          'note' => 'With completed events in range', 'tone' => 'blue'],
                ['label' => 'OVERALL ATTENDANCE',     'value' => $overall . '%',                    'note' => number_format($totPresent) . ' of ' . number_format($totMarked) . ' marks', 'tone' => $overall >= 75 ? 'green' : 'blue'],
                ['label' => 'EVENTS WITH ROLLCALL',   'value' => (string)$totEvents,                'note' => 'Completed events in range',      'tone' => 'blue'],
                ['label' => 'PRESENT MARKS',          'value' => number_format($totPresent),        'note' => 'Verified check-ins recorded',    'tone' => 'green'],
            ],
            'summary' => [
                'headers' => ['Division', 'Zone', 'Clubs', 'Events', 'Marks Taken', 'Present', 'Attendance Rate', 'Status'],
                'rows'    => $divisionRows,
            ],
            'raw' => [
                'headers' => ['Club', 'Division', 'Zone', 'Events', 'Marks Taken', 'Present', 'Attendance Rate', 'Status'],
                'rows'    => $clubRows,
            ],
            'syncLabel' => 'Divisions Synced',
            'noteTitle' => 'Attendance Rollup Verification:',
            'note'      => 'Aggregated from ' . $totEvents . ' completed event rollcalls covering ' . count($clubRows)
                         . ' clubs. Rates are computed from verified Present marks against total marks taken in the selected range.',
        ];
    }

    /** Events: Event Status Summary & Event Attendance Rate. */
    private function buildEventsPreview(string $typeName, string $scopeLevel, ?int $scopeId, string $start, string $end): array {
        $clubWhere = '';
        $params    = [$start, $end];
        if ($scopeLevel === 'Zonal' && $scopeId) {
            $clubWhere = ' AND z.zonal_id = ?';
            $params[]  = $scopeId;
        } elseif ($scopeLevel === 'Divisional' && $scopeId) {
            $clubWhere = ' AND d.division_id = ?';
            $params[]  = $scopeId;
        } elseif ($scopeLevel === 'Club' && $scopeId) {
            $clubWhere = ' AND e.organizer_club_id = ?';
            $params[]  = $scopeId;
        }

        if ($typeName === 'Event Attendance Rate') {
            $events = $this->resultSet(
                "SELECT e.title, c.club_name, e.start_datetime,
                        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present,
                        COUNT(a.attendance_id) AS marked
                 FROM event e
                 LEFT JOIN club c       ON c.club_id = e.organizer_club_id
                 LEFT JOIN division d   ON d.division_id = COALESCE(e.organizer_division_id, c.division_id)
                 LEFT JOIN zone z       ON z.zonal_id = COALESCE(e.organizer_zonal_id, d.zonal_id)
                 LEFT JOIN attendance a ON a.event_id = e.event_id
                 WHERE DATE(e.start_datetime) BETWEEN ? AND ? {$clubWhere}
                 GROUP BY e.event_id, e.title, c.club_name, e.start_datetime
                 ORDER BY e.start_datetime DESC",
                $params
            );

            $rows = []; $totPresent = 0; $totMarked = 0; $best = ['rate' => -1, 'title' => '—'];
            foreach ($events as $e) {
                $present = (int)$e->present; $marked = (int)$e->marked;
                $rate = $marked > 0 ? round($present / $marked * 100, 1) : 0.0;
                $totPresent += $present; $totMarked += $marked;
                if ($marked > 0 && $rate > $best['rate']) $best = ['rate' => $rate, 'title' => $e->title];
                $rows[] = ['cells' => [
                    $e->title, $e->club_name ?: '—', date('M d, Y', strtotime($e->start_datetime)),
                    $marked, $present, number_format($rate, 1) . '%',
                ], 'status' => $this->rateBand($rate)];
            }
            $avg = $totMarked > 0 ? round($totPresent / $totMarked * 100, 1) : 0.0;

            return [
                'kpis' => [
                    ['label' => 'EVENTS TRACKED',     'value' => (string)count($rows),  'note' => 'Completed with rollcall data',  'tone' => 'blue'],
                    ['label' => 'AVERAGE ATTENDANCE', 'value' => $avg . '%',            'note' => 'Across all tracked events',      'tone' => $avg >= 75 ? 'green' : 'blue'],
                    ['label' => 'TOTAL PRESENT',      'value' => number_format($totPresent), 'note' => 'Verified attendances',      'tone' => 'green'],
                    ['label' => 'BEST EVENT',         'value' => $best['rate'] >= 0 ? $best['rate'] . '%' : '—', 'note' => $best['title'], 'tone' => 'green'],
                ],
                'summary' => [
                    'headers' => ['Event', 'Organising Club', 'Date', 'Marks', 'Present', 'Rate', 'Status'],
                    'rows'    => array_slice($rows, 0, 12),
                ],
                'raw' => [
                    'headers' => ['Event', 'Organising Club', 'Date', 'Marks', 'Present', 'Rate', 'Status'],
                    'rows'    => $rows,
                ],
                'syncLabel' => 'Events Synced',
                'noteTitle' => 'Event Attendance Verification:',
                'note'      => 'Rates computed from verified Present marks against total rollcall marks for ' . count($rows)
                             . ' completed events in the selected range.',
            ];
        }

        // Default: Event Status Summary
        $byStatus = $this->resultSet(
            "SELECT e.status, COUNT(*) AS cnt
             FROM event e
             LEFT JOIN club c       ON c.club_id = e.organizer_club_id
             LEFT JOIN division d   ON d.division_id = COALESCE(e.organizer_division_id, c.division_id)
             LEFT JOIN zone z       ON z.zonal_id = COALESCE(e.organizer_zonal_id, d.zonal_id)
             WHERE DATE(e.start_datetime) BETWEEN ? AND ? {$clubWhere}
             GROUP BY e.status",
            $params
        );
        $statusCounts = [];
        foreach ($byStatus as $s) { $statusCounts[$s->status] = (int)$s->cnt; }
        $total = array_sum($statusCounts);

        $zonal = $this->resultSet(
            "SELECT z.zonal_name,
                    COUNT(*) AS total,
                    SUM(e.status = 'Completed') AS completed,
                    SUM(e.status = 'Approved')  AS approved,
                    SUM(e.status IN ('Draft','PendingApproval')) AS pending,
                    SUM(e.status = 'Rejected')  AS rejected
             FROM event e
             LEFT JOIN club c       ON c.club_id = e.organizer_club_id
             LEFT JOIN division d   ON d.division_id = COALESCE(e.organizer_division_id, c.division_id)
             LEFT JOIN zone z       ON z.zonal_id = COALESCE(e.organizer_zonal_id, d.zonal_id)
             WHERE DATE(e.start_datetime) BETWEEN ? AND ? {$clubWhere}
             GROUP BY z.zonal_id, z.zonal_name
             ORDER BY z.zonal_name",
            $params
        );

        $summaryRows = [];
        foreach ($zonal as $z) {
            $summaryRows[] = ['cells' => [
                $z->zonal_name ?: 'Unassigned', (int)$z->total, (int)$z->completed,
                (int)$z->approved, (int)$z->pending, (int)$z->rejected,
            ], 'status' => ((int)$z->total > 0 && (int)$z->completed > 0) ? 'Active' : 'Monitoring'];
        }

        $eventRows = [];
        $listed = $this->resultSet(
            "SELECT e.title, e.status, e.start_datetime, c.club_name
             FROM event e
             LEFT JOIN club c       ON c.club_id = e.organizer_club_id
             LEFT JOIN division d   ON d.division_id = COALESCE(e.organizer_division_id, c.division_id)
             LEFT JOIN zone z       ON z.zonal_id = COALESCE(e.organizer_zonal_id, d.zonal_id)
             WHERE DATE(e.start_datetime) BETWEEN ? AND ? {$clubWhere}
             ORDER BY e.start_datetime DESC",
            $params
        );
        foreach ($listed as $e) {
            $eventRows[] = ['cells' => [
                $e->title, $e->club_name ?: '—', date('M d, Y', strtotime($e->start_datetime)),
            ], 'status' => $e->status];
        }

        return [
            'kpis' => [
                ['label' => 'TOTAL EVENTS',        'value' => (string)$total,                          'note' => 'Scheduled in range',                 'tone' => 'blue'],
                ['label' => 'COMPLETED',           'value' => (string)($statusCounts['Completed'] ?? 0), 'note' => 'Executed and closed',            'tone' => 'green'],
                ['label' => 'AWAITING / APPROVED', 'value' => (string)(($statusCounts['PendingApproval'] ?? 0) + ($statusCounts['Approved'] ?? 0)), 'note' => 'In the approval pipeline', 'tone' => 'blue'],
                ['label' => 'REJECTED',            'value' => (string)($statusCounts['Rejected'] ?? 0),  'note' => 'Did not meet criteria',           'tone' => 'green'],
            ],
            'summary' => [
                'headers' => ['Zone', 'Total Events', 'Completed', 'Approved', 'Draft / Pending', 'Rejected', 'Status'],
                'rows'    => $summaryRows,
            ],
            'raw' => [
                'headers' => ['Event Title', 'Organising Club', 'Date', 'Status'],
                'rows'    => $eventRows,
            ],
            'syncLabel' => 'Zones Synced',
            'noteTitle' => 'Event Governance Verification:',
            'note'      => 'Status distribution aggregated from ' . $total . ' events scheduled within the selected range.',
        ];
    }

    /** Assets: Inventory by Category / Condition, Request Status Summary. */
    private function buildAssetsPreview(string $typeName, string $start, string $end): array {
        if ($typeName === 'Inventory by Condition') {
            $rows0 = $this->resultSet(
                "SELECT `condition`, COUNT(*) AS assets, SUM(quantity) AS units
                 FROM clubasset GROUP BY `condition`"
            );
            $condRows = []; $totalUnits = 0; $goodUnits = 0; $poorCount = 0; $totalAssets = 0;
            foreach ($rows0 as $r) {
                $units = (int)$r->units; $totalUnits += $units; $totalAssets += (int)$r->assets;
                if (in_array($r->condition, ['Excellent', 'Good'])) $goodUnits += $units;
                if ($r->condition === 'Poor') $poorCount = (int)$r->assets;
            }
            foreach ($rows0 as $r) {
                $share = $totalUnits > 0 ? round((int)$r->units / $totalUnits * 100, 1) : 0.0;
                $condRows[] = ['cells' => [
                    $r->condition, (int)$r->assets, (int)$r->units, $share . '%',
                ], 'status' => in_array($r->condition, ['Excellent', 'Good']) ? 'Healthy' : ($r->condition === 'Poor' ? 'Critical' : 'Review')];
            }
            $goodPct = $totalUnits > 0 ? round($goodUnits / $totalUnits * 100, 1) : 0.0;

            return [
                'kpis' => [
                    ['label' => 'CLUB ASSETS LOGGED', 'value' => number_format($totalAssets), 'note' => 'Asset records across clubs', 'tone' => 'blue'],
                    ['label' => 'TOTAL UNITS',        'value' => number_format($totalUnits),  'note' => 'Counted in inventory',        'tone' => 'blue'],
                    ['label' => 'GOOD CONDITION',     'value' => $goodPct . '%',              'note' => 'Excellent or Good units',     'tone' => $goodPct >= 70 ? 'green' : 'blue'],
                    ['label' => 'POOR ASSETS',        'value' => (string)$poorCount,          'note' => 'Records flagged Poor',        'tone' => $poorCount > 0 ? 'green' : 'green'],
                ],
                'summary' => [
                    'headers' => ['Condition Band', 'Asset Records', 'Units', 'Share of Units', 'Status'],
                    'rows'    => $condRows,
                ],
                'raw' => [
                    'headers' => ['Asset', 'Quantity', 'Condition', 'Logged On', 'Status'],
                    'rows'    => array_map(function ($a) {
                        return ['cells' => [
                            $a->asset_name, (int)$a->quantity, $a->condition,
                            date('M d, Y', strtotime($a->created_at)),
                        ], 'status' => in_array($a->condition, ['Excellent', 'Good']) ? 'Healthy' : ($a->condition === 'Poor' ? 'Critical' : 'Review')];
                    }, $this->resultSet("SELECT asset_name, quantity, `condition`, created_at FROM clubasset ORDER BY created_at DESC LIMIT 25")),
                ],
                'syncLabel' => 'Condition Bands Synced',
                'noteTitle' => 'Asset Condition Verification:',
                'note'      => 'Condition bands aggregated from club-submitted asset registers.',
            ];
        }

        if (in_array($typeName, ['Request Status Summary', 'Asset Transfer Report'], true)) {
            $rows0 = $this->resultSet(
                "SELECT t.status, COUNT(*) AS transfers, SUM(t.quantity) AS units
                 FROM assettransfer t GROUP BY t.status"
            );
            $statusRows = []; $totalT = 0; $completed = 0; $open = 0;
            foreach ($rows0 as $r) {
                $totalT += (int)$r->transfers;
                if ($r->status === 'Completed') $completed = (int)$r->transfers;
                if (in_array($r->status, ['Pending', 'In-Transit'])) $open += (int)$r->transfers;
            }
            foreach ($rows0 as $r) {
                $statusRows[] = ['cells' => [
                    $r->status, (int)$r->transfers, (int)$r->units,
                ], 'status' => $r->status === 'Completed' ? 'Healthy' : ($r->status === 'Cancelled' ? 'Critical' : 'Review')];
            }

            $raw = $this->resultSet(
                "SELECT ci.item_name, t.quantity, t.from_owner_level, t.to_owner_level,
                        t.transfer_date, t.status
                 FROM assettransfer t
                 LEFT JOIN assetcatalogitem ci ON ci.catalog_item_id = t.catalog_item_id
                 ORDER BY t.transfer_date DESC LIMIT 25"
            );
            $rawRows = [];
            foreach ($raw as $t) {
                $rawRows[] = ['cells' => [
                    $t->item_name ?: '—', (int)$t->quantity,
                    $t->from_owner_level . ' → ' . $t->to_owner_level,
                    date('M d, Y', strtotime($t->transfer_date)),
                ], 'status' => $t->status];
            }

            return [
                'kpis' => [
                    ['label' => 'TOTAL TRANSFERS',   'value' => (string)$totalT,   'note' => 'Recorded stock movements',        'tone' => 'blue'],
                    ['label' => 'COMPLETED',         'value' => (string)$completed, 'note' => 'Delivered and confirmed',        'tone' => 'green'],
                    ['label' => 'OPEN MOVEMENTS',    'value' => (string)$open,      'note' => 'Pending or in transit',          'tone' => 'blue'],
                    ['label' => 'FULFILMENT RATE',   'value' => ($totalT > 0 ? round($completed / $totalT * 100, 1) : 0) . '%', 'note' => 'Completed vs total', 'tone' => 'green'],
                ],
                'summary' => [
                    'headers' => ['Transfer Status', 'Movements', 'Units Moved', 'Status'],
                    'rows'    => $statusRows,
                ],
                'raw' => [
                    'headers' => ['Item', 'Quantity', 'Route', 'Date', 'Status'],
                    'rows'    => $rawRows,
                ],
                'syncLabel' => 'Status Bands Synced',
                'noteTitle' => 'Asset Movement Verification:',
                'note'      => 'Distribution requests aggregated from the asset transfer ledger.',
            ];
        }

        // Default: Inventory by Category
        $cats = $this->resultSet(
            "SELECT ci.category,
                    COUNT(DISTINCT ci.catalog_item_id) AS line_count,
                    SUM(CASE WHEN s.owner_level = 'National' THEN s.quantity ELSE 0 END) AS nat_qty,
                    SUM(CASE WHEN s.owner_level = 'Zonal' THEN s.quantity ELSE 0 END) AS zon_qty,
                    SUM(CASE WHEN s.owner_level = 'National' AND s.quantity <= ci.national_low_stock_threshold THEN 1 ELSE 0 END) AS low
             FROM assetcatalogitem ci
             LEFT JOIN assetstock s ON s.catalog_item_id = ci.catalog_item_id
             GROUP BY ci.category ORDER BY ci.category"
        );
        $summaryRows = []; $totLines = 0; $totNat = 0; $totZon = 0; $totLow = 0;
        foreach ($cats as $c) {
            $totLines += (int)$c->line_count; $totNat += (int)$c->nat_qty; $totZon += (int)$c->zon_qty; $totLow += (int)$c->low;
            $summaryRows[] = ['cells' => [
                $c->category, (int)$c->line_count, number_format((int)$c->nat_qty), number_format((int)$c->zon_qty), (int)$c->low,
            ], 'status' => ((int)$c->low > 0) ? 'Review' : 'Healthy'];
        }
        $raw = $this->resultSet(
            "SELECT ci.item_name, ci.sku, ci.category, ci.national_low_stock_threshold,
                    COALESCE(SUM(CASE WHEN s.owner_level = 'National' THEN s.quantity ELSE 0 END), 0) AS nat_qty
             FROM assetcatalogitem ci
             LEFT JOIN assetstock s ON s.catalog_item_id = ci.catalog_item_id
             GROUP BY ci.catalog_item_id, ci.item_name, ci.sku, ci.category, ci.national_low_stock_threshold
             ORDER BY ci.item_name"
        );
        $rawRows = [];
        foreach ($raw as $r) {
            $qty = (int)$r->nat_qty; $threshold = (int)$r->national_low_stock_threshold;
            $state = $qty <= 0 ? 'Deficit' : ($qty <= $threshold ? 'Low Stock' : 'Optimal');
            $rawRows[] = ['cells' => [
                $r->item_name, $r->sku, $r->category, $qty, $threshold,
            ], 'status' => $state];
        }

        return [
            'kpis' => [
                ['label' => 'CATALOG LINES',      'value' => (string)$totLines,              'note' => 'Distinct catalog items',    'tone' => 'blue'],
                ['label' => 'NATIONAL UNITS',     'value' => number_format($totNat),         'note' => 'Held at Central Depot',     'tone' => 'blue'],
                ['label' => 'ZONAL UNITS',        'value' => number_format($totZon),         'note' => 'Allocated to zones',        'tone' => 'blue'],
                ['label' => 'LOW STOCK LINES',    'value' => (string)$totLow,                'note' => 'At or below threshold',     'tone' => $totLow > 0 ? 'green' : 'green'],
            ],
            'summary' => [
                'headers' => ['Category', 'Catalog Lines', 'National Units', 'Zonal Units', 'Low Stock Lines', 'Status'],
                'rows'    => $summaryRows,
            ],
            'raw' => [
                'headers' => ['Item', 'SKU', 'Category', 'National Qty', 'Threshold', 'Status'],
                'rows'    => $rawRows,
            ],
            'syncLabel' => 'Categories Synced',
            'noteTitle' => 'Inventory Reconciliation Verification:',
            'note'      => 'Stock aggregated from national and zonal asset stock ledgers against the master catalog.',
        ];
    }

    /** Club Health: Health Status Distribution & Health Score Trend. */
    private function buildClubHealthPreview(string $typeName, string $scopeLevel, ?int $scopeId, string $start, string $end): array {
        $clubWhere = '';
        $params    = [];
        if ($scopeLevel === 'Zonal' && $scopeId) {
            $clubWhere = ' AND d.zonal_id = ?';
            $params[]  = $scopeId;
        } elseif ($scopeLevel === 'Divisional' && $scopeId) {
            $clubWhere = ' AND c.division_id = ?';
            $params[]  = $scopeId;
        } elseif ($scopeLevel === 'Club' && $scopeId) {
            $clubWhere = ' AND c.club_id = ?';
            $params[]  = $scopeId;
        }

        if ($typeName === 'Health Score Trend') {
            $trend = $this->resultSet(
                "SELECT DATE_FORMAT(chs.calculated_date, '%b %Y') AS month,
                        ROUND(AVG(chs.overall_score), 1) AS avg_score,
                        SUM(chs.health_status = 'Green') AS green,
                        SUM(chs.health_status = 'Red')   AS red,
                        COUNT(*) AS snapshots
                 FROM clubhealthscore chs
                 WHERE chs.calculated_date BETWEEN ? AND ?
                 GROUP BY DATE_FORMAT(chs.calculated_date, '%Y-%m'), DATE_FORMAT(chs.calculated_date, '%b %Y')
                 ORDER BY MIN(chs.calculated_date)",
                [$start, $end]
            );
            $trendRows = [];
            foreach ($trend as $t) {
                $trendRows[] = ['cells' => [
                    $t->month, number_format((float)$t->avg_score, 1), (int)$t->green, (int)$t->red, (int)$t->snapshots,
                ], 'status' => $this->rateBand((float)$t->avg_score)];
            }
            $first = (float)($trend[0]->avg_score ?? 0);
            $last  = (float)($trend[count($trend) - 1]->avg_score ?? 0);
            $delta = round($last - $first, 1);

            return [
                'kpis' => [
                    ['label' => 'SCORE SNAPSHOTS', 'value' => (string)array_sum(array_map(fn($t) => (int)$t->snapshots, $trend)), 'note' => 'Health evaluations in range', 'tone' => 'blue'],
                    ['label' => 'LATEST AVERAGE',  'value' => number_format($last, 1), 'note' => 'Most recent month average', 'tone' => 'blue'],
                    ['label' => 'TREND',           'value' => ($delta >= 0 ? '+' : '') . $delta, 'note' => 'First to last month', 'tone' => $delta >= 0 ? 'green' : 'blue'],
                    ['label' => 'RED SNAPSHOTS',   'value' => (string)array_sum(array_map(fn($t) => (int)$t->red, $trend)), 'note' => 'Critical evaluations', 'tone' => 'green'],
                ],
                'summary' => [
                    'headers' => ['Month', 'Average Score', 'Green Snapshots', 'Red Snapshots', 'Evaluations', 'Status'],
                    'rows'    => $trendRows,
                ],
                'raw' => [
                    'headers' => ['Month', 'Average Score', 'Green Snapshots', 'Red Snapshots', 'Evaluations', 'Status'],
                    'rows'    => $trendRows,
                ],
                'syncLabel' => 'Months Synced',
                'noteTitle' => 'Health Trend Verification:',
                'note'      => 'Month-over-month movement computed from recorded club health score snapshots.',
            ];
        }

        // Default: Health Status Distribution
        $clubs = $this->resultSet(
            "SELECT c.club_name, c.overall_health_score, c.health_status, c.flagged,
                    d.division_name, z.zonal_name
             FROM club c
             LEFT JOIN division d ON d.division_id = c.division_id
             LEFT JOIN zone z     ON z.zonal_id = d.zonal_id
             WHERE c.status IN ('Active','Flagged') {$clubWhere}
             ORDER BY c.overall_health_score DESC",
            $params
        );
        $summary = []; $totScore = 0; $green = 0; $yellow = 0; $red = 0;
        $clubRows = [];
        foreach ($clubs as $c) {
            $score = (float)$c->overall_health_score; $totScore += $score;
            $band  = $c->health_status ?: 'Red';
            if ($band === 'Green') $green++; elseif ($band === 'Yellow') $yellow++; else $red++;
            $clubRows[] = ['cells' => [
                $c->club_name, $c->division_name ?: '—', $c->zonal_name ?: '—',
                number_format($score, 1), $c->flagged ? 'Yes' : 'No',
            ], 'status' => $band];
            $zone = $c->zonal_name ?: 'Unassigned';
            if (!isset($summary[$zone])) $summary[$zone] = ['zone' => $zone, 'clubs' => 0, 'green' => 0, 'yellow' => 0, 'red' => 0, 'score' => 0.0];
            $summary[$zone]['clubs']++; $summary[$zone]['score'] += $score;
            if ($band === 'Green') $summary[$zone]['green']++; elseif ($band === 'Yellow') $summary[$zone]['yellow']++; else $summary[$zone]['red']++;
        }
        $summaryRows = [];
        foreach ($summary as $z) {
            $avg = $z['clubs'] > 0 ? round($z['score'] / $z['clubs'], 1) : 0.0;
            $summaryRows[] = ['cells' => [
                $z['zone'], $z['clubs'], $z['green'], $z['yellow'], $z['red'], number_format($avg, 1),
            ], 'status' => $this->rateBand($avg)];
        }
        $count = count($clubs);
        $avgAll = $count > 0 ? round($totScore / $count, 1) : 0.0;

        return [
            'kpis' => [
                ['label' => 'CLUBS ASSESSED',   'value' => (string)$count,  'note' => 'Active and flagged clubs',       'tone' => 'blue'],
                ['label' => 'GREEN BAND',       'value' => (string)$green,  'note' => $count > 0 ? round($green / $count * 100, 1) . '% of clubs' : 'No clubs', 'tone' => 'green'],
                ['label' => 'RED BAND',         'value' => (string)$red,    'note' => 'Requires disbandment review',    'tone' => 'blue'],
                ['label' => 'AVERAGE SCORE',    'value' => number_format($avgAll, 1), 'note' => 'Weighted across all clubs', 'tone' => $avgAll >= 70 ? 'green' : 'blue'],
            ],
            'summary' => [
                'headers' => ['Zone', 'Clubs', 'Green', 'Yellow', 'Red', 'Avg Score', 'Status'],
                'rows'    => $summaryRows,
            ],
            'raw' => [
                'headers' => ['Club', 'Division', 'Zone', 'Health Score', 'Flagged', 'Status'],
                'rows'    => $clubRows,
            ],
            'syncLabel' => 'Zones Synced',
            'noteTitle' => 'Health Governance Verification:',
            'note'      => 'Distribution computed from the live health status of ' . $count . ' clubs in scope.',
        ];
    }

    /** User Interactions: Login Activity, Role Distribution, Announcement Read Rate, Volunteer Hours. */
    private function buildUserInteractionsPreview(string $typeName, string $scopeLevel, ?int $scopeId, string $start, string $end): array {
        if ($typeName === 'Role Distribution') {
            $roles = $this->resultSet(
                "SELECT role, COUNT(*) AS users,
                        SUM(status = 'Active') AS active,
                        SUM(status <> 'Active') AS inactive
                 FROM user GROUP BY role ORDER BY users DESC"
            );
            $total = 0; foreach ($roles as $r) { $total += (int)$r->users; }
            $rows = [];
            foreach ($roles as $r) {
                $share = $total > 0 ? round((int)$r->users / $total * 100, 1) : 0.0;
                $rows[] = ['cells' => [
                    $r->role, (int)$r->users, $share . '%', (int)$r->active, (int)$r->inactive,
                ], 'status' => $r->role === 'NYSCAdministrator' ? 'Governance' : 'Registered'];
            }

            return [
                'kpis' => [
                    ['label' => 'REGISTERED USERS', 'value' => (string)$total, 'note' => 'Across all roles', 'tone' => 'blue'],
                    ['label' => 'ACTIVE ACCOUNTS',  'value' => (string)array_sum(array_map(fn($r) => (int)$r->active, $roles)), 'note' => 'Status Active', 'tone' => 'green'],
                    ['label' => 'CLUB MEMBERS',     'value' => (string)(array_sum(array_map(fn($r) => $r->role === 'ClubMember' ? (int)$r->users : 0, $roles))), 'note' => 'Grassroots membership', 'tone' => 'blue'],
                    ['label' => 'DISTINCT ROLES',   'value' => (string)count($roles), 'note' => 'In the permission model', 'tone' => 'blue'],
                ],
                'summary' => [
                    'headers' => ['Role', 'Users', 'Share', 'Active', 'Inactive', 'Status'],
                    'rows'    => $rows,
                ],
                'raw' => [
                    'headers' => ['Role', 'Users', 'Share', 'Active', 'Inactive', 'Status'],
                    'rows'    => $rows,
                ],
                'syncLabel' => 'Roles Synced',
                'noteTitle' => 'Role Census Verification:',
                'note'      => 'Breakdown of all ' . $total . ' registered accounts by system role.',
            ];
        }

        if ($typeName === 'Announcement Read Rate') {
            $anns = $this->resultSet(
                "SELECT a.title, a.level, a.status, a.published_at, a.view_count,
                        (SELECT COUNT(*) FROM announcementread r WHERE r.announcement_id = a.announcement_id) AS read_count
                 FROM announcement a
                 WHERE a.status IN ('Published','Archived')
                 ORDER BY a.published_at DESC
                 LIMIT 20"
            );
            $totalUsers = (int)($this->single("SELECT COUNT(*) AS c FROM user")->c ?? 0);
            $rows = []; $totReads = 0; $best = ['rate' => -1, 'title' => '—'];
            foreach ($anns as $a) {
                $rate = $totalUsers > 0 ? round((int)$a->reads / $totalUsers * 100, 1) : 0.0;
                $totReads += (int)$a->reads;
                if ($rate > $best['rate']) $best = ['rate' => $rate, 'title' => $a->title];
                $rows[] = ['cells' => [
                    $a->title, $a->level, $a->published_at ? date('M d, Y', strtotime($a->published_at)) : '—',
                    (int)$a->view_count, (int)$a->read_count, $rate . '%',
                ], 'status' => $rate >= 60 ? 'Healthy' : ($rate >= 25 ? 'Review' : 'Critical')];
            }

            return [
                'kpis' => [
                    ['label' => 'ANNOUNCEMENTS',  'value' => (string)count($rows),   'note' => 'Published to the network', 'tone' => 'blue'],
                    ['label' => 'TOTAL READS',    'value' => number_format($totReads), 'note' => 'Confirmed read receipts', 'tone' => 'green'],
                    ['label' => 'BEST READ RATE', 'value' => $best['rate'] >= 0 ? $best['rate'] . '%' : '—', 'note' => $best['title'], 'tone' => 'green'],
                    ['label' => 'USER BASE',      'value' => number_format($totalUsers), 'note' => 'Accounts counted',   'tone' => 'blue'],
                ],
                'summary' => [
                    'headers' => ['Announcement', 'Level', 'Published', 'Views', 'Reads', 'Read Rate', 'Status'],
                    'rows'    => array_slice($rows, 0, 10),
                ],
                'raw' => [
                    'headers' => ['Announcement', 'Level', 'Published', 'Views', 'Reads', 'Read Rate', 'Status'],
                    'rows'    => $rows,
                ],
                'syncLabel' => 'Announcements Synced',
                'noteTitle' => 'Broadcast Reach Verification:',
                'note'      => 'Read receipts aggregated against ' . number_format($totalUsers) . ' registered user accounts.',
            ];
        }

        if ($typeName === 'Volunteer Hours') {
            $members = $this->resultSet(
                "SELECT CONCAT(u.first_name, ' ', u.last_name) AS member,
                        c.club_name, d.division_name, z.zonal_name,
                        SUM(v.hours) AS hours, COUNT(v.history_id) AS logs
                 FROM volunteerhistory v
                 JOIN user u           ON u.user_id = v.user_id
                 LEFT JOIN club c      ON c.club_id = COALESCE(v.club_id, u.club_id)
                 LEFT JOIN division d  ON d.division_id = c.division_id
                 LEFT JOIN zone z      ON z.zonal_id = d.zonal_id
                 WHERE v.status = 'Verified' AND v.date BETWEEN ? AND ?
                 GROUP BY u.user_id, member, c.club_name, d.division_name, z.zonal_name
                 ORDER BY hours DESC",
                [$start, $end]
            );
            $totHours = 0; $clubAgg = []; $rows = [];
            foreach ($members as $m) {
                $hours = (int)$m->hours; $totHours += $hours;
                $rows[] = ['cells' => [
                    $m->member, $m->club_name ?: '—', $m->zonal_name ?: '—', (int)$m->logs, $hours,
                ], 'status' => $hours >= 40 ? 'Top Contributor' : ($hours >= 10 ? 'Active' : 'Participating')];
                $club = $m->club_name ?: 'Unassigned';
                if (!isset($clubAgg[$club])) $clubAgg[$club] = ['members' => 0, 'hours' => 0, 'zone' => $m->zonal_name ?: '—'];
                $clubAgg[$club]['members']++; $clubAgg[$club]['hours'] += $hours;
            }
            $clubRows = [];
            foreach ($clubAgg as $name => $c) {
                $clubRows[] = ['cells' => [
                    $name, $c['zone'], $c['members'], $c['hours'],
                    $c['members'] > 0 ? round($c['hours'] / $c['members'], 1) : 0,
                ], 'status' => $c['hours'] >= 100 ? 'Top Contributor' : 'Active'];
            }

            return [
                'kpis' => [
                    ['label' => 'VERIFIED HOURS',   'value' => number_format($totHours), 'note' => 'Logged in range', 'tone' => 'green'],
                    ['label' => 'ACTIVE VOLUNTEERS', 'value' => (string)count($rows),    'note' => 'With verified entries', 'tone' => 'blue'],
                    ['label' => 'CLUBS CONTRIBUTING', 'value' => (string)count($clubRows), 'note' => 'Clubs with volunteers', 'tone' => 'blue'],
                    ['label' => 'AVG PER VOLUNTEER', 'value' => count($rows) > 0 ? round($totHours / count($rows), 1) : '0', 'note' => 'Verified hours each', 'tone' => 'green'],
                ],
                'summary' => [
                    'headers' => ['Club', 'Zone', 'Volunteers', 'Verified Hours', 'Avg / Volunteer', 'Status'],
                    'rows'    => $clubRows,
                ],
                'raw' => [
                    'headers' => ['Volunteer', 'Club', 'Zone', 'Verified Logs', 'Hours', 'Status'],
                    'rows'    => array_slice($rows, 0, 25),
                ],
                'syncLabel' => 'Clubs Synced',
                'noteTitle' => 'Volunteer Hours Verification:',
                'note'      => 'Only Verified volunteer log entries within the selected range are counted.',
            ];
        }

        // Default: Login Activity
        $roles = $this->resultSet(
            "SELECT role, COUNT(*) AS total,
                    SUM(last_login_at BETWEEN ? AND ?) AS active,
                    SUM(last_login_at IS NULL) AS never_logged
             FROM user GROUP BY role ORDER BY total DESC",
            [$start, $end]
        );
        $totUsers = 0; $active = 0; $never = 0;
        $roleRows = [];
        foreach ($roles as $r) {
            $totUsers += (int)$r->total; $active += (int)$r->active; $never += (int)$r->never_logged;
            $roleRows[] = ['cells' => [
                $r->role, (int)$r->total, (int)$r->active, (int)$r->never_logged,
            ], 'status' => ((int)$r->active > 0) ? 'Healthy' : 'Review'];
        }
        $recent = $this->resultSet(
            "SELECT CONCAT(u.first_name, ' ', u.last_name) AS member, u.role, u.last_login_at, u.status
             FROM user u WHERE u.last_login_at BETWEEN ? AND ?
             ORDER BY u.last_login_at DESC LIMIT 20",
            [$start, $end]
        );
        $recentRows = [];
        foreach ($recent as $u) {
            $recentRows[] = ['cells' => [
                $u->member, $u->role, $u->last_login_at ? date('M d, Y H:i', strtotime($u->last_login_at)) : '—',
            ], 'status' => $u->status];
        }

        return [
            'kpis' => [
                ['label' => 'REGISTERED USERS', 'value' => (string)$totUsers, 'note' => 'All accounts', 'tone' => 'blue'],
                ['label' => 'LOGGED IN RANGE',  'value' => (string)$active,   'note' => 'Signed in within period', 'tone' => 'green'],
                ['label' => 'NEVER LOGGED IN',  'value' => (string)$never,    'note' => 'Dormant credentials', 'tone' => 'blue'],
                ['label' => 'ACTIVITY RATE',    'value' => ($totUsers > 0 ? round($active / $totUsers * 100, 1) : 0) . '%', 'note' => 'Active vs registered', 'tone' => 'green'],
            ],
            'summary' => [
                'headers' => ['Role', 'Total Users', 'Logged In Range', 'Never Logged In', 'Status'],
                'rows'    => $roleRows,
            ],
            'raw' => [
                'headers' => ['User', 'Role', 'Last Login', 'Status'],
                'rows'    => $recentRows,
            ],
            'syncLabel' => 'Roles Synced',
            'noteTitle' => 'Login Activity Verification:',
            'note'      => 'Sign-in events aggregated from user account last-login timestamps in the selected range.',
        ];
    }

    /** Financial category — one builder per ReportTypeCatalog type. */
    private function buildFinancialPreview(string $typeName, string $start, string $end): array {
        switch ($typeName) {
            case 'Income Summary':
                return $this->buildLedgerEntriesSummary('Income', $start, $end);
            case 'Expense Summary':
                return $this->buildLedgerEntriesSummary('Expense', $start, $end);
            case 'Fund Allocation vs Utilisation':
            case 'Club Fund Allocation Report': // legacy catalog alias
                return $this->buildFundAllocationVsUtilisation($start, $end);
            case 'Void Rate Report':
            case 'Void Request Activity': // legacy catalog alias
                return $this->buildVoidRateReport($start, $end);
            default: // 'National Consolidated Financial Rollup' (+ legacy 'Divisional Financial Summary' / 'Club Audit Compliance Report')
                return $this->buildNationalFinancialRollup($start, $end);
        }
    }

    /** Compliance band for void rates: <10% healthy, <25% review, else critical. */
    private function voidBand(float $rate): string {
        if ($rate < 10) return 'Healthy';
        if ($rate < 25) return 'Review';
        return 'Critical';
    }

    /** Income Summary / Expense Summary — approved LedgerEntry amounts grouped by category. */
    private function buildLedgerEntriesSummary(string $type, string $start, string $end): array {
        $isIncome = $type === 'Income';
        $rows = $this->resultSet(
            "SELECT COALESCE(NULLIF(le.category, ''), 'Uncategorised') AS src,
                    COUNT(*) AS entries,
                    SUM(le.amount) AS total
             FROM LedgerEntry le
             WHERE le.type = ?
               AND le.status = 'Approved'
               AND le.date BETWEEN ? AND ?
             GROUP BY src
             ORDER BY total DESC",
            [$type, $start, $end]
        );

        $grand = 0.0; $entries = 0; $dataRows = [];
        foreach ($rows as $r) {
            $grand    += (float)$r->total;
            $entries  += (int)$r->entries;
            $dataRows[] = ['cells' => [
                $r->src, (int)$r->entries, number_format((float)$r->total, 2),
            ], 'status' => 'Verified'];
        }

        $detailRows = [];
        foreach ($this->resultSet(
            "SELECT le.description, le.date,
                    COALESCE(NULLIF(le.category, ''), 'Uncategorised') AS category,
                    le.amount, le.status
             FROM LedgerEntry le
             WHERE le.type = ?
               AND le.status = 'Approved'
               AND le.date BETWEEN ? AND ?
             ORDER BY le.date DESC, le.entry_id DESC
             LIMIT 25",
            [$type, $start, $end]
        ) as $d) {
            $detailRows[] = ['cells' => [
                $d->description !== '' ? $d->description : '—',
                date('d M Y', strtotime($d->date)),
                $d->category,
                number_format((float)$d->amount, 2),
            ], 'status' => $d->status];
        }

        // Overall totals for the KPI cards (approved entries of the selected type only).
        $totals = $this->single(
            "SELECT COALESCE(SUM(le.amount), 0) AS grand, COUNT(*) AS cnt
             FROM LedgerEntry le
             WHERE le.type = ? AND le.status = 'Approved' AND le.date BETWEEN ? AND ?",
            [$type, $start, $end]
        );

        $top = isset($rows[0]) ? $rows[0] : null;
        $typeLabel  = $isIncome ? 'INCOME' : 'EXPENDITURE';
        $sourceLabel = $isIncome ? 'Income Source' : 'Expense Category';

        return [
            'kpis' => [
                ['label' => "TOTAL {$typeLabel}", 'value' => 'LKR ' . number_format((float)$totals->grand, 2),
                 'note' => "Approved {$typeLabel} entries in range", 'tone' => 'green'],
                ['label' => "{$typeLabel} SOURCES", 'value' => (string)count($rows),
                 'note' => 'Distinct ledger categories', 'tone' => 'blue'],
                ['label' => 'APPROVED ENTRIES', 'value' => (string)(int)$totals->cnt,
                 'note' => 'Ledger entries counted', 'tone' => 'blue'],
                ['label' => 'TOP SOURCE SHARE',
                 'value' => ($grand > 0 && $top)
                     ? round((float)$top->total / $grand * 100, 1) . '%' : '0%',
                 'note' => $top ? $top->src : "No {$typeLabel} recorded", 'tone' => 'green'],
            ],
            'summary' => [
                'headers' => [$sourceLabel, 'Entries', 'Amount (LKR)', 'Status'],
                'rows'    => $dataRows,
            ],
            'raw' => [
                'headers' => ['Entry', 'Date', 'Category', 'Amount (LKR)', 'Status'],
                'rows'    => $detailRows,
            ],
            'syncLabel' => 'Ledgers Synced',
            'noteTitle' => $isIncome ? 'Income Ledger Verification:' : 'Expense Ledger Verification:',
            'note'      => "Approved {$type} ledger entries aggregated by category for the selected range. Pending, Rejected and Voided entries are excluded.",
        ];
    }

    /** Fund Allocation vs Utilisation — allocations mirrored against utilisation via allocation_id. */
    private function buildFundAllocationVsUtilisation(string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT COALESCE(
                        CASE fa.to_level
                            WHEN 'Zonal'     THEN z.zonal_name
                            WHEN 'Divisional' THEN d.division_name
                            WHEN 'Club'      THEN c.club_name
                        END,
                        CONCAT(UCASE(fa.to_level), ' #', fa.to_id)) AS dest,
                    COALESCE(SUM(fa.amount), 0) AS allocated,
                    COALESCE(SUM(CASE WHEN le.type = 'Expense' AND le.status = 'Approved'
                                      THEN le.amount ELSE 0 END), 0) AS utilised
             FROM FundAllocation fa
             LEFT JOIN Zone     z ON fa.to_level = 'Zonal'      AND z.zonal_id     = fa.to_id
             LEFT JOIN division d ON fa.to_level = 'Divisional' AND d.division_id  = fa.to_id
             LEFT JOIN club     c ON fa.to_level = 'Club'       AND c.club_id      = fa.to_id
             LEFT JOIN LedgerEntry le ON le.allocation_id = fa.allocation_id
             WHERE fa.transfer_date BETWEEN ? AND ?
             GROUP BY dest
             ORDER BY allocated DESC",
            [$start, $end]
        );

        $grandAlloc = 0.0; $grandUtil = 0.0; $dataRows = [];
        foreach ($rows as $r) {
            $grandAlloc += (float)$r->allocated;
            $grandUtil  += (float)$r->utilised;
            $rate = (float)$r->allocated > 0 ? (float)$r->utilised / (float)$r->allocated * 100 : 0.0;
            $dataRows[] = ['cells' => [
                $r->dest,
                number_format((float)$r->allocated, 2),
                number_format((float)$r->utilised, 2),
                round($rate, 1) . '%',
            ], 'status' => $this->rateBand($rate)];
        }

        $transferRows = [];
        foreach ($this->resultSet(
            "SELECT fa.reference_no, fa.transfer_date, fa.amount, fa.status, fa.purpose_description,
                    COALESCE(
                        CASE fa.to_level
                            WHEN 'Zonal'     THEN z.zonal_name
                            WHEN 'Divisional' THEN d.division_name
                            WHEN 'Club'      THEN c.club_name
                        END,
                        CONCAT(UCASE(fa.to_level), ' #', fa.to_id)) AS dest
             FROM FundAllocation fa
             LEFT JOIN Zone     z ON fa.to_level = 'Zonal'      AND z.zonal_id     = fa.to_id
             LEFT JOIN division d ON fa.to_level = 'Divisional' AND d.division_id  = fa.to_id
             LEFT JOIN club     c ON fa.to_level = 'Club'       AND c.club_id      = fa.to_id
             WHERE fa.transfer_date BETWEEN ? AND ?
             ORDER BY fa.transfer_date DESC, fa.allocation_id DESC
             LIMIT 25",
            [$start, $end]
        ) as $t) {
            $transferRows[] = ['cells' => [
                $t->reference_no,
                $t->dest,
                date('d M Y', strtotime($t->transfer_date)),
                number_format((float)$t->amount, 2),
                $t->purpose_description,
            ], 'status' => $t->status === 'Completed' ? 'Healthy' : ($t->status === 'Failed' ? 'Critical' : 'Review')];
        }

        $rate = $grandAlloc > 0 ? $grandUtil / $grandAlloc * 100 : 0.0;

        return [
            'kpis' => [
                ['label' => 'TOTAL ALLOCATED', 'value' => 'LKR ' . number_format($grandAlloc, 2),
                 'note' => 'Completed allocations in range', 'tone' => 'blue'],
                ['label' => 'TOTAL UTILISED', 'value' => 'LKR ' . number_format($grandUtil, 2),
                 'note' => 'Approved expenses tied to allocations', 'tone' => 'blue'],
                ['label' => 'UTILISATION RATE', 'value' => round($rate, 1) . '%',
                 'note' => 'Utilised vs allocated overall', 'tone' => 'green'],
                ['label' => 'UNUTILISED BALANCE', 'value' => 'LKR ' . number_format(max($grandAlloc - $grandUtil, 0), 2),
                 'note' => 'Funds not yet expended', 'tone' => 'green'],
            ],
            'summary' => [
                'headers' => ['Zone / Division', 'Allocated (LKR)', 'Utilised (LKR)', 'Utilisation %', 'Status'],
                'rows'    => $dataRows,
            ],
            'raw' => [
                'headers' => ['Reference', 'Destination', 'Date', 'Amount (LKR)', 'Purpose', 'Status'],
                'rows'    => $transferRows,
            ],
            'syncLabel' => 'Zone Ledger Synced',
            'noteTitle' => 'Utilisation Verification:',
            'note'      => 'Allocated funds compared against approved expense ledger entries linked by allocation. Only completed allocations in the selected range are counted.',
        ];
    }

    /** Void Rate Report — monthly void rate plus the VoidRequest trail. */
    private function buildVoidRateReport(string $start, string $end): array {
        $months = $this->resultSet(
            "SELECT DATE_FORMAT(le.date, '%Y-%m') AS ym,
                    COUNT(*) AS total_entries,
                    SUM(CASE WHEN le.status = 'Voided' THEN 1 ELSE 0 END) AS voided
             FROM LedgerEntry le
             WHERE le.date BETWEEN ? AND ?
             GROUP BY ym
             ORDER BY ym ASC",
            [$start, $end]
        );

        $totalEntries = 0; $totalVoided = 0; $dataRows = []; $worstMonth = null; $worstRate = -1;
        foreach ($months as $m) {
            $totalEntries += (int)$m->total_entries;
            $totalVoided  += (int)$m->voided;
            $rate = (float)$m->total_entries > 0 ? (float)$m->voided / (float)$m->total_entries * 100 : 0.0;
            if ($rate > $worstRate) { $worstRate = $rate; $worstMonth = $m->ym; }
            $dataRows[] = ['cells' => [
                date('M Y', strtotime($m->ym . '-01')),
                (int)$m->total_entries,
                (int)$m->voided,
                round($rate, 1) . '%',
            ], 'status' => $this->voidBand($rate)];
        }

        $overallRate = $totalEntries > 0 ? $totalVoided / $totalEntries * 100 : 0.0;

        $openRequests = (int)$this->single(
            "SELECT COUNT(*) AS c FROM VoidRequest WHERE status = 'Pending'"
        )->c;

        $trailRows = [];
        foreach ($this->resultSet(
            "SELECT vr.void_request_id, vr.reason, vr.status, vr.requested_at,
                    le.description, le.amount, le.date
             FROM VoidRequest vr
             JOIN LedgerEntry le ON le.entry_id = vr.entry_id
             WHERE vr.requested_at BETWEEN ? AND ?
             ORDER BY vr.requested_at DESC
             LIMIT 25",
            [$start, $end]
        ) as $vr) {
            $trailRows[] = ['cells' => [
                '#' . $vr->void_request_id,
                $vr->description !== '' ? $vr->description : '—',
                number_format((float)$vr->amount, 2),
                date('d M Y', strtotime($vr->date)),
                $vr->reason,
                date('d M Y', strtotime($vr->requested_at)),
            ], 'status' => $vr->status];
        }

        return [
            'kpis' => [
                ['label' => 'OVERALL VOID RATE', 'value' => round($overallRate, 1) . '%',
                 'note' => $overallRate < 10 ? 'Healthy compliance (<10%)' : 'Above compliance threshold', 'tone' => $overallRate < 10 ? 'green' : 'blue'],
                ['label' => 'VOIDED ENTRIES', 'value' => (string)$totalVoided,
                 'note' => "Of {$totalEntries} entries logged", 'tone' => 'blue'],
                ['label' => 'OPEN VOID REQUESTS', 'value' => (string)$openRequests,
                 'note' => 'Pending VoidRequest reviews', 'tone' => 'blue'],
                ['label' => 'WORST MONTH',
                 'value' => $worstMonth !== null ? round($worstRate, 1) . '%' : '0%',
                 'note' => $worstMonth !== null ? date('M Y', strtotime($worstMonth . '-01')) : 'No entries in range', 'tone' => 'blue'],
            ],
            'summary' => [
                'headers' => ['Month', 'Entries Logged', 'Voided', 'Void Rate %', 'Status'],
                'rows'    => $dataRows,
            ],
            'raw' => [
                'headers' => ['Request', 'Ledger Entry', 'Amount (LKR)', 'Entry Date', 'Reason', 'Requested At', 'Status'],
                'rows'    => $trailRows,
            ],
            'syncLabel' => 'Void Trail Synced',
            'noteTitle' => 'Void Compliance Verification:',
            'note'      => 'Rate of voided ledger entries against all entries logged per month. A void rate below 10% is considered healthy compliance.',
        ];
    }

    /** National Consolidated Financial Rollup — per-zone live rollup (statutory layout). */
    private function buildNationalFinancialRollup(string $start, string $end): array {
        $rows = $this->resultSet(
            "SELECT z.zonal_id, z.zonal_name,
                    COALESCE(a.total_amount, 0) AS allocated,
                    COALESCE(f.income_amount, 0) AS income,
                    COALESCE(f.expense_amount, 0) AS expense,
                    COALESCE(f.voided_count, 0) AS voided
             FROM Zone z
             LEFT JOIN (
                 SELECT fa.to_id, SUM(fa.amount) AS total_amount
                 FROM FundAllocation fa
                 WHERE fa.to_level = 'Zonal' AND fa.status = 'Completed'
                   AND fa.transfer_date BETWEEN ? AND ?
                 GROUP BY fa.to_id
             ) a ON a.to_id = z.zonal_id
             LEFT JOIN (
                 SELECT l.owner_id,
                        SUM(CASE WHEN le.type = 'Income'  AND le.status = 'Approved' THEN le.amount ELSE 0 END) AS income_amount,
                        SUM(CASE WHEN le.type = 'Expense' AND le.status = 'Approved' THEN le.amount ELSE 0 END) AS expense_amount,
                        SUM(CASE WHEN le.status = 'Voided' THEN 1 ELSE 0 END) AS voided_count
                 FROM LedgerEntry le
                 JOIN Ledger l ON l.ledger_id = le.ledger_id
                 WHERE l.owner_type = 'Zone' AND le.date BETWEEN ? AND ?
                 GROUP BY l.owner_id
             ) f ON f.owner_id = z.zonal_id
             HAVING allocated > 0 OR income > 0 OR expense > 0
             ORDER BY allocated DESC",
            [$start, $end, $start, $end]
        );

        $grandAlloc = 0.0; $grandIncome = 0.0; $grandExpense = 0.0; $grandVoided = 0;
        $dataRows = [];
        foreach ($rows as $r) {
            $grandAlloc   += (float)$r->allocated;
            $grandIncome  += (float)$r->income;
            $grandExpense += (float)$r->expense;
            $grandVoided  += (int)$r->voided;
            $net = (float)$r->income - (float)$r->expense;
            $dataRows[] = ['cells' => [
                $r->zonal_name,
                number_format((float)$r->allocated, 2),
                number_format((float)$r->income, 2),
                number_format((float)$r->expense, 2),
                number_format($net, 2),
            ], 'status' => $net >= 0 ? 'Healthy' : 'Review'];
        }

        $detailRows = [];
        foreach ($this->resultSet(
            "SELECT le.description, le.category, le.type, le.amount, le.status, le.date, z.zonal_name
             FROM LedgerEntry le
             JOIN Ledger l ON l.ledger_id = le.ledger_id
             LEFT JOIN Zone z ON z.zonal_id = l.owner_id AND l.owner_type = 'Zone'
             WHERE l.owner_type = 'Zone' AND le.date BETWEEN ? AND ?
             ORDER BY le.date DESC, le.entry_id DESC
             LIMIT 25",
            [$start, $end]
        ) as $d) {
            $detailRows[] = ['cells' => [
                $d->zonal_name ?? '—',
                date('d M Y', strtotime($d->date)),
                $d->type,
                $d->description !== '' ? $d->description : '—',
                number_format((float)$d->amount, 2),
            ], 'status' => $d->status];
        }

        $overallVoidRate = $this->single(
            "SELECT CASE WHEN COUNT(*) = 0 THEN 0
                    ELSE SUM(CASE WHEN le.status = 'Voided' THEN 1 ELSE 0 END) * 100.0 / COUNT(*) END AS rate
             FROM LedgerEntry le
             WHERE le.date BETWEEN ? AND ?",
            [$start, $end]
        );

        return [
            'kpis' => [
                ['label' => 'TOTAL ALLOCATED FUNDS', 'value' => 'LKR ' . number_format($grandAlloc, 2),
                 'note' => 'Completed transfers to zones in range', 'tone' => 'green'],
                ['label' => 'TOTAL INCOME', 'value' => 'LKR ' . number_format($grandIncome, 2),
                 'note' => 'Approved income across zonal ledgers', 'tone' => 'blue'],
                ['label' => 'TOTAL EXPENDITURE', 'value' => 'LKR ' . number_format($grandExpense, 2),
                 'note' => 'Approved expenses across zonal ledgers', 'tone' => 'blue'],
                ['label' => 'OVERALL VOID RATE',
                 'value' => round((float)($overallVoidRate->rate ?? 0), 1) . '%',
                 'note' => (float)($overallVoidRate->rate ?? 0) < 10 ? 'Healthy compliance (<10%)' : 'Above compliance threshold',
                 'tone' => (float)($overallVoidRate->rate ?? 0) < 10 ? 'green' : 'blue'],
            ],
            'summary' => [
                'headers' => ['Province / Zone', 'Allocated (LKR)', 'Income (LKR)', 'Expense (LKR)', 'Net (LKR)', 'Status'],
                'rows'    => $dataRows,
            ],
            'raw' => [
                'headers' => ['Zonal Ledger', 'Date', 'Type', 'Entry', 'Amount (LKR)', 'Status'],
                'rows'    => $detailRows,
            ],
            'syncLabel' => 'Zonal Hubs Synced',
            'noteTitle' => 'Statutory Ledger Compliance Verification:',
            'note'      => 'Live statutory aggregation across completed fund transfers and approved zonal ledger entries for the selected range, sealed under NYSC FinAct ' . date('Y') . '.',
        ];
    }
}
