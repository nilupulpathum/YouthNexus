<?php

/**
 * FundTransferModel
 *
 * Manages FundAllocation records, BankAccount lookups,
 * FundBudget tracking, and paired double-entry accounting in Ledger & LedgerEntry.
 */
class FundTransferModel extends Model {

    /**
     * Get all fund allocations with optional filters.
     *
     * @param  array $filters  search, zone_id, status, quarter, date_from, date_to
     * @return array
     */
    public function getTransfers(array $filters = []) {
        $sql = "SELECT
                    fa.allocation_id,
                    fa.allocation_id AS transfer_id,
                    fa.from_level,
                    fa.from_id,
                    fa.to_level,
                    fa.to_id,
                    fa.to_id AS target_zonal_id,
                    fa.amount,
                    fa.transfer_date,
                    fa.reference_no,
                    fa.reference_no AS reference_number,
                    fa.disbursement_method,
                    fa.purpose_description,
                    fa.purpose_description AS purpose,
                    fa.status,
                    fa.authorized_by,
                    fa.created_at,
                    z.zonal_name           AS target_zone_name,
                    z.province             AS target_province,
                    z.hub_name             AS target_hub_name,
                    ba.bank_name,
                    ba.branch_name,
                    ba.account_number,
                    ba.account_label       AS bank_account_name,
                    ba.gateway_type,
                    ba.verification_status,
                    CONCAT(u.first_name, ' ', u.last_name) AS authorized_by_name,
                    u.role                 AS authorizer_role
                FROM FundAllocation fa
                LEFT JOIN Zone z        ON (fa.to_level = 'Zonal' AND fa.to_id = z.zonal_id)
                LEFT JOIN BankAccount ba ON fa.source_bank_account_id = ba.bank_account_id
                LEFT JOIN User u         ON fa.authorized_by = u.user_id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['zone_id'])) {
            $sql .= " AND fa.to_id = :zone_id";
            $params['zone_id'] = (int)$filters['zone_id'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $sql .= " AND fa.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND fa.transfer_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND fa.transfer_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['quarter'])) {
            switch ($filters['quarter']) {
                case 'Q1': $sql .= " AND MONTH(fa.transfer_date) BETWEEN 1 AND 3"; break;
                case 'Q2': $sql .= " AND MONTH(fa.transfer_date) BETWEEN 4 AND 6"; break;
                case 'Q3': $sql .= " AND MONTH(fa.transfer_date) BETWEEN 7 AND 9"; break;
                case 'Q4': $sql .= " AND MONTH(fa.transfer_date) BETWEEN 10 AND 12"; break;
            }
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (fa.reference_no LIKE :s_ref
                           OR z.zonal_name LIKE :s_zone
                           OR z.province LIKE :s_prov
                           OR z.hub_name LIKE :s_hub
                           OR fa.purpose_description LIKE :s_purp
                           OR ba.bank_name LIKE :s_bank
                           OR ba.account_number LIKE :s_acc)";
            $params['s_ref']  = $search;
            $params['s_zone'] = $search;
            $params['s_prov'] = $search;
            $params['s_hub']  = $search;
            $params['s_purp'] = $search;
            $params['s_bank'] = $search;
            $params['s_acc']  = $search;
        }

        $sql .= " ORDER BY fa.transfer_date DESC, fa.allocation_id DESC";

        return $this->resultSet($sql, $params);
    }

    /**
     * Find a single allocation by ID.
     *
     * @param  int $allocationId
     * @return object|false
     */
    public function findById($allocationId) {
        $sql = "SELECT
                    fa.allocation_id,
                    fa.allocation_id AS transfer_id,
                    fa.from_level,
                    fa.from_id,
                    fa.to_level,
                    fa.to_id,
                    fa.to_id AS target_zonal_id,
                    fa.amount,
                    fa.transfer_date,
                    fa.reference_no,
                    fa.reference_no AS reference_number,
                    fa.disbursement_method,
                    fa.purpose_description,
                    fa.purpose_description AS purpose,
                    fa.status,
                    fa.authorized_by,
                    fa.created_at,
                    z.zonal_name           AS target_zone_name,
                    z.province             AS target_province,
                    z.hub_name             AS target_hub_name,
                    ba.bank_name,
                    ba.branch_name,
                    ba.account_number,
                    ba.account_label       AS bank_account_name,
                    ba.gateway_type,
                    ba.verification_status,
                    CONCAT(u.first_name, ' ', u.last_name) AS authorized_by_name,
                    u.role                 AS authorizer_role
                FROM FundAllocation fa
                LEFT JOIN Zone z        ON (fa.to_level = 'Zonal' AND fa.to_id = z.zonal_id)
                LEFT JOIN BankAccount ba ON fa.source_bank_account_id = ba.bank_account_id
                LEFT JOIN User u         ON fa.authorized_by = u.user_id
                WHERE fa.allocation_id = ?
                LIMIT 1";

        return $this->single($sql, [(int)$allocationId]);
    }

    /**
     * Aggregate statistics for stat cards:
     * 1. Quarter fiscal window total
     * 2. In-flight transfers count & amount
     * 3. Annual budget cap, utilized, remaining & percentage
     * 4. Core bank account details
     *
     * @return array
     */
    public function getStats() {
        $currentYear  = (int)date('Y');
        $currentMonth = (int)date('n');
        $quarterNum   = ceil($currentMonth / 3);
        $quarterCode  = 'Q' . $quarterNum;
        $qStartMonth  = (($quarterNum - 1) * 3) + 1;
        $qEndMonth    = $qStartMonth + 2;

        // 1. Current quarter total disbursed from National
        $qTotal = $this->single(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM FundAllocation
             WHERE from_level = 'National'
               AND YEAR(transfer_date) = ?
               AND MONTH(transfer_date) BETWEEN ? AND ?",
            [$currentYear, $qStartMonth, $qEndMonth]
        );

        // 2. In-flight (Processing) transfers
        $inFlight = $this->single(
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total
             FROM FundAllocation
             WHERE status = 'Processing'"
        );

        // 3. Fiscal Year Total disbursed
        $yearTotal = $this->single(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM FundAllocation
             WHERE from_level = 'National'
               AND YEAR(transfer_date) = ?",
            [$currentYear]
        );

        // 4. Budget cap from FundBudget table
        $budgetRow = $this->single(
            "SELECT total_cap FROM FundBudget
             WHERE fiscal_year = ? AND quarter = ?
             LIMIT 1",
            [$currentYear, $quarterCode]
        );

        $budgetCap = $budgetRow ? (float)$budgetRow->total_cap : 45000000.00;
        $yearTotalAmt = (float)($yearTotal->total ?? 0);
        $remaining = max(0, $budgetCap - $yearTotalAmt);
        $utilizedPct = $budgetCap > 0 ? min(100, round(($yearTotalAmt / $budgetCap) * 100)) : 0;

        // 5. Verified Core Bank Account
        $coreAccount = $this->single(
            "SELECT * FROM BankAccount
             WHERE owner_level = 'National' AND status = 'Active'
             ORDER BY verification_status = 'Verified' DESC, bank_account_id ASC
             LIMIT 1"
        );

        return [
            'fiscal_year'        => $currentYear,
            'quarter_label'      => $quarterCode . ' ' . $currentYear,
            'quarter_total'      => (float)($qTotal->total ?? 0),
            'in_flight_count'    => (int)($inFlight->cnt ?? 0),
            'in_flight_total'    => (float)($inFlight->total ?? 0),
            'year_total'         => $yearTotalAmt,
            'budget_cap'         => $budgetCap,
            'remaining_budget'   => $remaining,
            'utilized_pct'       => $utilizedPct,
            'annual_budget'      => $budgetCap,
            'core_account'       => $coreAccount,
        ];
    }

    /**
     * Create a new FundAllocation with paired double-entry LedgerEntries:
     * - Outflow (Expense) in National Ledger
     * - Inflow (Income) in Destination Zone Ledger
     * - Balance updates on both ledgers
     *
     * @param  array $data
     * @return int   Created allocation_id
     * @throws Exception
     */
    public function createTransfer(array $data) {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Insert FundAllocation record
            $sqlAlloc = "INSERT INTO FundAllocation (
                            from_level,
                            from_id,
                            to_level,
                            to_id,
                            source_bank_account_id,
                            amount,
                            transfer_date,
                            reference_no,
                            disbursement_method,
                            purpose_description,
                            status,
                            authorized_by,
                            created_at
                        ) VALUES (
                            'National',
                            NULL,
                            'Zonal',
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            NOW()
                        )";

            $stmtAlloc = $pdo->prepare($sqlAlloc);
            $stmtAlloc->execute([
                (int)$data['target_zonal_id'],
                (int)$data['source_account_id'],
                (float)$data['amount'],
                $data['transfer_date'],
                $data['reference_no'],
                $data['disbursement_method'],
                $data['purpose_description'],
                $data['status'] ?? 'Processing',
                (int)$data['authorized_by'],
            ]);

            $allocationId = (int)$pdo->lastInsertId();

            // 2. Fetch destination Zone name for ledger entry description
            $stmtZone = $pdo->prepare("SELECT zonal_name FROM Zone WHERE zonal_id = ?");
            $stmtZone->execute([(int)$data['target_zonal_id']]);
            $zoneName = $stmtZone->fetchColumn() ?: ('Zone #' . $data['target_zonal_id']);

            // 3. Locate or create National Ledger
            $stmtNat = $pdo->query("SELECT ledger_id FROM Ledger WHERE owner_level = 'National' LIMIT 1");
            $natLedgerId = $stmtNat->fetchColumn();

            if (!$natLedgerId) {
                $pdo->exec("INSERT INTO Ledger (owner_type, owner_level, owner_id, current_balance, status, created_at)
                            VALUES ('Zone', 'National', 0, 100000000.00, 'Active', NOW())");
                $natLedgerId = (int)$pdo->lastInsertId();
            }

            // 4. Locate or create Destination Zone Ledger
            $stmtZoneLedger = $pdo->prepare("SELECT ledger_id FROM Ledger WHERE owner_type = 'Zone' AND owner_id = ? LIMIT 1");
            $stmtZoneLedger->execute([(int)$data['target_zonal_id']]);
            $zoneLedgerId = $stmtZoneLedger->fetchColumn();

            if (!$zoneLedgerId) {
                $stmtNewZL = $pdo->prepare("INSERT INTO Ledger (owner_type, owner_level, owner_id, current_balance, status, created_at)
                                            VALUES ('Zone', 'Zonal', ?, 0.00, 'Active', NOW())");
                $stmtNewZL->execute([(int)$data['target_zonal_id']]);
                $zoneLedgerId = (int)$pdo->lastInsertId();
            }

            // 5. Insert paired LedgerEntry #1: National Outflow (Expense)
            $stmtEntry = $pdo->prepare("INSERT INTO LedgerEntry (
                                            ledger_id,
                                            amount,
                                            type,
                                            category,
                                            description,
                                            status,
                                            date,
                                            created_by,
                                            created_at,
                                            allocation_id
                                        ) VALUES (?, ?, ?, ?, ?, 'Approved', ?, ?, NOW(), ?)");

            $stmtEntry->execute([
                $natLedgerId,
                (float)$data['amount'],
                'Expense',
                'Zonal Fund Allocation',
                'Disbursement to ' . $zoneName . ' (' . $data['reference_no'] . ')',
                $data['transfer_date'],
                (int)$data['authorized_by'],
                $allocationId,
            ]);

            // Deduct from National Ledger
            $stmtUpdNat = $pdo->prepare("UPDATE Ledger SET current_balance = current_balance - ? WHERE ledger_id = ?");
            $stmtUpdNat->execute([(float)$data['amount'], $natLedgerId]);

            // 6. Insert paired LedgerEntry #2: Destination Zone Inflow (Income)
            $stmtEntry->execute([
                $zoneLedgerId,
                (float)$data['amount'],
                'Income',
                'National Grant / Zonal Allocation',
                'Allocation from NYSC National Admin (' . $data['reference_no'] . ') — ' . $data['purpose_description'],
                $data['transfer_date'],
                (int)$data['authorized_by'],
                $allocationId,
            ]);

            // Add to Destination Zone Ledger
            $stmtUpdZone = $pdo->prepare("UPDATE Ledger SET current_balance = current_balance + ? WHERE ledger_id = ?");
            $stmtUpdZone->execute([(float)$data['amount'], $zoneLedgerId]);

            // Commit transaction
            $pdo->commit();

            return $allocationId;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Generate sequential reference number matching method:
     * RTGS         → TRF-YYYY-XXXX
     * ChequeSLIPS  → CHQ-YYYY-XXXX
     *
     * @param  string $method  'RTGS' or 'ChequeSLIPS'
     * @return string
     */
    public function generateReferenceNumber(string $method = 'RTGS') {
        $prefix = (strtoupper($method) === 'RTGS') ? 'TRF' : 'CHQ';
        $year   = date('Y');
        $like   = "{$prefix}-{$year}-%";

        $last = $this->single(
            "SELECT reference_no FROM FundAllocation
             WHERE reference_no LIKE ?
             ORDER BY allocation_id DESC LIMIT 1",
            [$like]
        );

        $nextSeq = 1;
        if ($last && !empty($last->reference_no)) {
            $parts = explode('-', $last->reference_no);
            $num   = (int)end($parts);
            if ($num > 0) {
                $nextSeq = $num + 1;
            }
        }

        return "{$prefix}-{$year}-" . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Fetch all active zones with formatted display names and details.
     *
     * @return array
     */
    public function getAllZones() {
        return $this->resultSet(
            "SELECT zonal_id, zonal_name, province, hub_name
             FROM Zone
             ORDER BY zonal_name ASC"
        );
    }

    /**
     * Fetch active national disbursement bank accounts.
     *
     * @return array
     */
    public function getActiveBankAccounts() {
        return $this->resultSet(
            "SELECT bank_account_id, bank_name, branch_name, account_number, account_label, gateway_type, verification_status, status
             FROM BankAccount
             WHERE status = 'Active'
             ORDER BY (owner_level = 'National') DESC, (verification_status = 'Verified') DESC, bank_account_id ASC"
        );
    }

    /**
     * Fetch destination ledger and entries for a zone (used by Zonal Treasurer or audit).
     *
     * @param  int $zoneId
     * @return object|null
     */
    public function getZoneLedger($zoneId) {
        return $this->single(
            "SELECT l.*, z.zonal_name, z.province
             FROM Ledger l
             JOIN Zone z ON l.owner_id = z.zonal_id
             WHERE l.owner_type = 'Zone' AND l.owner_id = ?
             LIMIT 1",
            [(int)$zoneId]
        );
    }
}
