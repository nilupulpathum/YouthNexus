<?php

// Completion-evidence uploads for club events (D2). Mirrors
// FinanceReceiptStorage: validated, randomised names, no execution.
final class EventEvidenceStorage {
    private const MAX_SIZE = 5242880;
    private const DIRECTORY = '/public/uploads/event-evidence';

    public static function store($file): string {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Attach the attendance sheet - evidence is required to complete an event.');
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_SIZE) {
            throw new InvalidArgumentException('Evidence files must be no larger than 5 MB.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'text/csv' => 'csv',
            'text/plain' => 'csv',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        ];
        if (!isset($extensions[$mime])) {
            throw new InvalidArgumentException('Evidence must be a sheet, PDF, or image file.');
        }

        $directory = dirname(__DIR__, 2) . self::DIRECTORY;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('The evidence storage directory is unavailable.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            throw new RuntimeException('The evidence file could not be stored.');
        }

        return '/uploads/event-evidence/' . $name;
    }
}
