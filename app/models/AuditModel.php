<?php

/**
 * AuditModel
 *
 * Annual Financial Audit module for NYSC Administration.
 *
 * Implements the four workflow phases:
 *   Phase 1 — Scope & financial year selection (National / Zonal / Divisional / Club).
 *   Phase 2 — Core equation math check against the ledger's actual closing balance.
 *   Phase 3 — Red flag detection: missing receipts, fund hoarding, high void rate,
 *             plus a critical math-mismatch flag.
 *   Phase 4 — Auditor review: clarification requests to the entity's treasurer &
 *             coordinator, and formal sign-off that locks the audited financial year.
 *
 * All figures are computed from real ledger activity (LedgerEntry / FundAllocation).
 * Transfer-related entries are excluded from self-raised income/expense totals via
 * LedgerEntry.allocation_id to avoid double counting against FundAllocation sums.
 */
class AuditModel extends Model {

    /**
     * All selectable scopes grouped by level for the Phase 1 selector.
     *
     * @return array ['National' => [...], 'Zonal' => [...], 'Divisional' => [...], 'Club' => [...]]
     */
    public function getAvailableScopes() {
        $scopes = [
            'National'  => [['scope_level' => 'National', 'scope_id' => 0, 'name' => 'National Summary (NYSC Central Ledger)']],
            'Zonal'     => [],
            'Divisional'=> [],
            'Club'      => [],
        ];

        $zones = $this->resultSet("SELECT zonal_id, zonal_name, province FROM Zone ORDER BY zonal_name ASC");
        foreach ($zones as $z) {
            $scopes['Zonal'][] = [
                'scope_level' => 'Zonal',
                'scope_id'    => (int)$z->zonal_id,
                'name'        => $z->zonal_name . (($z->province) ? ' — ' . $z->province : ''),
            ];
        }

        $divisions = $this->resultSet("SELECT d.division_id, d.division_name, z.zonal_name
                                       FROM Division d LEFT JOIN Zone z ON z.zonal_id = d.zonal_id
                                       ORDER BY d.division_name ASC");
        foreach ($divisions as $d) {
            $scopes['Divisional'][] = [
                'scope_level' => 'Divisional',
                'scope_id'    => (int)$d->division_id,
                'name'        => $d->division_name . (($d->zonal_name) ? ' (' . $d->zonal_name . ')' : ''),
            ];
        }

        $clubs = $this->resultSet("SELECT club_id, club_name, club_code FROM Club WHERE status = 'Active' ORDER BY club_name ASC");
        foreach ($clubs as $c) {
            $scopes['Club'][] = [
                'scope_level' => 'Club',
                'scope_id'    => (int)$c->club_id,
                'name'        => $c->club_name . ' [' . $c->club_code . ']',
            ];
        }

        return $scopes;
    }

    /**
     * Financial years offered in the Phase 1 selector ( newest first ).
     *
     * @return array
     */
    public function getSelectableYears() {
        $current = (int)date('Y');
        return range($current, 2023);
    }

    /**
     * Resolve human-readable details and designated clarification contacts for a scope.
     *
     * @param  string   $scopeLevel  'National','Zonal','Divisional','Club'
     * @param  int|null $scopeId
     * @return object
     */
    public function getScopeDetails($scopeLevel, $scopeId) {
        $scopeLevel = ucfirst(strtolower($scopeLevel));
        $scopeId    = ($scopeLevel === 'National') ? null : (int)$scopeId;

        $details = (object)[
            'scope_level'      => $scopeLevel,
            'scope_id'         => $scopeId,
            'title'            => 'National Youth Services Council',
            'subtitle'         => 'National Headquarters & Central Treasury',
            'treasurer_name'   => 'Central Treasury Officer',
            'coordinator_name' => 'Director General — NYSC',
            'treasurer_id'     => null,
            'coordinator_id'   => null,
            'recipients_label' => 'Headquarters Financial Controller & Central Treasury',
        ];

        if ($scopeLevel === 'Zonal' && $scopeId) {
            $zone = $this->single("SELECT zonal_name, province, hub_name FROM Zone WHERE zonal_id = :id", ['id' => $scopeId]);
            if ($zone) {
                $details->title    = $zone->zonal_name . (($zone->hub_name) ? ' — ' . $zone->hub_name : '');
                $details->subtitle = ($zone->province ?: 'Zonal Office') . ' • Zonal Ledger';
            }
            $details->treasurer_name   = $this->resolveOfficerName('ZonalTreasurer', 'zonal_id', $scopeId, 'Zonal Treasurer');
            $details->coordinator_name = $this->resolveOfficerName('ZonalCoordinator', 'zonal_id', $scopeId, 'Zonal Coordinator');
            $details->treasurer_id     = $this->resolveOfficerId('ZonalTreasurer', 'zonal_id', $scopeId);
            $details->coordinator_id   = $this->resolveOfficerId('ZonalCoordinator', 'zonal_id', $scopeId);
        }

        if ($scopeLevel === 'Divisional' && $scopeId) {
            $division = $this->single("SELECT d.division_name, z.zonal_name
                                       FROM Division d LEFT JOIN Zone z ON z.zonal_id = d.zonal_id
                                       WHERE d.division_id = :id", ['id' => $scopeId]);
            if ($division) {
                $details->title    = $division->division_name;
                $details->subtitle = 'Divisional Ledger' . (($division->zonal_name) ? ' • ' . $division->zonal_name : '');
            }
            $details->treasurer_name   = $this->resolveOfficerName('DivisionalTreasurer', 'division_id', $scopeId, 'Divisional Treasurer');
            $details->coordinator_name = $this->resolveOfficerName('DivisionalCoordinator', 'division_id', $scopeId, 'Divisional Coordinator');
            $details->treasurer_id     = $this->resolveOfficerId('DivisionalTreasurer', 'division_id', $scopeId);
            $details->coordinator_id   = $this->resolveOfficerId('DivisionalCoordinator', 'division_id', $scopeId);
        }

        if ($scopeLevel === 'Club' && $scopeId) {
            $club = $this->single("SELECT c.club_name, c.club_code, d.division_name
                                   FROM Club c LEFT JOIN Division d ON d.division_id = c.division_id
                                   WHERE c.club_id = :id", ['id' => $scopeId]);
            if ($club) {
                $details->title    = $club->club_name . ' [' . $club->club_code . ']';
                $details->subtitle = 'Club Ledger' . (($club->division_name) ? ' • ' . $club->division_name . ' Division' : '');
            }
            $details->treasurer_name   = $this->resolveOfficerName('ClubTreasurer', 'club_id', $scopeId, 'Club Treasurer');
            $details->coordinator_name = $this->resolveOfficerName('DivisionalCoordinator', 'division_id', $this->clubDivisionId($scopeId), 'Divisional Coordinator');
            $details->treasurer_id     = $this->resolveOfficerId('ClubTreasurer', 'club_id', $scopeId);
            $details->coordinator_id   = $this->resolveOfficerId('DivisionalCoordinator', 'division_id', $this->clubDivisionId($scopeId));
        }

        if ($scopeLevel !== 'National' && $details->treasurer_name && $details->coordinator_name) {
            $tRole = ['Zonal' => 'Zonal Treasurer', 'Divisional' => 'Divisional Treasurer', 'Club' => 'Club Treasurer'][$scopeLevel] ?? 'Treasurer';
            $cRole = ['Zonal' => 'Zonal Coordinator', 'Divisional' => 'Divisional Coordinator', 'Club' => 'Divisional Coordinator'][$scopeLevel] ?? 'Coordinator';
            // Avoid "Zonal Treasurer (Zonal Treasurer)" when the fallback label is in use.
            $tPart = ($details->treasurer_name === $tRole) ? $tRole : "{$details->treasurer_name} ({$tRole})";
            $cPart = ($details->coordinator_name === $cRole) ? $cRole : "{$details->coordinator_name} ({$cRole})";
            $details->recipients_label = "{$tPart} & {$cPart}";
        }

        return $details;
    }

    private function clubDivisionId($clubId) {
        $row = $this->single("SELECT division_id FROM Club WHERE club_id = :id", ['id' => (int)$clubId]);
        return $row ? (int)$row->division_id : 0;
    }

    private function resolveOfficerId($role, $column, $value) {
        if (!$value) return null;
        $row = $this->single("SELECT user_id FROM User WHERE role = :role AND {$column} = :val AND status = 'Active' ORDER BY user_id ASC LIMIT 1",
            ['role' => $role, 'val' => $value]);
        return $row ? (int)$row->user_id : null;
    }

    private function resolveOfficerName($role, $column, $value, $fallbackLabel) {
        $id = $this->resolveOfficerId($role, $column, $value);
        if ($id) {
            $u = $this->single("SELECT first_name, last_name FROM User WHERE user_id = :id", ['id' => $id]);
            $name = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            if ($name !== '') return $name;
        }
        return $fallbackLabel;
    }

    /**
     * Locate the Ledger row that backs a scope.
     *
     * @return object|null
     */
    public function resolveLedger($scopeLevel, $scopeId) {
        if ($scopeLevel === 'National') {
            return $this->single("SELECT * FROM Ledger WHERE owner_level = 'National' LIMIT 1");
        }
        $map = ['Zonal' => 'Zone', 'Divisional' => 'Division', 'Club' => 'Club'];
        if (!isset($map[$scopeLevel]) || !$scopeId) return null;
        return $this->single("SELECT * FROM Ledger WHERE owner_type = :t AND owner_id = :id LIMIT 1",
            ['t' => $map[$scopeLevel], 'id' => (int)$scopeId]);
    }

    /**
     * Load an existing audit WITHOUT recomputing (report view).
     */
    public function getAuditById($auditId) {
        $audit = $this->single("SELECT a.*,
                                       u1.first_name AS init_first, u1.last_name AS init_last, u1.role AS init_role,
                                       u2.first_name AS sign_first, u2.last_name AS sign_last, u2.role AS sign_role
                                FROM Audit a
                                LEFT JOIN User u1 ON a.initiated_by = u1.user_id
                                LEFT JOIN User u2 ON a.signed_off_by = u2.user_id
                                WHERE a.audit_id = :id", ['id' => (int)$auditId]);

        if ($audit) {
            $audit->red_flags     = $this->getRedFlags($audit->audit_id);
            $audit->scope_details = $this->getScopeDetails($audit->scope_level, $audit->scope_id);
        }

        return $audit;
    }

    /**
     * Dashboard listing: every audit with scope label and open flag counts.
     */
    public function getAuditList() {
        $rows = $this->resultSet("SELECT a.*,
                                         (SELECT COUNT(*) FROM RedFlag rf WHERE rf.audit_id = a.audit_id) AS total_flags,
                                         (SELECT COUNT(*) FROM RedFlag rf WHERE rf.audit_id = a.audit_id AND rf.status <> 'Resolved') AS open_flags
                                  FROM Audit a
                                  ORDER BY a.audit_id DESC
                                  LIMIT 25");

        foreach ($rows as $row) {
            $row->scope_label = $this->scopeLabel($row->scope_level, $row->scope_id);
        }
        return $rows;
    }

    public function scopeLabel($scopeLevel, $scopeId) {
        if ($scopeLevel === 'National') return 'National Summary';
        $table  = ['Zonal' => 'Zone', 'Divisional' => 'Division', 'Club' => 'Club'];
        $column = ['Zonal' => 'zonal_id', 'Divisional' => 'division_id', 'Club' => 'club_id'];
        $name   = ['Zonal' => 'zonal_name', 'Divisional' => 'division_name', 'Club' => 'club_name'];
        if (!isset($table[$scopeLevel]) || !$scopeId) return ucfirst($scopeLevel) . ' #' . (int)$scopeId;
        $row = $this->single("SELECT {$name[$scopeLevel]} AS n FROM {$table[$scopeLevel]} WHERE {$column[$scopeLevel]} = :id", ['id' => (int)$scopeId]);
        return $row ? $row->n : ucfirst($scopeLevel) . ' #' . (int)$scopeId;
    }

    /**
     * All red flags for an audit, ordered by severity group.
     */
    public function getRedFlags($auditId) {
        $sql = "SELECT rf.*,
                       le.amount      AS entry_amount,
                       le.description AS entry_description,
                       le.date        AS entry_date,
                       le.category    AS entry_category,
                       le.attachment_url
                FROM RedFlag rf
                LEFT JOIN LedgerEntry le ON rf.entry_id = le.entry_id
                WHERE rf.audit_id = :aid
                ORDER BY
                    CASE rf.flag_type
                        WHEN 'MathMismatch'   THEN 0
                        WHEN 'MissingReceipt' THEN 1
                        WHEN 'FundHoarding'   THEN 2
                        WHEN 'HighVoidRate'   THEN 3
                        ELSE 4
                    END ASC,
                    rf.red_flag_id ASC";

        return $this->resultSet($sql, ['aid' => (int)$auditId]);
    }

    /**
     * PHASE 1 + 2 + 3 — Compile the audit for a scope & year:
     * aggregate real ledger activity, run the core equation check, and
     * regenerate the red flag set.
     *
     * @return int Audit ID
     */
    public function runAuditCheck($scopeLevel, $scopeId, $year, $adminUserId = 1, array $thresholds = []) {
        $scopeLevel = ucfirst(strtolower($scopeLevel));
        $scopeIdVal = ($scopeLevel === 'National') ? null : (int)$scopeId;
        $year       = (int)$year;

        $receiptThreshold  = (float)($thresholds['receipt_threshold'] ?? 5000.00);
        $hoardingMargin    = (float)($thresholds['hoarding_margin'] ?? 80.00);
        $voidRateThreshold = (float)($thresholds['void_rate'] ?? 10.00);

        $ledger  = $this->resolveLedger($scopeLevel, $scopeIdVal);
        $ledgerId = $ledger ? (int)$ledger->ledger_id : 0;
        $actualClosing = $ledger ? (float)$ledger->current_balance : 0.00;

        // Activity aggregates. Transfer rows are posted to ledgers with a
        // non-null allocation_id, so self-raised income/expense excludes them
        // and FundAllocation supplies the transfer totals (no double counting).
        $activity = $this->aggregateActivity($ledgerId, $year);

        $transfersIn  = $this->transfersTotal('to', $scopeLevel, $scopeIdVal, $year);
        $transfersOut = $this->transfersTotal('from', $scopeLevel, $scopeIdVal, $year);

        $prior = $this->aggregateActivity($ledgerId, $year, true);
        $priorTransfersIn  = $this->transfersTotal('to', $scopeLevel, $scopeIdVal, $year, true);
        $priorTransfersOut = $this->transfersTotal('from', $scopeLevel, $scopeIdVal, $year, true);

        $openingBalance = round($prior['income'] - $prior['expenses'] + $priorTransfersIn - $priorTransfersOut, 2);

        // PHASE 2 — Core equation check.
        $expectedClosing = round($openingBalance + $activity['income'] + $transfersIn - $activity['expenses'] - $transfersOut, 2);
        $variance        = round($actualClosing - $expectedClosing, 2);
        $mathStatus      = (abs($variance) < 0.01) ? 'Passed' : 'Mismatch';

        // Persist audit record.
        $existingAudit = $this->single("SELECT * FROM Audit WHERE scope_level = :level AND (scope_id = :sid OR (scope_id IS NULL AND :sid_null = 1)) AND financial_year = :yr LIMIT 1", [
            'level'    => $scopeLevel,
            'sid'      => $scopeIdVal ?? 0,
            'sid_null' => ($scopeIdVal === null || $scopeIdVal === 0) ? 1 : 0,
            'yr'       => $year,
        ]);

        $common = [
            'ob'  => $openingBalance,
            'ti'  => $activity['income'],
            'tr'  => $transfersIn,
            'te'  => $activity['expenses'],
            'td'  => $transfersOut,
            'ecb' => $expectedClosing,
            'acb' => $actualClosing,
            'mcs' => $mathStatus,
            'rta' => $receiptThreshold,
            'hmp' => $hoardingMargin,
            'vrt' => $voidRateThreshold,
        ];

        if ($existingAudit) {
            $auditId = (int)$existingAudit->audit_id;
            $this->query("UPDATE Audit SET
                            opening_balance = :ob, total_income = :ti,
                            total_transfers_received = :tr, total_expenses = :te,
                            total_transfers_distributed = :td,
                            expected_closing_balance = :ecb, actual_closing_balance = :acb,
                            math_check_status = :mcs, receipt_threshold_amount = :rta,
                            hoarding_margin_pct = :hmp, void_rate_threshold_pct = :vrt
                          WHERE audit_id = :aid", $common + ['aid' => $auditId]);
        } else {
            $this->query("INSERT INTO Audit (
                            financial_year, scope_level, scope_id,
                            opening_balance, total_income, total_transfers_received,
                            total_expenses, total_transfers_distributed,
                            expected_closing_balance, actual_closing_balance, math_check_status,
                            receipt_threshold_amount, hoarding_margin_pct, void_rate_threshold_pct,
                            audit_status, initiated_by, locked
                          ) VALUES (
                            :yr, :level, :sid, :ob, :ti, :tr, :te, :td, :ecb, :acb, :mcs,
                            :rta, :hmp, :vrt, 'Pending', :init_by, 0
                          )", $common + [
                'yr'      => $year,
                'level'   => $scopeLevel,
                'sid'     => $scopeIdVal,
                'init_by' => $adminUserId,
            ]);
            $auditId = (int)Database::getInstance()->getConnection()->lastInsertId();
        }

        // PHASE 3 — Regenerate the red flag set for this audit.
        $this->detectRedFlags($auditId, $ledgerId, $year, $receiptThreshold, $hoardingMargin, $voidRateThreshold, [
            'transfers_in' => $transfersIn,
            'expenses'     => $activity['expenses'],
            'expected'     => $expectedClosing,
            'actual'       => $actualClosing,
            'variance'     => $variance,
        ]);

        return $auditId;
    }

    /**
     * Aggregate ledger activity for a fiscal year.
     * Pass $before = true to aggregate everything BEFORE January 1 of $year (opening position).
     */
    private function aggregateActivity($ledgerId, $year, $before = false) {
        if ($ledgerId <= 0) {
            return ['income' => 0.00, 'expenses' => 0.00];
        }
        $dateOp = $before ? "date < :start" : "YEAR(date) = :yr";
        $params = $before
            ? ['lid' => $ledgerId, 'start' => $year . '-01-01']
            : ['lid' => $ledgerId, 'yr' => $year];

        $income = $this->single("SELECT COALESCE(SUM(amount), 0) AS total FROM LedgerEntry
                                 WHERE ledger_id = :lid AND type = 'Income' AND status = 'Approved'
                                   AND allocation_id IS NULL AND {$dateOp}", $params);
        $expenses = $this->single("SELECT COALESCE(SUM(amount), 0) AS total FROM LedgerEntry
                                   WHERE ledger_id = :lid AND type = 'Expense' AND status = 'Approved'
                                     AND allocation_id IS NULL AND {$dateOp}", $params);

        return [
            'income'  => (float)($income->total ?? 0),
            'expenses'=> (float)($expenses->total ?? 0),
        ];
    }

    /**
     * Sum completed fund transfers for a scope. $side = 'to' (received) or 'from' (distributed).
     */
    private function transfersTotal($side, $scopeLevel, $scopeId, $year, $before = false) {
        $levelMap = ['Zonal' => 'Zonal', 'Divisional' => 'Divisional', 'Club' => 'Club'];
        $column   = ['to' => ['level' => 'to_level', 'id' => 'to_id'], 'from' => ['level' => 'from_level', 'id' => 'from_id']];

        if ($side === 'to') {
            if ($scopeLevel === 'National' || !isset($levelMap[$scopeLevel])) return 0.00;
            $level = $levelMap[$scopeLevel];
        } else {
            if ($scopeLevel === 'National') {
                $level = 'National';
            } elseif (isset($levelMap[$scopeLevel])) {
                $level = $levelMap[$scopeLevel];
            } else {
                return 0.00;
            }
        }

        $dateOp  = $before ? "transfer_date < :start" : "YEAR(transfer_date) = :yr";
        $params  = $before ? ['start' => $year . '-01-01'] : ['yr' => $year];

        if ($side === 'to') {
            $sql = "SELECT COALESCE(SUM(amount), 0) AS total FROM FundAllocation
                    WHERE to_level = :lvl AND to_id = :sid AND status = 'Completed' AND {$dateOp}";
            $params += ['lvl' => $level, 'sid' => (int)$scopeId];
        } elseif ($level === 'National') {
            $sql = "SELECT COALESCE(SUM(amount), 0) AS total FROM FundAllocation
                    WHERE from_level = 'National' AND status = 'Completed' AND {$dateOp}";
        } else {
            $sql = "SELECT COALESCE(SUM(amount), 0) AS total FROM FundAllocation
                    WHERE from_level = :lvl AND from_id = :sid AND status = 'Completed' AND {$dateOp}";
            $params += ['lvl' => $level, 'sid' => (int)$scopeId];
        }

        $row = $this->single($sql, $params);
        return (float)($row->total ?? 0);
    }

    /**
     * PHASE 3 — Rebuild the red flag set from real ledger data:
     *
     *   1. MathMismatch   — Phase 2 equation failed (critical).
     *   2. MissingReceipt — Expense > threshold with no receipt attachment.
     *   3. FundHoarding   — Transfers received largely unspent (>= hoarding margin).
     *   4. HighVoidRate   — Voided entries exceed the allowed rate.
     *
     * Existing flag statuses (e.g. ClarificationRequested) are preserved for
     * flags that remain valid; flags whose condition no longer holds are removed.
     */
    private function detectRedFlags($auditId, $ledgerId, $year, $receiptThreshold, $hoardingMargin, $voidRateThreshold, array $math) {
        $auditId = (int)$auditId;

        $existing = $this->resultSet("SELECT * FROM RedFlag WHERE audit_id = :aid", ['aid' => $auditId]);
        $byEntry = [];
        $standing = []; // entry-less flags keyed by type
        foreach ($existing as $ef) {
            if ($ef->entry_id) {
                $byEntry[(int)$ef->entry_id] = $ef;
            } else {
                $standing[$ef->flag_type] = $ef;
            }
        }

        // -------------------------------------------------------------
        // 1. Math mismatch (Phase 2 critical flag)
        // -------------------------------------------------------------
        if ($math['variance'] != 0 && abs($math['variance']) >= 0.01) {
            $desc = sprintf(
                'Ledger calculation error detected. Expected closing balance LKR %s but the ledger reports LKR %s (variance LKR %s).',
                number_format($math['expected'], 2),
                number_format($math['actual'], 2),
                number_format($math['variance'], 2)
            );
            $this->upsertStandingFlag($auditId, 'MathMismatch', $desc, $standing);
        } else {
            $this->dropStandingFlag($auditId, 'MathMismatch', $standing);
        }

        // -------------------------------------------------------------
        // 2. Missing receipts (Expense > threshold, receipt NULL)
        // -------------------------------------------------------------
        $validEntryIds = [];
        if ($ledgerId > 0) {
            $missingEntries = $this->resultSet("SELECT entry_id, amount, description, category, date FROM LedgerEntry
                                                WHERE ledger_id = :lid
                                                  AND type = 'Expense'
                                                  AND status = 'Approved'
                                                  AND amount > :thresh
                                                  AND (attachment_url IS NULL OR attachment_url = '')
                                                  AND YEAR(date) = :yr", [
                'lid'    => $ledgerId,
                'thresh' => $receiptThreshold,
                'yr'     => $year,
            ]);

            foreach ($missingEntries as $me) {
                $validEntryIds[] = (int)$me->entry_id;
                $desc = sprintf('%s (Ref: #ENTRY-%d) • Expense: LKR %s (exceeds LKR %s receipt threshold)',
                    ($me->description ?: ($me->category ?: 'Expense entry')),
                    (int)$me->entry_id,
                    number_format((float)$me->amount, 2),
                    number_format($receiptThreshold, 2)
                );

                if (isset($byEntry[(int)$me->entry_id])) {
                    $this->query("UPDATE RedFlag SET description = :d WHERE red_flag_id = :fid",
                        ['d' => $desc, 'fid' => $byEntry[(int)$me->entry_id]->red_flag_id]);
                } else {
                    $this->query("INSERT INTO RedFlag (audit_id, entry_id, flag_type, description, status)
                                  VALUES (:aid, :eid, 'MissingReceipt', :desc, 'Open')", [
                        'aid'  => $auditId,
                        'eid'  => (int)$me->entry_id,
                        'desc' => $desc,
                    ]);
                }
            }
        }

        // Remove missing-receipt flags for entries that now carry a receipt.
        foreach ($byEntry as $entryId => $flag) {
            if ($flag->flag_type === 'MissingReceipt' && !in_array($entryId, $validEntryIds, true)) {
                $this->query("DELETE FROM RedFlag WHERE red_flag_id = :fid", ['fid' => $flag->red_flag_id]);
            }
        }

        // -------------------------------------------------------------
        // 3. Fund hoarding — transfers received but largely unspent
        // -------------------------------------------------------------
        $transfersIn = (float)$math['transfers_in'];
        if ($transfersIn > 0) {
            $idle = $transfersIn - (float)$math['expenses'];
            $idlePct = round(($idle / $transfersIn) * 100, 1);
            if ($idle > 0 && $idlePct >= $hoardingMargin) {
                $desc = sprintf(
                    'Fund hoarding detected: LKR %s was received in transfers but only LKR %s was logged as expenses — %s%% of transferred funds remain idle (threshold %s%%).',
                    number_format($transfersIn, 2),
                    number_format($math['expenses'], 2),
                    number_format($idlePct, 1),
                    number_format($hoardingMargin, 1)
                );
                $this->upsertStandingFlag($auditId, 'FundHoarding', $desc, $standing);
            } else {
                $this->dropStandingFlag($auditId, 'FundHoarding', $standing);
            }
        } else {
            $this->dropStandingFlag($auditId, 'FundHoarding', $standing);
        }

        // -------------------------------------------------------------
        // 4. High void rate — voided vs active entries
        // -------------------------------------------------------------
        if ($ledgerId > 0) {
            $counts = $this->single("SELECT
                    SUM(CASE WHEN status = 'Voided' THEN 1 ELSE 0 END)  AS voided,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved
                 FROM LedgerEntry WHERE ledger_id = :lid AND YEAR(date) = :yr", ['lid' => $ledgerId, 'yr' => $year]);

            $voided  = (int)($counts->voided ?? 0);
            $active  = (int)($counts->approved ?? 0);
            $rate    = ($voided + $active) > 0 ? round(($voided / ($voided + $active)) * 100, 1) : 0.0;

            if ($rate > $voidRateThreshold) {
                $desc = sprintf(
                    'High void rate detected: %d of %d ledger entries were voided this year (%s%% — threshold %s%%). Frequent reversals suggest weak financial recording discipline.',
                    $voided, $voided + $active, number_format($rate, 1), number_format($voidRateThreshold, 1)
                );
                $this->upsertStandingFlag($auditId, 'HighVoidRate', $desc, $standing);
            } else {
                $this->dropStandingFlag($auditId, 'HighVoidRate', $standing);
            }
        } else {
            $this->dropStandingFlag($auditId, 'HighVoidRate', $standing);
        }
    }

    /** Insert or refresh an entry-less standing flag, preserving its status. */
    private function upsertStandingFlag($auditId, $type, $description, array &$standing) {
        if (isset($standing[$type])) {
            $this->query("UPDATE RedFlag SET description = :d WHERE red_flag_id = :fid",
                ['d' => $description, 'fid' => $standing[$type]->red_flag_id]);
        } else {
            $this->query("INSERT INTO RedFlag (audit_id, entry_id, flag_type, description, status)
                          VALUES (:aid, NULL, :type, :desc, 'Open')", [
                'aid'  => $auditId,
                'type' => $type,
                'desc' => $description,
            ]);
        }
    }

    /** Remove a standing flag whose condition no longer holds. */
    private function dropStandingFlag($auditId, $type, array &$standing) {
        if (isset($standing[$type])) {
            $this->query("DELETE FROM RedFlag WHERE red_flag_id = :fid", ['fid' => $standing[$type]->red_flag_id]);
            unset($standing[$type]);
        }
    }

    /**
     * Count unresolved (Open / ClarificationRequested) flags for an audit.
     */
    public function countUnresolvedFlags($auditId) {
        $row = $this->single("SELECT COUNT(*) AS total FROM RedFlag
                              WHERE audit_id = :aid AND status IN ('Open','ClarificationRequested')", ['aid' => (int)$auditId]);
        return (int)($row->total ?? 0);
    }

    /**
     * PHASE 4 — Dispatch formal clarification request to the entity's
     * treasurer & coordinator. Audit status stays Pending.
     */
    public function requestClarification($auditId, array $flagIds, $queryText, $adminUserId, $deadlineDays = 7) {
        $audit = $this->getAuditById($auditId);
        if (!$audit) {
            return ['success' => false, 'message' => 'Audit record not found.'];
        }
        if ($audit->locked) {
            return ['success' => false, 'message' => 'This audit is already signed off and locked.'];
        }

        $queryText = trim($queryText);
        if ($queryText === '') {
            $queryText = 'Audit flagged items on your ledger. Please provide the missing receipts or a justification within the deadline.';
        }

        $recipients = [];
        if (!empty($audit->scope_details->treasurer_id))   $recipients[] = (int)$audit->scope_details->treasurer_id;
        if (!empty($audit->scope_details->coordinator_id)) $recipients[] = (int)$audit->scope_details->coordinator_id;

        if (empty($recipients)) {
            $fallback = $this->resultSet("SELECT user_id FROM User WHERE role IN ('ZonalTreasurer','ZonalCoordinator','DivisionalTreasurer','DivisionalCoordinator') AND status = 'Active' LIMIT 2");
            foreach ($fallback as $fu) $recipients[] = (int)$fu->user_id;
        }

        // Targeted flags (fallback: every unresolved flag on the audit).
        if (!empty($flagIds)) {
            $inClause = implode(',', array_map('intval', $flagIds));
            $flagRows = $this->resultSet("SELECT * FROM RedFlag WHERE red_flag_id IN ($inClause) AND audit_id = :aid", ['aid' => $auditId]);
        } else {
            $flagRows = $this->resultSet("SELECT * FROM RedFlag WHERE audit_id = :aid AND status IN ('Open','ClarificationRequested')", ['aid' => $auditId]);
        }

        $flaggedItems = [];
        foreach ($flagRows as $fr) {
            $flaggedItems[] = sprintf('[%s] %s', str_replace('MissingReceipt', 'Missing Receipt', $fr->flag_type), $fr->description);
            $this->query("UPDATE RedFlag SET status = 'ClarificationRequested' WHERE red_flag_id = :fid", ['fid' => $fr->red_flag_id]);
        }

        $itemList = implode("\n- ", $flaggedItems);
        $notifMessage = mb_substr(
            "Audit clarification required for FY {$audit->financial_year} ({$audit->scope_details->title}). " .
            "Flagged item(s):\n- {$itemList}\nPlease respond within {$deadlineDays} days.",
            0, 500
        );

        foreach ($recipients as $recipientId) {
            $this->query("INSERT INTO Notification (recipient_id, type, message, related_entity_type, related_entity_id, read_status, created_at)
                          VALUES (:rid, 'AuditClarification', :msg, 'Audit', :aid, 0, NOW())", [
                'rid' => $recipientId,
                'msg' => $notifMessage,
                'aid' => $auditId,
            ]);
        }

        $this->query("UPDATE Audit SET audit_status = 'Pending' WHERE audit_id = :aid", ['aid' => $auditId]);

        $label = $audit->scope_details->recipients_label ?: 'the designated officers';
        return [
            'success'    => true,
            'count'      => count($recipients),
            'recipients' => $label,
            'message'    => "Clarification request sent to {$label}. Audit status remains Pending until items are resolved.",
        ];
    }

    /**
     * PHASE 4 — Formal sign-off. Only allowed when the math check passed and
     * every red flag is resolved. Locks the audited financial year for that entity.
     */
    public function signOffAudit($auditId, $adminUserId) {
        $audit = $this->getAuditById($auditId);
        if (!$audit) {
            return ['success' => false, 'message' => 'Audit record not found.'];
        }
        if ($audit->locked) {
            return ['success' => false, 'message' => 'This audit is already signed off and locked.'];
        }
        if ($audit->math_check_status !== 'Passed') {
            return ['success' => false, 'message' => 'Cannot sign off: the core math check has a variance mismatch that must be resolved first.'];
        }

        $unresolved = $this->countUnresolvedFlags($auditId);
        if ($unresolved > 0) {
            return ['success' => false, 'message' => "Cannot sign off: {$unresolved} red flag(s) are still unresolved. Request clarification and resolve them first."];
        }

        $this->query("UPDATE Audit SET
                        audit_status  = 'Completed',
                        signed_off_by = :admin_id,
                        signed_off_at = NOW(),
                        locked        = 1
                      WHERE audit_id = :aid", [
            'admin_id' => $adminUserId,
            'aid'      => $auditId,
        ]);

        // Close the entity's ledger — every ledger write path already refuses
        // inactive ledgers, so this enforces the sign-off lock feature-wide.
        if ($audit->scope_level === 'National') {
            $this->query("UPDATE Ledger SET status = 'Closed' WHERE owner_level = 'National'");
        } elseif ($audit->scope_id) {
            $ownerType = ['Zonal' => 'Zone', 'Divisional' => 'Division', 'Club' => 'Club'][$audit->scope_level] ?? null;
            if ($ownerType) {
                $this->query("UPDATE Ledger SET status = 'Closed' WHERE owner_type = :t AND owner_id = :sid", [
                    't'   => $ownerType,
                    'sid' => (int)$audit->scope_id,
                ]);
            }
        }

        return [
            'success' => true,
            'message' => 'Audit signed off. The ' . $audit->financial_year . ' ledger for ' . $audit->scope_details->title . ' is now locked — no new entries or voids can be recorded.',
        ];
    }

    /**
     * Mark a specific red flag as resolved (auditor accepted the item).
     */
    public function resolveFlag($flagId) {
        return (bool)$this->query("UPDATE RedFlag SET status = 'Resolved' WHERE red_flag_id = :fid", ['fid' => (int)$flagId]);
    }

    /**
     * Audit ID that owns a red flag (for redirecting after flag actions).
     */
    public function getFlagAuditId($flagId) {
        $row = $this->single("SELECT audit_id FROM RedFlag WHERE red_flag_id = :fid", ['fid' => (int)$flagId]);
        return $row ? (int)$row->audit_id : null;
    }

    /**
     * Structured CSV export for an audit.
     */
    public function exportAuditCsv($auditId) {
        $audit = $this->getAuditById($auditId);
        if (!$audit) {
            return "Error: Audit not found";
        }

        $out = fopen('php://temp', 'w');
        fputcsv($out, ['YOUTHNEXUS ANNUAL FINANCIAL AUDIT SUMMARY']);
        fputcsv($out, ['Scope', $audit->scope_details->title]);
        fputcsv($out, ['Financial Year', $audit->financial_year]);
        fputcsv($out, ['Math Check Status', $audit->math_check_status]);
        fputcsv($out, ['Audit Status', $audit->audit_status]);
        fputcsv($out, ['Locked / Certified', $audit->locked ? 'YES' : 'NO']);
        fputcsv($out, []);

        fputcsv($out, ['CORE MATHEMATICAL LEDGER VERIFICATION']);
        fputcsv($out, ['Step', 'Description', 'Amount (LKR)']);
        fputcsv($out, ['1', 'Opening Balance', number_format($audit->opening_balance, 2)]);
        fputcsv($out, ['2', '+ Total Income (self-raised)', number_format($audit->total_income, 2)]);
        fputcsv($out, ['3', '+ Transfers Received', number_format($audit->total_transfers_received, 2)]);
        fputcsv($out, ['4', '- Total Expenses', number_format($audit->total_expenses, 2)]);
        fputcsv($out, ['5', '- Transfers Distributed', number_format($audit->total_transfers_distributed, 2)]);
        fputcsv($out, ['6', '= Expected Closing Balance', number_format($audit->expected_closing_balance, 2)]);
        fputcsv($out, ['7', 'Actual Closing Balance (ledger)', number_format($audit->actual_closing_balance, 2)]);
        fputcsv($out, ['8', 'Variance', number_format($audit->actual_closing_balance - $audit->expected_closing_balance, 2)]);
        fputcsv($out, []);

        fputcsv($out, ['AUDIT EXCEPTIONS & FLAGGED ITEMS']);
        fputcsv($out, ['ID', 'Type', 'Status', 'Description', 'Flagged Date']);
        foreach ($audit->red_flags as $rf) {
            fputcsv($out, [$rf->red_flag_id, $rf->flag_type, $rf->status, $rf->description, $rf->flagged_at]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }
}
