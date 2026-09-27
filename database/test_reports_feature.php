<?php
require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "--- Catalog-linked Reports (status='Active', report_type_id IS NOT NULL) ---\n";
    $rows = $pdo->query("
        SELECT r.report_id, c.category, c.type_name, r.scope_level, r.format, r.status, r.generated_at
        FROM Report r
        JOIN ReportTypeCatalog c ON r.report_type_id = c.report_type_id
        WHERE r.report_type_id IS NOT NULL
        ORDER BY r.report_id
    ")->fetchAll();

    echo "Found " . count($rows) . " catalog-linked reports:\n";
    foreach ($rows as $r) {
        echo " - [#{$r['report_id']}] {$r['category']} | {$r['type_name']} | Scope: {$r['scope_level']} | Format: {$r['format']} | Status: {$r['status']}\n";
    }

    echo "\n--- ReportTypeCatalog count ---\n";
    $catCount = $pdo->query("SELECT COUNT(*) FROM ReportTypeCatalog")->fetchColumn();
    echo "Types: $catCount\n";

    $aggregateType = $pdo->query(
        "SELECT report_type_id FROM ReportTypeCatalog WHERE type_name = 'Club Activity Aggregate' LIMIT 1"
    )->fetchColumn();
    if (!$aggregateType) {
        throw new RuntimeException('Club Activity Aggregate report type is missing. Run migrate_divisional_reports.php.');
    }
    echo "Secretary aggregate report type: ready\n";

    echo "\nAll OK - Manage Reports module DB is ready.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
