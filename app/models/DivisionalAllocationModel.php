<?php

class DivisionalAllocationModel extends Model {
    public function getClubs(int $divisionId): array {
        return $this->resultSet(
            "SELECT club_id, club_name, status
             FROM Club
             WHERE division_id = ? AND status IN ('Active', 'Flagged')
             ORDER BY club_name",
            [$divisionId]
        );
    }

    public function getSourceAccount(int $divisionId) {
        return $this->single(
            "SELECT bank_account_id, bank_name, branch_name, account_number, account_label,
                    gateway_type, verification_status
             FROM BankAccount
             WHERE owner_level = 'Divisional' AND owner_id = ? AND status = 'Active'
             ORDER BY verification_status = 'Verified' DESC, bank_account_id
             LIMIT 1",
            [$divisionId]
        );
    }

    public function getSummary(int $divisionId, int $ledgerId): array {
        $ledger = $this->single(
            'SELECT current_balance FROM Ledger WHERE ledger_id = ? LIMIT 1',
            [$ledgerId]
        );
        $pending = $this->single(
            "SELECT COUNT(*) AS total
             FROM FundAllocation fa
             INNER JOIN Club c ON fa.to_level = 'Club' AND fa.to_id = c.club_id
             WHERE fa.from_level = 'Divisional' AND fa.from_id = ?
               AND c.division_id = ? AND fa.status = 'PendingApproval'",
            [$divisionId, $divisionId]
        );

        return [
            'balance' => (float) ($ledger->current_balance ?? 0),
            'pending' => (int) ($pending->total ?? 0),
        ];
    }

    public function getPendingRequests(int $divisionId): array {
        return $this->resultSet(
            "SELECT fa.*, c.club_name
             FROM FundAllocation fa
             INNER JOIN Club c ON fa.to_level = 'Club' AND fa.to_id = c.club_id
             WHERE fa.from_level = 'Divisional' AND fa.from_id = ?
               AND c.division_id = ? AND fa.status = 'PendingApproval'
             ORDER BY fa.created_at ASC, fa.allocation_id ASC",
            [$divisionId, $divisionId]
        );
    }

    public function getHistory(int $divisionId): array {
        return $this->resultSet(
            "SELECT fa.*, c.club_name
             FROM FundAllocation fa
             INNER JOIN Club c ON fa.to_level = 'Club' AND fa.to_id = c.club_id
             WHERE fa.from_level = 'Divisional' AND fa.from_id = ?
               AND c.division_id = ? AND fa.status <> 'PendingApproval'
             ORDER BY fa.transfer_date DESC, fa.allocation_id DESC",
            [$divisionId, $divisionId]
        );
    }

