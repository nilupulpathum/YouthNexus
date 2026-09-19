<?php
/**
 * Manage Assets View — NYSC Administration
 * National Asset Management & Logistics Module
 */
$title                   = $title ?? 'Manage Assets — YouthNexus';
$pageTitle               = $pageTitle ?? 'National Asset Management & Warehouse Logistics';
$pageDescription         = $pageDescription ?? 'National Youth Services Council — Central Logistics, Zonal Stock Allocation & Deficit Surveillance';
$currentRoute            = 'manageassets';
$unreadNotificationCount = (int)($stats['low_stock_count'] ?? 0);

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/manageassets.css">

<!-- Flash Message Holders for JS Toast -->
<?php if (!empty($flashSuccess)): ?>
    <input type="hidden" id="initialFlashSuccess" value="<?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <input type="hidden" id="initialFlashError" value="<?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>

<div class="asset-module">

    <!-- Top Action Bar -->
    <div class="am-header-bar">
        <div class="am-header-titles">
            <h2 class="am-section-title">National Asset Management &amp; Warehouse Logistics</h2>
            <p class="am-section-desc">National Youth Services Council — Central Logistics, Zonal Stock Allocation &amp; Deficit Surveillance</p>
        </div>
        <div class="am-header-actions">
            <a href="<?= ROOT ?>/manageassets/export?<?= http_build_query(['category' => $selCategory, 'zone' => $selZoneVal, 'search' => $searchQuery]) ?>" class="am-btn am-btn-outline" id="btnExportReport" title="Export Inventory to CSV">
                <span>&#8681;</span> Export Inventory Report
            </a>
            <button type="button" class="am-btn am-btn-sky" id="btnOpenDistribute">
                <span>🎯</span> Distribute to Zone
            </button>
            <button type="button" class="am-btn am-btn-primary" id="btnOpenAddStock">
                <span>+</span> Add National Stock
            </button>
        </div>
    </div>

    <!-- Stat Cards (Shown in National View) -->
    <?php if (!$isZoneView): ?>
    <div class="am-stats-grid">
        <!-- 1. Total Stock -->
        <div class="am-stat-card">
            <div class="am-stat-head">
                <span class="am-stat-title">TOTAL ITEMS IN STOCK</span>
            </div>
            <div class="am-stat-big">
                <?= number_format($stats['total_stock'] ?? 0) ?>
                <span class="am-stat-unit">Units</span>
            </div>
            <div class="am-stat-sub">
                <span class="green">+320 this month</span>
                <span class="gray"><?= (int)($stats['catalog_count'] ?? 20) ?> catalog lines</span>
            </div>
        </div>

        <!-- 2. Total Distributed -->
        <div class="am-stat-card">
            <div class="am-stat-head">
                <span class="am-stat-title">TOTAL DISTRIBUTED ASSETS</span>
            </div>
            <div class="am-stat-big">
                <span class="num-blue"><?= number_format($stats['total_distributed'] ?? 0) ?></span>
                <span class="am-stat-unit">Units</span>
            </div>
            <div class="am-stat-sub">
                <span class="gray">Active Distribution</span>
                <span class="gray"><?= count($zones) ?> Zones &bull; 332 Divs</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter & Search Toolbar -->
    <form method="GET" action="<?= ROOT ?>/manageassets" id="filterForm" style="margin: 0;">
        <div class="am-filter-bar">
            <div class="am-search-wrap">
                <span class="am-search-icon">&#128269;</span>
                <input type="text" name="search" id="assetSearchInput" class="am-search-input"
                       placeholder="Search catalog items, SKUs, or specifications..."
                       value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
            </div>

            <select name="category" id="filterCategory" class="am-select" onchange="document.getElementById('filterForm').submit()">
                <option value="All Categories" <?= ($selCategory === 'All Categories') ? 'selected' : '' ?>>All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= ($selCategory === $cat) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="zone" id="filterZone" class="am-select" onchange="document.getElementById('filterForm').submit()">
                <option value="All Zones" <?= ($selZoneVal === 'All Zones') ? 'selected' : '' ?>>All Zones (National)</option>
                <?php foreach ($zones as $z): ?>
                    <option value="<?= htmlspecialchars($z->zonal_name, ENT_QUOTES, 'UTF-8') ?>" <?= ($selZoneVal === $z->zonal_name || (int)$selectedZoneId === (int)$z->zonal_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($z->zonal_name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <!-- ================= NATIONAL WAREHOUSE VIEW TABLE ================= -->
    <?php if (!$isZoneView): ?>
    <div class="am-panel">
        <div class="am-panel-head">
            <div>
                <h3 class="am-panel-title">
                    National Asset Catalog &amp; Zonal Allocation
                </h3>
                <span class="am-badge-count">Showing <?= count($inventory) ?> of <?= count($allCatalogItems) ?> Items</span>
            </div>
            <a href="#" class="am-link" id="btnRunReconcile">
                <span>⇄</span> Run Zonal Reconciliation
            </a>
        </div>

        <div class="am-table-responsive">
            <table class="am-table">
                <thead>
                    <tr>
                        <th style="width: 32px;"><input type="checkbox" aria-label="Select all items"></th>
                        <th>Item Identifier &amp; Specification</th>
                        <th>Category</th>
                        <th>Warehouse Qty</th>
                        <th>Threshold</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 36px; color: #9ca3af;">
                                No catalog assets matched your search filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><input type="checkbox" aria-label="Select item <?= htmlspecialchars($item->item_name) ?>"></td>
                                <td>
                                    <div class="am-item-name"><?= htmlspecialchars($item->item_name, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="am-item-meta">
                                        <?= htmlspecialchars($item->sku, ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($item->specifications ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($item->category, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="<?= ($item->status === 'Deficit' || $item->status === 'Low Stock') ? 'qty-red' : 'qty-green' ?>">
                                    <?= number_format($item->quantity) ?> <?= htmlspecialchars($item->unit, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>Min: <?= (int)$item->national_low_stock_threshold ?> <?= htmlspecialchars($item->unit, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ($item->status === 'Deficit'): ?>
                                        <span class="am-status-badge deficit">Deficit</span>
                                    <?php elseif ($item->status === 'Low Stock'): ?>
                                        <span class="am-status-badge lowstock">Low Stock</span>
                                    <?php else: ?>
                                        <span class="am-status-badge optimal">Optimal</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="am-actions" style="justify-content: flex-end;">
                                        <button type="button" class="am-btn-sm am-btn-distribute <?= ($item->quantity <= 0) ? 'disabled' : '' ?>"
                                                data-action="distribute-row"
                                                data-item-id="<?= (int)$item->catalog_item_id ?>"
                                                data-category="<?= htmlspecialchars($item->category, ENT_QUOTES, 'UTF-8') ?>"
                                                <?= ($item->quantity <= 0) ? 'disabled' : '' ?>>
                                            Distribute
                                        </button>
                                        <button type="button" class="am-btn-sm am-btn-addstock"
                                                data-action="add-stock-row"
                                                data-item-id="<?= (int)$item->catalog_item_id ?>"
                                                data-category="<?= htmlspecialchars($item->category, ENT_QUOTES, 'UTF-8') ?>">
                                            + Add Stock
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="am-panel-foot">
            <div>National Warehouse: Central Depot Maharagama &bull; Real-time synchronization active</div>
            <div class="am-pagination">
                <span>Showing <?= count($inventory) ?> entries</span>
            </div>
        </div>
    </div>

    <!-- ================= REGIONAL ZONE VIEW TABLE ================= -->
    <?php else: ?>

    <div class="am-zone-header">
        <div>
            <h2>
                Zone: <?= htmlspecialchars($selectedZoneName, ENT_QUOTES, 'UTF-8') ?> — Current Asset Inventory
            </h2>
            <span class="am-filter-tag">Active Filter</span>
        </div>
        <a href="<?= ROOT ?>/manageassets?category=<?= urlencode($selCategory) ?>&zone=All+Zones" class="am-clear-link">Clear Filter</a>
    </div>

    <div class="am-panel">
        <div class="am-table-responsive">
            <table class="am-table">
                <thead>
                    <tr>
                        <th>Asset Name</th>
                        <th>Category</th>
                        <th>Allocated Qty</th>
                        <th>Available Qty</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $zoneTotalAlloc = 0;
                    $zoneTotalAvail = 0;
                    ?>
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 36px; color: #9ca3af;">
                                No asset allocations found for this zone.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                            <?php
                            $zoneTotalAlloc += (int)$item->allocated_quantity;
                            $zoneTotalAvail += (int)$item->available_quantity;
                            ?>
                            <tr>
                                <td>
                                    <div class="am-item-name"><?= htmlspecialchars($item->item_name, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="am-item-meta"><?= htmlspecialchars($item->sku, ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td><?= htmlspecialchars($item->category, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="<?= ($item->status === 'Deficit') ? 'qty-red' : '' ?>">
                                    <?= (int)$item->allocated_quantity ?> <?= htmlspecialchars($item->unit, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="<?= ($item->status === 'Deficit') ? 'qty-red' : (($item->status === 'Low Stock') ? 'qty-orange' : 'qty-green') ?>">
                                    <?= (int)$item->available_quantity ?> <?= htmlspecialchars($item->unit, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <?php if ($item->status === 'Deficit'): ?>
                                        <span class="am-status-badge deficit">Deficit</span>
                                    <?php elseif ($item->status === 'Low Stock'): ?>
                                        <span class="am-status-badge lowstock">Low Stock</span>
                                    <?php else: ?>
                                        <span class="am-status-badge optimal">Optimal</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" class="am-btn-sm am-btn-distribute"
                                            data-action="distribute-row"
                                            data-item-id="<?= (int)$item->catalog_item_id ?>"
                                            data-category="<?= htmlspecialchars($item->category, ENT_QUOTES, 'UTF-8') ?>">
                                        Replenish
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="am-zone-summary">
            <div>Total Assets: <?= count($inventory) ?> items &bull; <?= $zoneTotalAlloc ?> allocated &bull; <?= $zoneTotalAvail ?> available</div>
            <a href="<?= ROOT ?>/manageassets" class="am-link">View All National Stock &rarr;</a>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /asset-module -->

<!-- ============================================================
     POPUP MODAL 1: ADD NATIONAL STOCK
     ============================================================ -->
<div class="am-overlay" id="addStockModal" role="dialog" aria-modal="true" aria-labelledby="addStockTitle">
    <div class="am-popup">
        <div class="am-popup-header">
            <div>
                <h2 class="am-popup-title" id="addStockTitle">Add National Stock</h2>
                <p class="am-popup-subtitle">Record verified warehouse receipts into NYSC national inventory.</p>
            </div>
            <button type="button" class="am-close-btn" data-close-modal="addStockModal" aria-label="Close modal">&times;</button>
        </div>

        <form method="POST" action="<?= ROOT ?>/manageassets/addstock" id="addStockForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

            <div class="am-popup-body">
                <!-- Category Select -->
                <label class="am-field-label" for="addStockCategory">
                    Asset Category <span class="am-required">*</span>
                </label>
                <select id="addStockCategory" class="am-field-input">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Catalog Item -->
                <div class="am-label-row">
                    <label class="am-field-label" for="addStockItemSelect">
                        Catalog Item <span class="am-required">*</span>
                    </label>
                    <span class="am-stock-info">
                        Current stock: <span class="am-stock-badge" id="addStockCurrentBadge">0 units</span>
                    </span>
                </div>
                <select id="addStockItemSelect" name="catalog_item_id" class="am-field-input" required>
                    <!-- Populated dynamically via JS -->
                </select>

                <!-- Row: Quantity & Invoice Ref -->
                <div class="am-field-row">
                    <div class="am-field-col">
                        <label class="am-field-label" for="addStockQty">
                            Quantity Added <span class="am-required">*</span>
                        </label>
                        <input type="number" id="addStockQty" name="quantityAdded" class="am-field-input" min="1" value="10" required>
                    </div>

                    <div class="am-field-col">
                        <label class="am-field-label" for="addStockInvoice">
                            Procurement / Invoice Reference <span class="am-required">*</span>
                        </label>
                        <input type="text" id="addStockInvoice" name="invoiceRef" class="am-field-input" placeholder="e.g. INV-2026-0099" value="INV-2026-00<?= rand(10, 99) ?>" required>
                    </div>
                </div>
            </div>

            <div class="am-popup-footer">
                <button type="button" class="am-btn-cancel" data-close-modal="addStockModal">Cancel</button>
                <button type="submit" class="am-btn-confirm">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="10.5" cy="13.5" r="8.5"></circle>
                        <path d="M7 13.5l2.5 2.5 5-5"></path>
                        <path d="M19 1v6"></path>
                        <path d="M16 4h6"></path>
                    </svg>
                    Add to Inventory
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     POPUP MODAL 2: DISTRIBUTE ASSETS TO ZONE
     ============================================================ -->
<div class="am-overlay" id="distributeModal" role="dialog" aria-modal="true" aria-labelledby="distributeTitle">
    <div class="am-popup">
        <div class="am-popup-header">
            <div>
                <h2 class="am-popup-title" id="distributeTitle">Distribute Assets to Zone</h2>
                <p class="am-popup-subtitle">Allocate national warehouse stock to regional zonal offices.</p>
            </div>
            <button type="button" class="am-close-btn" data-close-modal="distributeModal" aria-label="Close modal">&times;</button>
        </div>

        <form method="POST" action="<?= ROOT ?>/manageassets/distribute" id="distributeForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

            <div class="am-popup-body">
                <!-- Intelligent Deficit Note Box -->
                <div class="am-note-box" id="distNoteBox">
                    <span class="am-note-icon">i</span>
                    <p class="am-note-text" id="distNoteText">
                        Note: Select an item and zone to analyze regional deficit requirements and recommended allocations.
                    </p>
                </div>

                <!-- Category -->
                <label class="am-field-label" for="distCategory">Asset Category</label>
                <select id="distCategory" class="am-field-input">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Catalog Item -->
                <div class="am-label-row">
                    <label class="am-field-label" for="distItemSelect">Catalog Item</label>
                    <span class="am-stock-badge" id="distAvailableBadge">Available stock: 0 units</span>
                </div>
                <select id="distItemSelect" name="catalog_item_id" class="am-field-input" required>
                    <!-- Populated dynamically via JS -->
                </select>

                <!-- Target Zonal Office -->
                <label class="am-field-label" for="distZoneSelect">Target Zonal Office</label>
                <select id="distZoneSelect" name="target_zone_id" class="am-field-input" required>
                    <?php foreach ($zones as $z): ?>
                        <option value="<?= (int)$z->zonal_id ?>" <?= ($selectedZoneId && $selectedZoneId == $z->zonal_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($z->zonal_name, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Quantity to Distribute -->
                <div class="am-label-row">
                    <label class="am-field-label" for="distQuantityInput">Quantity to Distribute</label>
                    <span class="am-max-text" id="distMaxText">Maximum: 0</span>
                </div>
                <input type="number" id="distQuantityInput" name="quantity" class="am-field-input" min="1" value="2" required>

                <!-- Hint Text -->
                <p class="am-hint-text">
                    Remaining national stock after transfer:
                    <span class="am-hint-blue" id="distRemainingText">0 units</span>.
                </p>
            </div>

            <div class="am-popup-footer">
                <button type="button" class="am-btn-cancel" data-close-modal="distributeModal">Cancel</button>
                <button type="submit" class="am-btn-confirm">Confirm Distribution</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div class="am-toast" id="assetToast"></div>

<!-- Embedded Data Context for Client-Side JS -->
<script>
    window.YouthNexusAssets = {
        rootUrl: '<?= ROOT ?>',
        csrfToken: '<?= $csrf_token ?>'
    };
    window.YouthNexusAssetsCatalog = <?= json_encode(array_map(function($it) {
        return [
            'catalog_item_id' => (int)$it->catalog_item_id,
            'category'        => $it->category,
            'item_name'       => $it->item_name,
            'sku'             => $it->sku,
            'unit'            => $it->unit,
            'specifications'  => $it->specifications
        ];
    }, $allCatalogItems)) ?>;
</script>
<script src="<?= ROOT ?>/assets/js/manageassets.js" defer></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
