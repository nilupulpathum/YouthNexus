<?php
// Small native SVGs follow the same outline-icon style as Events and Attendance.
$annIcon = static function ($name) {
    $paths = [
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>',
        'filter' => '<path d="M4 7h16M7 12h10M10 17h4"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'edit' => '<path d="m15 5 4 4M4 20l5-1L20 8a2.8 2.8 0 0 0-4-4L5 15l-1 5Z"/>',
        'file' => '<path d="M14 3H5v18h14V8l-5-5Zm0 0v5h5M8 13h8M8 17h6"/>',
        'download' => '<path d="M12 3v12m-5-5 5 5 5-5M5 16v5h14v-5"/>',
        'broadcast' => '<path d="m4 11 14-5v12L4 13v-2Zm3 3 2 6h3l-2-5M21 9v6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
    ];
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
};

$annDate = static function ($value) {
    if (empty($value)) return '';
    return '<time datetime="' . htmlspecialchars(date('Y-m-d\TH:i:s', strtotime($value)), ENT_QUOTES) . '">' . date('M j, Y · g:i:s A', strtotime($value)) . '</time>';
};
