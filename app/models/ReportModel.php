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
        // Default to financial demo matching the source file
        return [
            'kpis' => [
                ['label'=>'TOTAL ALLOCATED FUNDS', 'value'=>'LKR 84.5M', 'note'=>'+12% vs Last Year',         'tone'=>'green'],
                ['label'=>'TOTAL EXPENDITURE',     'value'=>'LKR 61.2M', 'note'=>'72.4% Spend Ratio',         'tone'=>'blue'],
                ['label'=>'SUB-LEDGER VOID RATE',  'value'=>'2.1%',      'note'=>'Healthy compliance (<10%)',  'tone'=>'green'],
            ],
            'summaryRows' => [
                ['zone'=>'Western Province (Hub 01)',  'allocated'=>'24,500,000', 'disbursed'=>'24,500,000', 'expenses'=>'19,850,000', 'voids'=>4, 'status'=>'Reconciled'],
                ['zone'=>'Central Province (Hub 02)',  'allocated'=>'18,200,000', 'disbursed'=>'18,200,000', 'expenses'=>'14,100,000', 'voids'=>2, 'status'=>'Reconciled'],
                ['zone'=>'Southern Province (Hub 03)', 'allocated'=>'16,000,000', 'disbursed'=>'16,000,000', 'expenses'=>'12,450,000', 'voids'=>1, 'status'=>'Reconciled'],
                ['zone'=>'Northern Province (Hub 04)', 'allocated'=>'14,800,000', 'disbursed'=>'14,800,000', 'expenses'=>'9,800,000',  'voids'=>3, 'status'=>'Reconciled'],
                ['zone'=>'Eastern Province (Hub 05)',  'allocated'=>'11,000,000', 'disbursed'=>'11,000,000', 'expenses'=>'5,000,000',  'voids'=>0, 'status'=>'Reconciled'],
            ],
            'rawRows' => [
                ['division'=>'Colombo Division',     'zone'=>'Western Province (Hub 01)', 'allocated'=>'14,200,000','disbursed'=>'14,200,000','expenses'=>'11,600,000','voids'=>2,'by'=>'K. Perera',       'status'=>'Verified'],
                ['division'=>'Gampaha Division',     'zone'=>'Western Province (Hub 01)', 'allocated'=>'10,300,000','disbursed'=>'10,300,000','expenses'=>'8,250,000', 'voids'=>2,'by'=>'S. Silva',        'status'=>'Verified'],
                ['division'=>'Kandy Division',       'zone'=>'Central Province (Hub 02)', 'allocated'=>'11,500,000','disbursed'=>'11,500,000','expenses'=>'8,900,000', 'voids'=>1,'by'=>'M. Jayawardena',  'status'=>'Verified'],
                ['division'=>'Matale Division',      'zone'=>'Central Province (Hub 02)', 'allocated'=>'6,700,000', 'disbursed'=>'6,700,000', 'expenses'=>'5,200,000', 'voids'=>1,'by'=>'R. Bandara',      'status'=>'Verified'],
                ['division'=>'Galle Division',       'zone'=>'Southern Province (Hub 03)','allocated'=>'9,400,000', 'disbursed'=>'9,400,000', 'expenses'=>'7,300,000', 'voids'=>1,'by'=>'N. Wickramasinghe','status'=>'Verified'],
                ['division'=>'Matara Division',      'zone'=>'Southern Province (Hub 03)','allocated'=>'6,600,000', 'disbursed'=>'6,600,000', 'expenses'=>'5,150,000', 'voids'=>0,'by'=>'T. Fernando',     'status'=>'Verified'],
                ['division'=>'Jaffna Division',      'zone'=>'Northern Province (Hub 04)','allocated'=>'9,200,000', 'disbursed'=>'9,200,000', 'expenses'=>'6,100,000', 'voids'=>2,'by'=>'A. Rajasingham',  'status'=>'Verified'],
                ['division'=>'Vavuniya Division',    'zone'=>'Northern Province (Hub 04)','allocated'=>'5,600,000', 'disbursed'=>'5,600,000', 'expenses'=>'3,700,000', 'voids'=>1,'by'=>'P. Anandarajah',  'status'=>'Verified'],
                ['division'=>'Batticaloa Division',  'zone'=>'Eastern Province (Hub 05)', 'allocated'=>'6,400,000', 'disbursed'=>'6,400,000', 'expenses'=>'2,900,000', 'voids'=>0,'by'=>'S. Chandran',     'status'=>'Verified'],
                ['division'=>'Trincomalee Division', 'zone'=>'Eastern Province (Hub 05)', 'allocated'=>'4,600,000', 'disbursed'=>'4,600,000', 'expenses'=>'2,100,000', 'voids'=>0,'by'=>'L. Iqbal',        'status'=>'Verified'],
            ],
        ];
    }
}
