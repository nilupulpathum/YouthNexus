<?php
/**
 * Club Members — C1 shell. Role assignment + registration flows land in C3.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$stats  = $stats ?? [];
$roster = $roster ?? [];
$can_manage = !empty($can_manage);
?>

<section class="club-page" aria-labelledby="club-members-heading">
    <h1 id="club-members-heading" class="sr-only">Club members</h1>

    <div class="club-stat-grid" aria-label="Membership summary">
        <article class="club-stat-card">
            <p class="club-stat-label">Total members</p>
            <p class="club-stat-value"><?= $escape($stats['total'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Executives</p>
            <p class="club-stat-value"><?= $escape($stats['executives'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">General members</p>
            <p class="club-stat-value"><?= $escape($stats['members'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Pending approvals</p>
            <p class="club-stat-value"><?= $escape($stats['pending'] ?? 0) ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="club-roster-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Youth Development Club</p>
                <h2 id="club-roster-heading">Member Roster</h2>
            </div>
            <label class="club-search" for="club-member-search">
                <span class="icon"><?= yn_icon('eye') ?></span>
                <span class="sr-only">Search roster</span>
                <input id="club-member-search" type="search" placeholder="Search name, role, email..." autocomplete="off">
            </label>
        </div>

        <div class="club-table-wrap">
            <table class="club-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="club-roster-body">
                    <?php foreach ($roster as $m): ?>
                        <tr data-search="<?= $escape(strtolower(($m['name'] ?? '') . ' ' . ($m['role'] ?? '') . ' ' . ($m['email'] ?? ''))) ?>">
                            <td><strong><?= $escape($m['name'] ?? '') ?></strong></td>
                            <td><?= $escape($m['role'] ?? '') ?></td>
                            <td><?= $escape($m['email'] ?? '') ?></td>
                            <td><?= $escape($m['phone'] ?? '') ?></td>
                            <td><?= $escape($m['joined'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($m['status_key'] ?? 'active') ?>"><?= $escape($m['status'] ?? '') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($can_manage): ?>
            <p class="club-note">Role assignment actions land in C3 — this shell is read-only.</p>
        <?php endif; ?>
    </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('club-member-search');
    const body = document.getElementById('club-roster-body');
    if (!input || !body) return;
    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        body.querySelectorAll('tr').forEach(row => {
            row.style.display = (!q || (row.getAttribute('data-search') || '').includes(q)) ? '' : 'none';
        });
    });
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
