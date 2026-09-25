<?php

/**
 * Zonal fund allocation (D10). Zone -> Division disbursements over
 * FundAllocation / Ledger / LedgerEntry / BankAccount, scoped to the
 * treasurer's zone. Mirrors FundTransferModel's paired-entry pattern.
 */
class ZoneFundModel extends Model {

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

    public function getBankAccounts(int $zonalId): array {
        return $this->resultSet(
            "SELECT bank_account_id, bank_name, branch_name, account_number, account_label
             FROM BankAccount
             WHERE owner_level = 'Zonal' AND owner_id = ? AND status = 'Active'
             ORDER BY bank_account_id",
            [$zonalId]
        );
    }

    public function ensureZoneLedger(int $zonalId) {
        $ledger = $this->single(
            "SELECT * FROM Ledger WHERE owner_type = 'Zone' AND owner_id = ? LIMIT 1",
            [$zonalId]
        );
        if ($ledger) {
            return $ledger;
        }
        try {
            $this->query(
                "INSERT INTO Ledger (owner_type, owner_level, owner_id, current_balance, status)
                 VALUES ('Zone', 'Zonal', ?, 0.00, 'Active')",
                [$zonalId]
            );
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }
        }
        return $this->single(
            "SELECT * FROM Ledger WHERE owner_type = 'Zone' AND owner_id = ? LIMIT 1",
            [$zonalId]
        );
    }

    public function getStats(int $zonalId): array {
        $year = date('Y');
        $quarter = (int) ceil(date('n') / 3);
        $qStart = sprintf('%d-%02d-01', $year, ($quarter - 1) * 3 + 1);
        $qEnd = date('Y-m-t', strtotime(sprintf('%d-%02d-01', $year, $quarter * 3)));
        $received = $this->single(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM FundAllocation
             WHERE to_level = 'Zonal' AND to_id = ? AND status = 'Completed'",
            [$zonalId]
        );
        $allocated = $this->single(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM FundAllocation
             WHERE from_level = 'Zonal' AND from_id = ? AND status = 'Completed'",
            [$zonalId]
        );
        $quarterTotal = $this->single(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM FundAllocation
             WHERE from_level = 'Zonal' AND from_id = ? AND status = 'Completed'
               AND transfer_date BETWEEN ? AND ?",
            [$zonalId, $qStart, $qEnd]
        );
        $ledger = $this->ensureZoneLedger($zonalId);
        $accounts = $this->getBankAccounts($zonalId);
        $receivedTotal = (float) ($received->total ?? 0);
        $allocatedTotal = (float) ($allocated->total ?? 0);
        return [
            'quarter_label' => 'Q' . $quarter . ' ' . $year,
            'quarter_total' => (float) ($quarterTotal->total ?? 0),
            'in_flight_count' => 0,
            'in_flight_total' => 0,
            'budget_cap' => $receivedTotal,
            'year_total' => $allocatedTotal,
            'remaining_budget' => (float) ($ledger->current_balance ?? 0),
            'utilized_pct' => $receivedTotal > 0 ? (int) round($allocatedTotal * 100 / $receivedTotal) : 0,
            'core_account' => $accounts[0] ?? null,
            'received' => $receivedTotal,
            'allocated' => $allocatedTotal,
            'available' => (float) ($ledger->current_balance ?? 0),
        ];
    }

    public function getTransfers(int $zonalId, array $filters = []): array {
        $sql = "SELECT fa.*, d.division_name AS target_zone_name,
                       b.account_label AS bank_label, b.bank_name, b.branch_name, b.account_number,
                       CONCAT_WS(' ', u.first_name, u.last_name) AS authorized_by_name,
                       u.role AS authorizer_role
                FROM FundAllocation fa
                LEFT JOIN Division d ON d.division_id = fa.to_id AND fa.to_level = 'Divisional'
                LEFT JOIN BankAccount b ON b.bank_account_id = fa.source_bank_account_id
                LEFT JOIN User u ON u.user_id = fa.authorized_by
                WHERE fa.from_level = 'Zonal' AND fa.from_id = ?";
        $params = [$zonalId];
        if (!empty($filters['search'])) {
            $sql .= " AND (fa.reference_no LIKE ? OR fa.purpose_description LIKE ? OR d.division_name LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['status'])) {
            $sql .= " AND fa.status = ?";
            $params[] = $filters['status'];
        }
        $sql .= " ORDER BY fa.transfer_date DESC, fa.allocation_id DESC";
        return $this->resultSet($sql, $params);
    }

    public function find(int $zonalId, int $allocationId) {
        return $this->single(
            "SELECT fa.*, d.division_name AS target_zone_name,
                    b.account_label AS bank_label, b.bank_name, b.branch_name,
                    b.account_number, z.zonal_name AS from_zone_name
             FROM FundAllocation fa
             LEFT JOIN Division d ON d.division_id = fa.to_id AND fa.to_level = 'Divisional'
             LEFT JOIN BankAccount b ON b.bank_account_id = fa.source_bank_account_id
             LEFT JOIN Zone z ON z.zonal_id = fa.from_id AND fa.from_level = 'Zonal'
             WHERE fa.allocation_id = ? AND fa.from_level = 'Zonal' AND fa.from_id = ? LIMIT 1",
            [$allocationId, $zonalId]
        );
    }

    public function generateReferenceNumber(string $method = 'RTGS'): string {
        $prefix = (strtoupper($method) === 'RTGS') ? 'TRF' : 'CHQ';
        $like = "{$prefix}-" . date('Y') . '-%';
        $last = $this->single(
            "SELECT reference_no FROM FundAllocation
             WHERE reference_no LIKE ? ORDER BY allocation_id DESC LIMIT 1",
            [$like]
        );
        $nextSeq = 1;
        if ($last && !empty($last->reference_no)) {
            $parts = explode('-', $last->reference_no);
            $num = (int) end($parts);
            if ($num > 0) {
                $nextSeq = $num + 1;
            }
        }
        return "{$prefix}-" . date('Y') . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Allocate zone funds to a division. Transactional with paired ledger
     * entries and balance guard. Returns the new allocation_id.
     */
    public function createAllocation(int $zonalId, int $userId, array $data): int {
        $divisionId = (int) ($data['division_id'] ?? 0);
        $bankId = (int) ($data['bank_account_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);
        $date = trim($data['transfer_date'] ?? '');
        $method = in_array($data['method'] ?? '', ['RTGS', 'ChequeSLIPS'], true) ? $data['method'] : 'RTGS';
        $reference = trim($data['reference'] ?? '');
        $purpose = trim($data['purpose'] ?? '');

        $division = $this->single(
            "SELECT division_id, division_name FROM Division
             WHERE division_id = ? AND zonal_id = ? LIMIT 1",
            [$divisionId, $zonalId]
        );
        if (!$division) {
            throw new InvalidArgumentException('Select a division in your zone.');
        }
        $bank = $this->single(
            "SELECT bank_account_id FROM BankAccount
             WHERE bank_account_id = ? AND owner_level = 'Zonal' AND owner_id = ? AND status = 'Active' LIMIT 1",
            [$bankId, $zonalId]
        );
        if (!$bank) {
            throw new InvalidArgumentException('Select a valid zonal bank account.');
        }
        if ($amount <= 0) {
            throw new InvalidArgumentException('Enter a positive amount.');
        }
        if ($date === '' || !strtotime($date)) {
            throw new InvalidArgumentException('Enter a valid transfer date.');
        }
        if ($purpose === '') {
            throw new InvalidArgumentException('A purpose is required.');
        }
        if ($reference === '') {
            $reference = $this->generateReferenceNumber($method);
        }
        $dup = $this->single("SELECT allocation_id FROM FundAllocation WHERE reference_no = ? LIMIT 1", [$reference]);
        if ($dup) {
            throw new InvalidArgumentException('Reference number already used. Refresh for a new one.');
        }

        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $zoneLedger = $pdo->prepare("SELECT ledger_id, current_balance FROM Ledger WHERE owner_type = 'Zone' AND owner_id = ? FOR UPDATE");
            $zoneLedger->execute([$zonalId]);
            $zl = $zoneLedger->fetch(PDO::FETCH_ASSOC);
            if (!$zl) {
                throw new RuntimeException('No ledger exists for your zone.');
            }
            if ((float) $zl['current_balance'] < $amount) {
                throw new InvalidArgumentException('Insufficient zonal balance for this allocation.');
            }

            $alloc = $pdo->prepare(
                "INSERT INTO FundAllocation (from_level, from_id, to_level, to_id,
                    source_bank_account_id, amount, transfer_date, reference_no,
                    disbursement_method, purpose_description, status, authorized_by, created_at)
                 VALUES ('Zonal', ?, 'Divisional', ?, ?, ?, ?, ?, ?, ?, 'Completed', ?, NOW())"
            );
            $alloc->execute([$zonalId, $divisionId, $bankId, $amount, $date, $reference, $method, $purpose, $userId]);
            $allocationId = (int) $pdo->lastInsertId();

            $out = $pdo->prepare(
                "INSERT INTO LedgerEntry (ledger_id, amount, type, category, description, status, date, created_by, created_at, allocation_id)
                 VALUES (?, ?, 'Expense', 'Allocation', ?, 'Approved', ?, ?, NOW(), ?)"
            );
            $out->execute([(int) $zl['ledger_id'], $amount, "Allocation to {$division->division_name} ({$reference}) - {$purpose}", $date, $userId, $allocationId]);

            $divLedger = $pdo->prepare("SELECT ledger_id FROM Ledger WHERE owner_type = 'Division' AND owner_id = ? LIMIT 1");
            $divLedger->execute([$divisionId]);
            $dlId = $divLedger->fetchColumn();
            if (!$dlId) {
                $newDl = $pdo->prepare("INSERT INTO Ledger (owner_type, owner_level, owner_id, current_balance, status) VALUES ('Division', 'Divisional', ?, 0.00, 'Active')");
                $newDl->execute([$divisionId]);
                $dlId = (int) $pdo->lastInsertId();
            }
            $in = $pdo->prepare(
                "INSERT INTO LedgerEntry (ledger_id, amount, type, category, description, status, date, created_by, created_at, allocation_id)
                 VALUES (?, ?, 'Income', 'Allocation', ?, 'Approved', ?, ?, NOW(), ?)"
            );
            $in->execute([(int) $dlId, $amount, "Allocation from zone ({$reference}) - {$purpose}", $date, $userId, $allocationId]);

            $updZone = $pdo->prepare("UPDATE Ledger SET current_balance = current_balance - ? WHERE ledger_id = ?");
            $updZone->execute([$amount, (int) $zl['ledger_id']]);
            $updDiv = $pdo->prepare("UPDATE Ledger SET current_balance = current_balance + ? WHERE ledger_id = ?");
            $updDiv->execute([$amount, (int) $dlId]);

            $audit = $pdo->prepare("INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details) VALUES (?, 'ALLOCATE_FUNDS', 'FundAllocation', ?, ?)");
            $audit->execute([$userId, $allocationId, "Allocated {$amount} to {$division->division_name} ({$reference})"]);
            $pdo->commit();
            return $allocationId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
