<?php

class Divisionalassets extends Controller {
    private function requireTreasurer(): void {
        if (empty($_SESSION['user_id'])) $this->redirect('auth/signin');
        if (($_SESSION['user_role'] ?? '') !== 'DivisionalTreasurer') $this->redirect('home');
        if ((int) ($_SESSION['division_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a division.');
        }
    }

    private function verifyPost(): bool {
        $token = (string) ($_POST['csrf_token'] ?? '');
        return $_SERVER['REQUEST_METHOD'] === 'POST'
            && $token !== ''
            && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function flash(string $type, string $message): void {
        $_SESSION['divisional_asset_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['divisional_asset_flash'] ?? null;
        unset($_SESSION['divisional_asset_flash']);
        return is_array($flash) ? $flash : null;
    }

    public function index(): void {
        $this->requireTreasurer();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $divisionId = (int) $_SESSION['division_id'];
        $model = $this->model('DivisionalAssetModel');
        $division = $model->getDivision($divisionId);
        if (!$division) {
            http_response_code(500);
            exit('The division asset profile could not be loaded.');
        }
        $this->view('divisionalassets/index', [
            'division' => $division,
            'summary' => $model->getSummary($divisionId),
            'catalog' => $model->getCatalog(),
            'clubs' => $model->getClubs($divisionId),
            'inventory' => $model->getInventory($divisionId),
            'clubRequests' => $model->getPendingClubRequests($divisionId),
            'transferHistory' => $model->getTransferHistory($divisionId),
            'zonalRequests' => $model->getZonalRequests($divisionId),
            'csrfToken' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
            'userName' => $_SESSION['user_name'] ?? 'Divisional Treasurer',
            'userRole' => $_SESSION['user_role'],
            'userEmail' => $_SESSION['user_email'] ?? '',
        ]);
    }

    public function add(): void {
        $this->mutate(function (DivisionalAssetModel $model, int $divisionId, int $userId): void {
            $model->addStock($divisionId, (int) ($_POST['catalog_item_id'] ?? 0), (int) ($_POST['quantity'] ?? 0),
                $userId, trim((string) ($_POST['notes'] ?? '')));
            $this->flash('success', 'The asset quantity was added to divisional inventory.');
        });
    }

    public function adjust(): void {
        $this->mutate(function (DivisionalAssetModel $model, int $divisionId, int $userId): void {
            $model->adjustStock($divisionId, (int) ($_POST['catalog_item_id'] ?? 0), (int) ($_POST['quantity'] ?? -1),
                $userId, trim((string) ($_POST['reason'] ?? '')));
            $this->flash('success', 'The inventory quantity was adjusted.');
        });
    }

    public function transfer(): void {
        $this->mutate(function (DivisionalAssetModel $model, int $divisionId, int $userId): void {
            $model->transferToClub($divisionId, (int) ($_POST['catalog_item_id'] ?? 0), (int) ($_POST['club_id'] ?? 0),
                (int) ($_POST['quantity'] ?? 0), $userId, trim((string) ($_POST['notes'] ?? '')));
            $this->flash('success', 'The assets were transferred and the club inventory was updated.');
        });
    }

    public function decide($requestId = null): void {
        $this->mutate(function (DivisionalAssetModel $model, int $divisionId, int $userId) use ($requestId): void {
            $decision = (string) ($_POST['decision'] ?? '');
            $remarks = trim((string) ($_POST['remarks'] ?? ''));
            if (!in_array($decision, ['approve', 'reject'], true) || (int) $requestId < 1
                || ($decision === 'reject' && strlen($remarks) < 5)) {
                throw new InvalidArgumentException('Select a decision and provide remarks when rejecting.');
            }
            $model->decideClubRequest($divisionId, (int) $requestId, $userId, $decision, $remarks);
            $this->flash('success', $decision === 'approve'
                ? 'The request was approved and stock was transferred to the club.'
                : 'The club asset request was rejected.');
        });
    }

    public function requestzonal(): void {
        $this->mutate(function (DivisionalAssetModel $model, int $divisionId, int $userId): void {
            $model->requestFromZonal($divisionId, (int) ($_POST['catalog_item_id'] ?? 0), (int) ($_POST['quantity'] ?? 0),
                $userId, trim((string) ($_POST['reason'] ?? '')));
            $this->flash('success', 'The asset request was sent to the zonal office.');
        });
    }

    private function mutate(callable $operation): void {
        $this->requireTreasurer();
        if (!$this->verifyPost()) {
            $this->flash('error', 'The request could not be verified. Please try again.');
            $this->redirect('divisionalassets');
        }
        try {
            $operation($this->model('DivisionalAssetModel'), (int) $_SESSION['division_id'], (int) $_SESSION['user_id']);
        } catch (Throwable $exception) {
            $allowed = [
                'Select a valid asset and enter a positive quantity.',
                'Enter a valid quantity and an adjustment reason.',
                'The selected inventory record was not found.',
                'Select an active club in your division.',
                'Transfer quantity must be positive.',
                'There is not enough divisional stock for this transfer.',
                'This asset request is no longer pending.',
                'Select a decision and provide remarks when rejecting.',
                'Select an asset, quantity, and reason.',
                'The selected catalog item was not found.',
            ];
            $this->flash('error', in_array($exception->getMessage(), $allowed, true)
                ? $exception->getMessage() : 'The asset update could not be saved.');
        }
        $this->redirect('divisionalassets');
    }
}
