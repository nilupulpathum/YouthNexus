<?php

class Financereceipt extends Controller {
    public function show($entryId = null): void {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $role = $_SESSION['user_role'] ?? '';
        $divisionId = (int) ($_SESSION['division_id'] ?? 0);
        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $entryId = (int) $entryId;
        if ($entryId < 1) {
            http_response_code(403);
            exit('You are not permitted to view this receipt.');
        }

        $pdo = Database::getInstance()->getConnection();
        if ($role === 'DivisionalTreasurer' && $divisionId > 0) {
            $statement = $pdo->prepare(
                "SELECT le.attachment_url
                 FROM LedgerEntry le
                 INNER JOIN Ledger l ON l.ledger_id = le.ledger_id
                 LEFT JOIN Club c ON l.owner_type = 'Club' AND c.club_id = l.owner_id
                 WHERE le.entry_id = ?
                   AND ((l.owner_type = 'Division' AND l.owner_id = ?)
                     OR (l.owner_type = 'Club' AND c.division_id = ?))
                 LIMIT 1"
            );
            $statement->execute([$entryId, $divisionId, $divisionId]);
            $url = $statement->fetchColumn();
        } elseif (in_array($role, ['ClubPresident', 'ClubTreasurer'], true) && $clubId > 0) {            $statement = $pdo->prepare(
                "SELECT le.attachment_url
                 FROM LedgerEntry le
                 INNER JOIN Ledger l ON l.ledger_id = le.ledger_id
                 WHERE le.entry_id = ?
                   AND l.owner_type = 'Club' AND l.owner_id = ?
                 LIMIT 1"
            );
            $statement->execute([$entryId, $clubId]);
            $url = $statement->fetchColumn();
        } elseif ($role === 'ZonalTreasurer' && $zonalId > 0) {
            $statement = $pdo->prepare(
                "SELECT le.attachment_url
                 FROM LedgerEntry le
                 INNER JOIN Ledger l ON l.ledger_id = le.ledger_id
                 WHERE le.entry_id = ?
                   AND l.owner_type = 'Zone' AND l.owner_id = ?
                 LIMIT 1"
            );
            $statement->execute([$entryId, $zonalId]);
            $url = $statement->fetchColumn();
        } else {
            http_response_code(403);
            exit('You are not permitted to view this receipt.');
        }
        $path = FinanceReceiptStorage::resolvePath($url !== false ? (string) $url : null);
        if (!$path) {
            http_response_code(404);
            exit('The receipt was not found.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($mime, $allowed, true)) {
            http_response_code(415);
            exit('The receipt file type is not supported.');
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $filename = 'receipt-' . (int) $entryId . '.' . $extension;
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
