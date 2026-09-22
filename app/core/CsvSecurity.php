<?php

final class CsvSecurity {
    public static function cell($value): string {
        $text = str_replace("\0", '', (string) $value);
        return preg_match('/^[\x00-\x20]*[=+\-@]/', $text) ? "'" . $text : $text;
    }
}
