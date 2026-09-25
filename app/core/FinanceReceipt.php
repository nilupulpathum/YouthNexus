<?php

// Keep this helper name distinct from the Financereceipt controller. PHP class
// names are case-insensitive, so FinanceReceipt would collide with it.
final class FinanceReceiptStorage {
    private const MAX_SIZE = 5242880;
    private const DIRECTORY = '/public/uploads/ledger-receipts';

    public static function store($file): ?string {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || (int) ($file['size'] ?? 0) > self::MAX_SIZE) {
            throw new InvalidArgumentException('The receipt must be a PDF, JPG, or PNG file no larger than 5 MB.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];
        if (!isset($extensions[$mime])) {
            throw new InvalidArgumentException('Only PDF, JPG, and PNG receipts are accepted.');
        }

        $directory = dirname(__DIR__, 2) . self::DIRECTORY;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('The receipt storage directory is unavailable.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            throw new RuntimeException('The receipt could not be stored.');
        }

        return '/uploads/ledger-receipts/' . $name;
    }

    public static function remove(?string $url): void {
        $path = self::resolvePath($url);
        if ($path && is_file($path)) {
            unlink($path);
        }
    }

    public static function resolvePath(?string $url): ?string {
        if (!$url || !preg_match('#^/uploads/ledger-receipts/[a-f0-9]{32}\.(pdf|jpg|png)$#', $url)) {
            return null;
        }
        $path = dirname(__DIR__, 2) . '/public' . $url;
        return is_file($path) ? $path : null;
    }
}
