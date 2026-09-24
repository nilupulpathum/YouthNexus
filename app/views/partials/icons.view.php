<?php
/**
 * Shared member-pack icons.
 *
 * Stroke-style SVGs matching the dashboard sidebar icon set.
 * Usage: <?= yn_icon('calendar') ?>
 */
if (!function_exists('yn_icon')) {
function yn_icon(string $name): string {
    $paths = [
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16" stroke-linecap="round"/>',
        'pin' => '<path d="M12 21s7-5.5 7-11a7 7 0 0 0-14 0c0 5.5 7 11 7 11Z" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.5"/>',
        'file' => '<path d="M6 3.5h9l3 3V20H6z" stroke-linejoin="round"/><path d="M15 3.5V7h3" stroke-linecap="round"/>',
        'download' => '<path d="M12 4v11m0 0 4-4m-4 4-4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke-linecap="round"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-3.4 3-5 7-5s6.3 1.6 7 5" stroke-linecap="round"/>',
        'lock' => '<rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke-linecap="round"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" stroke-linejoin="round"/><path d="M10 21h4" stroke-linecap="round"/>',
        'upload' => '<path d="M12 15V4m0 0 4 4m-4-4L8 8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke-linecap="round"/>',
        'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01" stroke-linecap="round"/>',
        'play' => '<circle cx="12" cy="12" r="8.5"/><path d="m10 8.5 5 3.5-5 3.5v-7Z" stroke-linejoin="round"/>',
        'check' => '<path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3.5 19c.5-3.2 2.2-5 5.5-5s5 1.8 5.5 5" stroke-linecap="round"/><path d="M16 5.5a3 3 0 0 1 0 5.8M17 14.4c2.1.8 3.2 2.3 3.5 4.6" stroke-linecap="round"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M8 10h8M8 14h6" stroke-linecap="round"/>',
        'reports' => '<path d="M5 19V9M12 19V5M19 19v-7M3 19h18" stroke-linecap="round"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/>',
        'eye' => '<path d="M3 12s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6Z" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.5"/>',
        'link' => '<path d="M10 14a4 4 0 0 0 6 0l3-3a4 4 0 0 0-6-6l-1 1" stroke-linecap="round"/><path d="M14 10a4 4 0 0 0-6 0l-3 3a4 4 0 0 0 6 6l1-1" stroke-linecap="round"/>',
        'pen' => '<path d="m14 5 5 5L8 21H3v-5L14 5Z" stroke-linejoin="round"/><path d="m12 7 5 5" stroke-linecap="round"/>',
        'heart' => '<path d="M12 20s7-4.5 7-10a4 4 0 0 0-7-2.5A4 4 0 0 0 5 10c0 5.5 7 10 7 10Z" stroke-linejoin="round"/>',
        'award' => '<circle cx="12" cy="9" r="5"/><path d="m8.5 13.5-2 7 5.5-3 5.5 3-2-7" stroke-linejoin="round"/>',
        'leaf' => '<path d="M5 19C5 9 13 5 20 5c0 8-4 14-13 14" stroke-linejoin="round"/><path d="M5 19c3-5 7-9 11-11" stroke-linecap="round"/>',
        'palette' => '<circle cx="12" cy="12" r="8.5"/><circle cx="9" cy="10" r="1" fill="currentColor"/><circle cx="14" cy="9" r="1" fill="currentColor"/><circle cx="15" cy="14" r="1" fill="currentColor"/>',
        'ball' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v10M7.5 9.5c2 1.5 7 1.5 9 0M7.5 14.5c2-1.5 7-1.5 9 0" stroke-linecap="round"/>',
    ];
    $inner = $paths[$name] ?? $paths['info'];
    return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">' . $inner . '</svg>';
}
}