    public function getCategories(int $divisionId): array {
        return $this->query(
            "SELECT DISTINCT fa.fund_category
             FROM FundAllocation fa
             INNER JOIN Club c ON fa.to_level = 'Club' AND fa.to_id = c.club_id
             WHERE fa.from_level = 'Divisional' AND fa.from_id = ?
               AND c.division_id = ?
               AND fa.fund_category IS NOT NULL AND fa.fund_category <> ''
             ORDER BY fa.fund_category",
            [$divisionId, $divisionId]
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function generateReference(): string {
        $year = date('Y');
        $last = $this->single(
            "SELECT reference_no FROM FundAllocation
             WHERE reference_no LIKE ? ORDER BY allocation_id DESC LIMIT 1",
            ['DFA-' . $year . '-%']
        );
        $sequence = 1;
        if ($last && preg_match('/(\d+)$/', (string) $last->reference_no, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }
        return 'DFA-' . $year . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function createAllocation(int $divisionId, int $ledgerId, int $userId, array $data): int {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $club = $this->lockClub($pdo, $divisionId, (int) $data['club_id']);
            $account = $this->lockSourceAccount($pdo, $divisionId, (int) $data['bank_account_id']);
            $sourceLedger = $this->lockSourceLedger($pdo, $ledgerId, $divisionId);
            $amount = (float) $data['amount'];
            if ((float) $sourceLedger->current_balance < $amount) {
                throw new RuntimeException('The division ledger does not have enough funds for this allocation.');
            }

            $reference = $this->generateReference();
            $insert = $pdo->prepare(
                "INSERT INTO FundAllocation
                    (from_level, from_id, to_level, to_id, source_bank_account_id, amount,
                     fund_category, transfer_date, reference_no, disbursement_method,
                     purpose_description, status, authorized_by, created_at)
                 VALUES ('Divisional', ?, 'Club', ?, ?, ?, ?, ?, ?, ?, ?, 'Completed', ?, NOW())"
            );
            $insert->execute([
                $divisionId,
                $club->club_id,
                $account->bank_account_id,
                $amount,
                $data['fund_category'],
                $data['transfer_date'],
                $reference,
                $data['disbursement_method'],
                $data['purpose_description'],
                $userId,
            ]);
            $allocationId = (int) $pdo->lastInsertId();
            $this->postLedgerTransfer($pdo, $ledgerId, $club, $allocationId, $reference, $amount, $data, $userId);
            $pdo->commit();
            return $allocationId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function approveRequest(int $divisionId, int $ledgerId, int $requestId, int $userId): void {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();

        try {
            $request = $this->lockRequest($pdo, $divisionId, $requestId);
            $club = $this->lockClub($pdo, $divisionId, (int) $request->to_id);
            $this->lockSourceAccount($pdo, $divisionId, (int) $request->source_bank_account_id);
            $sourceLedger = $this->lockSourceLedger($pdo, $ledgerId, $divisionId);
            if ((float) $sourceLedger->current_balance < (float) $request->amount) {
                throw new RuntimeException('The division ledger does not have enough funds for this allocation.');
            }

            $update = $pdo->prepare(
                "UPDATE FundAllocation
                 SET status = 'Completed', authorized_by = ?, transfer_date = ?
                 WHERE allocation_id = ? AND status = 'PendingApproval'"
            );
            $update->execute([$userId, date('Y-m-d'), $requestId]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('This fund request has already been reviewed.');
            }

            $data = [
                'fund_category' => $request->fund_category ?: 'Club Allocation',
                'purpose_description' => $request->purpose_description,
                'transfer_date' => date('Y-m-d'),
            ];
            $this->postLedgerTransfer(
                $pdo,
                $ledgerId,
                $club,
                $requestId,
                $request->reference_no,
                (float) $request->amount,
                $data,
                $userId
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function rejectRequest(int $divisionId, int $requestId): void {
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $this->lockRequest($pdo, $divisionId, $requestId);
            $update = $pdo->prepare(
                "UPDATE FundAllocation SET status = 'Rejected'
                 WHERE allocation_id = ? AND status = 'PendingApproval'"
            );
            $update->execute([$requestId]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('This fund request has already been reviewed.');
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function lockClub(PDO $pdo, int $divisionId, int $clubId) {
        $statement = $pdo->prepare(
            "SELECT club_id, club_name FROM Club
             WHERE club_id = ? AND division_id = ? AND status IN ('Active', 'Flagged')
             FOR UPDATE"
        );
        $statement->execute([$clubId, $divisionId]);
        $club = $statement->fetch();
        if (!$club) {
            throw new RuntimeException('Select an eligible club from your division.');
        }
        return $club;
    }

    private function lockSourceAccount(PDO $pdo, int $divisionId, int $accountId) {
        $statement = $pdo->prepare(
            "SELECT bank_account_id FROM BankAccount
             WHERE bank_account_id = ? AND owner_level = 'Divisional'
               AND owner_id = ? AND status = 'Active'
             FOR UPDATE"
        );
        $statement->execute([$accountId, $divisionId]);
        $account = $statement->fetch();
        if (!$account) {
            throw new RuntimeException('The division bank account is not available.');
        }
        return $account;
    }

    private function lockSourceLedger(PDO $pdo, int $ledgerId, int $divisionId) {
        $statement = $pdo->prepare(
            "SELECT ledger_id, current_balance FROM Ledger
             WHERE ledger_id = ? AND owner_type = 'Division' AND owner_id = ? AND status = 'Active'
             FOR UPDATE"
        );
        $statement->execute([$ledgerId, $divisionId]);
        $ledger = $statement->fetch();
        if (!$ledger) {
            throw new RuntimeException('The division ledger is not active.');
        }
        return $ledger;
    }

    private function lockRequest(PDO $pdo, int $divisionId, int $requestId) {
        $statement = $pdo->prepare(
            "SELECT fa.*
             FROM FundAllocation fa
             INNER JOIN Club c ON fa.to_level = 'Club' AND fa.to_id = c.club_id
             WHERE fa.allocation_id = ? AND fa.from_level = 'Divisional'
               AND fa.from_id = ? AND c.division_id = ? AND fa.status = 'PendingApproval'
             FOR UPDATE"
        );
        $statement->execute([$requestId, $divisionId, $divisionId]);
        $request = $statement->fetch();
        if (!$request) {
            throw new RuntimeException('This fund request is no longer pending.');
        }
        return $request;
    }

    private function postLedgerTransfer(
        PDO $pdo,
        int $sourceLedgerId,
        object $club,
        int $allocationId,
        string $reference,
        float $amount,
        array $data,
        int $userId
    ): void {
        $clubLedger = $pdo->prepare(
            "SELECT ledger_id FROM Ledger WHERE owner_type = 'Club' AND owner_id = ? LIMIT 1 FOR UPDATE"
        );
        $clubLedger->execute([$club->club_id]);
        $clubLedgerId = $clubLedger->fetchColumn();
        if (!$clubLedgerId) {
            $create = $pdo->prepare(
                "INSERT INTO Ledger (owner_type, owner_level, owner_id, current_balance, status)
                 VALUES ('Club', 'Club', ?, 0.00, 'Active')"
            );
            $create->execute([$club->club_id]);
            $clubLedgerId = (int) $pdo->lastInsertId();
        }

        $entry = $pdo->prepare(
            "INSERT INTO LedgerEntry
                (ledger_id, amount, type, category, description, status, reconciled,
                 created_by, date, created_at, allocation_id)
             VALUES (?, ?, ?, ?, ?, 'Approved', 0, ?, ?, NOW(), ?)"
        );
        $category = $data['fund_category'] ?: 'Club Allocation';
        $entry->execute([
            $sourceLedgerId,
            $amount,
            'Expense',
            $category,
            'Allocation to ' . $club->club_name . ' (' . $reference . ') - ' . $data['purpose_description'],
            $userId,
            $data['transfer_date'],
            $allocationId,
        ]);
        $entry->execute([
            $clubLedgerId,
            $amount,
            'Income',
            $category,
            'Allocation from division (' . $reference . ') - ' . $data['purpose_description'],
            $userId,
            $data['transfer_date'],
            $allocationId,
        ]);

        $debit = $pdo->prepare(
            'UPDATE Ledger SET current_balance = current_balance - ? WHERE ledger_id = ? AND status = \'Active\''
        );
        $debit->execute([$amount, $sourceLedgerId]);
        if ($debit->rowCount() !== 1) {
            throw new RuntimeException('The division balance could not be updated.');
        }
        $credit = $pdo->prepare(
            'UPDATE Ledger SET current_balance = current_balance + ? WHERE ledger_id = ? AND status = \'Active\''
        );
        $credit->execute([$amount, $clubLedgerId]);
        if ($credit->rowCount() !== 1) {
            throw new RuntimeException('The club balance could not be updated.');
        }
    }
}
