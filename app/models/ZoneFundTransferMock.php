<?php

/** Session-only adapter for the shared NYSC fund transfer screen. */
class ZoneFundTransferMock {
    private function &state() {
        $key = (string)($_SESSION['user_id'] ?? 'demo');
        if (!isset($_SESSION['zonal_funds_demo'][$key])) {
            $_SESSION['zonal_funds_demo'][$key] = [
                'received' => 150000000, 'balance' => 150000000,
                'divisions' => [1 => 0, 2 => 0, 3 => 0],
                'transfers' => [], 'entries' => [],
            ];
        }
        return $_SESSION['zonal_funds_demo'][$key];
    }

    public function divisions() {
        return array_map(static function ($id, $name) {
            return (object)['zonal_id' => $id, 'zonal_name' => $name, 'province' => 'Gampaha Zone', 'hub_name' => ''];
        }, [1, 2, 3], ['Gampaha Division', 'Ja-Ela Division', 'Negombo Division']);
    }

    public function account() {
        return (object)['bank_account_id' => 1, 'bank_name' => 'Demo zonal account',
            'branch_name' => 'Gampaha', 'account_number' => 'DEMO-ZONE-001',
            'account_label' => 'Gampaha Zone — NYSC funds received', 'gateway_type' => 'Demo'];
    }

    public function snapshot() { return $this->state(); }

    public function reference($method = 'RTGS') {
        return ($method === 'ChequeSLIPS' ? 'ZCHQ-' : 'ZTRF-') . date('Y') . '-' . str_pad(count($this->state()['transfers']) + 1, 4, '0', STR_PAD_LEFT);
    }

    public function transfers(array $filters = []) {
        return array_values(array_filter(array_reverse($this->state()['transfers']), static function ($t) use ($filters) {
            return (empty($filters['zone_id']) || (string)$t->target_zonal_id === (string)$filters['zone_id'])
                && (empty($filters['status']) || $filters['status'] === 'All' || $t->status === $filters['status'])
                && (empty($filters['quarter']) || 'Q' . ceil((int)date('n', strtotime($t->transfer_date)) / 3) === $filters['quarter'])
                && (empty($filters['search']) || stripos($t->reference_no . ' ' . $t->target_zone_name . ' ' . $t->purpose_description, $filters['search']) !== false);
        }));
    }

    public function stats() {
        $s = $this->state();
        $quarter = 'Q' . ceil((int)date('n') / 3);
        return ['quarter_label' => $quarter . ' ' . date('Y'),
            'quarter_total' => array_sum(array_map(static fn($t) => $t->amount, $this->transfers(['quarter' => $quarter]))),
            'in_flight_count' => 0, 'in_flight_total' => 0,
            'budget_cap' => $s['received'] / 100, 'year_total' => ($s['received'] - $s['balance']) / 100,
            'remaining_budget' => $s['balance'] / 100,
            'utilized_pct' => round(($s['received'] - $s['balance']) * 100 / $s['received']),
            'core_account' => $this->account()];
    }

    public function create(array $input) {
        $s =& $this->state();
        $division = null;
        foreach ($this->divisions() as $candidate) {
            if ((string)$candidate->zonal_id === (string)($input['zone_id'] ?? '')) $division = $candidate;
        }
        if (!$division) throw new InvalidArgumentException('Select a division under Gampaha Zone.');
        if ((string)($input['bank_account_id'] ?? '') !== '1') throw new InvalidArgumentException('Select the zonal source account.');
        $raw = str_replace(',', '', trim((string)($input['amount'] ?? '')));
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $raw)) throw new InvalidArgumentException('Enter a valid amount with at most two decimal places.');
        $cents = (int)round((float)$raw * 100);
        if ($cents <= 0 || $cents > $s['balance']) throw new InvalidArgumentException('Amount must be positive and within the available zonal balance.');
        $date = (string)($input['transfer_date'] ?? '');
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) throw new InvalidArgumentException('Enter a valid transfer date.');
        $purpose = trim((string)($input['purpose'] ?? ''));
        if ($purpose === '' || strlen($purpose) > 1000) throw new InvalidArgumentException('Purpose is required (maximum 1000 characters).');
        $method = $input['disbursement_method'] ?? '';
        if (!in_array($method, ['RTGS', 'ChequeSLIPS'], true)) throw new InvalidArgumentException('Select a valid transfer method.');
        $ref = trim((string)($input['reference'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9-]{1,60}$/D', $ref)) throw new InvalidArgumentException('Enter a reference using letters, numbers and hyphens.');
        foreach ($s['transfers'] as $existing) {
            if (strcasecmp($existing->reference_no, $ref) === 0) throw new InvalidArgumentException('This reference has already been used.');
        }
        $id = count($s['transfers']) + 1;
        $account = $this->account();
        $transfer = (object)[
            'allocation_id' => $id, 'target_zonal_id' => $division->zonal_id,
            'from_level' => 'Zonal', 'to_level' => 'Divisional',
            'target_zone_name' => $division->zonal_name, 'target_province' => 'Gampaha Zone', 'target_hub_name' => '',
            'amount' => $cents / 100, 'transfer_date' => $date, 'reference_no' => $ref,
            'disbursement_method' => $method, 'purpose_description' => $purpose,
            'status' => 'Completed', 'created_at' => date('Y-m-d H:i:s'),
            'bank_name' => $account->bank_name, 'branch_name' => $account->branch_name,
            'account_number' => $account->account_number, 'bank_account_name' => $account->account_label,
            'authorized_by_name' => $_SESSION['user_name'] ?? 'Zonal Treasurer', 'authorizer_role' => 'Zonal Treasurer',
        ];
        $s['transfers'][$id] = $transfer;
        $s['balance'] -= $cents;
        $s['divisions'][$division->zonal_id] += $cents;
        $s['entries'][] = ['allocation_id' => $id, 'owner_level' => 'Zonal', 'type' => 'Expense', 'amount_cents' => $cents];
        $s['entries'][] = ['allocation_id' => $id, 'owner_level' => 'Divisional', 'owner_id' => $division->zonal_id, 'type' => 'Income', 'amount_cents' => $cents];
        return $transfer;
    }

    public function find($id) { return $this->state()['transfers'][(int)$id] ?? null; }
}
