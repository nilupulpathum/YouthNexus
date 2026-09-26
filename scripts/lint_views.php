<?php
declare(strict_types=1);

// Divisional pages and the shared pages reachable from Divisional navigation.
$root = dirname(__DIR__);
$views = glob($root . '/app/views/divisional*/*.view.php') ?: [];
foreach ([
    'announcements/list.view.php',
    'announcements/detail.view.php',
    'attendance/session-list.view.php',
    'attendance/session-detail.view.php',
    'clubregistrationapproval/application-list.view.php',
    'eventapproval/event-list.view.php',
    'manageevents/list.view.php',
    'manageevents/status.view.php',
] as $shared) {
    $views[] = $root . '/app/views/' . $shared;
}

$errors = [];
foreach ($views as $view) {
    $source = file_get_contents($view);
    $name = substr($view, strlen($root) + 1);
    if ($source === false) {
        $errors[] = "$name: cannot read view";
        continue;
    }
    $layout = strpos($source, 'dashboard-start.view.php');
    if ($layout !== false) {
        $setup = substr($source, 0, $layout);
        foreach (['pageStyles', 'pageScripts'] as $variable) {
            if (!preg_match('/\$' . $variable . '\s*=\s*\[/', $setup)) {
                $errors[] = "$name: missing \$$variable array before dashboard layout";
            }
        }
    }
    if (preg_match('/<link\b[^>]*rel\s*=\s*["\x27]stylesheet|<script\b[^>]*\bsrc\s*=/i', $source)) {
        $errors[] = "$name: load assets through the dashboard layout";
    }
    if (preg_match('/<h1\b/i', $source)) {
        $errors[] = "$name: page heading belongs in the dashboard topbar";
    }
    if (str_contains($name, 'app/views/divisional')) {
        if (preg_match('/<div\b[^>]*class=["\x27][^"\x27]*yn-empty-state/i', $source)) {
            $errors[] = "$name: use the shared empty state partial";
        }
        if (preg_match_all('/<table\b[^>]*>/i', $source, $tables)) {
            foreach ($tables[0] as $table) {
                if (!str_contains($table, 'yn-table')) {
                    $errors[] = "$name: table missing yn-table";
                }
            }
        }
    }
    if (preg_match_all('/\bstyle\s*=\s*"([^"]*)"/i', $source, $styles)) {
        foreach ($styles[1] as $style) {
            $dynamicWidth = preg_match('/^\s*width\s*:\s*<\?=.+\?>\s*%\s*;?\s*$/s', $style);
            $dynamicBackground = preg_match('/^\s*background\s*:\s*conic-gradient\(/', $style);
            $customProperty = preg_match('/^\s*--[a-z0-9-]+\s*:/', $style);
            if (!$dynamicWidth && !$dynamicBackground && !$customProperty) {
                $errors[] = "$name: non-data-driven inline style: $style";
            }
        }
    }
}

foreach (glob($root . '/public/assets/css/divisional*.css') ?: [] as $css) {
    $source = file_get_contents($css);
    $name = substr($css, strlen($root) + 1);
    if ($source !== false && preg_match('/--(?:db|dw)-|#[a-f0-9]{3,8}\b|!important/i', $source)) {
        $errors[] = "$name: legacy token, literal color, or important override";
    }
}

foreach (['divisionalledger', 'divisionalallocations', 'divisionalvoidapproval', 'divisionalassets'] as $section) {
    $view = file_get_contents($root . '/app/views/' . $section . '/index.view.php');
    if ($view === false || !str_contains($view, 'data-yn-pagination')) {
        $errors[] = "$section: missing list pagination";
    }
}
if (!is_file($root . '/app/views/partials/empty-state.view.php')) {
    $errors[] = 'Missing shared empty state partial';
}
if (!str_contains((string) file_get_contents($root . '/app/core/config.php'), "define('ASSET_VERSION'")) {
    $errors[] = 'Missing ASSET_VERSION constant';
}

if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}
echo 'Divisional UI view contract passed (' . count($views) . ' views).' . PHP_EOL;
