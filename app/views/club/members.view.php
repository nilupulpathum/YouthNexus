<?php
/**
 * Club Members — roster + search/role/status filters; president assign-role
 * modal + member review modal; secretary register-member modal.
 * Presentation-only: no DB writes; backend contract lands in C13.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$stats  = $stats ?? [];
$roster = $roster ?? [];
$can_manage = !empty($can_manage);
$can_register = !empty($can_register);
$existing_nics = $existing_nics ?? [];

$title = 'Club Members - YouthNexus';
$pageTitle = 'Club Members';
$pageDescription = 'Roster, approvals and executive assignments';
$currentRoute = 'club/members';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/club.js',
];

$summaryCards = [
    ['value' => (string) ($stats['total'] ?? 0), 'label' => 'Total members', 'note' => 'Gampaha Youth Development Club', 'icon' => 'users', 'tone' => 'blue'],
    ['value' => (string) ($stats['executives'] ?? 0), 'label' => 'Executives', 'note' => 'President, secretary, treasurer', 'icon' => 'award', 'tone' => 'green'],
    ['value' => (string) ($stats['members'] ?? 0), 'label' => 'General members', 'note' => 'Active roster', 'icon' => 'user', 'tone' => 'blue'],
    ['value' => (string) ($stats['pending'] ?? 0), 'label' => 'Pending approvals', 'note' => 'Awaiting president decision', 'icon' => 'clock', 'tone' => 'amber'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-labelledby="club-members-heading">
    <h1 id="club-members-heading" class="visually-hidden">Club members</h1>

    <div class="dw-alert dw-alert--success" id="roster-toast" role="status" hidden></div>
    <?php if (!empty($flash)): ?>
        <div class="dw-alert dw-alert--<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status">
            <?= $e($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <div class="dw-summary-grid" aria-label="Membership summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="dw-toolbar" aria-label="Roster tools">
        <div class="dw-toolbar__search dw-search dw-search--plain">
            <label class="visually-hidden" for="club-member-search">Search roster</label>
            <input id="club-member-search" type="search" placeholder="Search name, role, email..." autocomplete="off">
        </div>
        <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="club-member-filters" aria-expanded="false">Filters</button>
        <?php if ($can_register): ?>
            <button type="button" class="dw-button dw-button--primary" data-modal-open="club-register-modal">Register Member</button>
        <?php endif; ?>
    </div>

    <section class="dw-filter-panel" id="club-member-filters" hidden>
        <h2 class="dw-filter-panel__heading">Filter Member Roster</h2>
        <div class="dw-filter-grid">
            <div class="dw-field">
                <label for="club-member-role">Filter by role</label>
                <select id="club-member-role">
                    <option value="">All roles</option>
                    <option value="president">President</option>
                    <option value="secretary">Secretary</option>
                    <option value="treasurer">Treasurer</option>
                    <option value="member">Member</option>
                </select>
            </div>
            <div class="dw-field">
                <label for="club-member-status">Filter by status</label>
                <select id="club-member-status">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
        </div>
        <div class="dw-filter-actions">
            <button class="dw-button dw-button--secondary" type="button" data-roster-reset>Reset all</button>
            <button class="dw-button dw-button--primary" type="button" data-roster-apply>Apply filters</button>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="club-roster-heading">
        <header class="dw-panel__header">
            <div>
                <h2 id="club-roster-heading">Member Roster</h2>
                <p>Gampaha Youth Development Club members</p>
            </div>
            <span class="dw-count"><?= count($roster) ?> <?= count($roster) === 1 ? 'member' : 'members' ?></span>
        </header>
        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <?php if ($can_manage): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="club-roster-body">
                    <?php foreach ($roster as $m): ?>
                        <tr data-search="<?= $e(strtolower(($m['name'] ?? '') . ' ' . ($m['role'] ?? '') . ' ' . ($m['email'] ?? ''))) ?>"
                            data-id="<?= (int) ($m['id'] ?? 0) ?>"
                            data-name="<?= $e($m['name'] ?? '') ?>"
                            data-role="<?= $e($m['role'] ?? '') ?>"
                            data-email="<?= $e($m['email'] ?? '') ?>"
                            data-phone="<?= $e($m['phone'] ?? '') ?>"
                            data-address="<?= $e($m['address'] ?? '') ?>"
                            data-nic="<?= $e($m['nic'] ?? '') ?>"
                            data-joined="<?= $e($m['joined'] ?? '') ?>"
                            data-status="<?= $e($m['status_key'] ?? 'active') ?>">
                            <td><strong><?= $e($m['name'] ?? '') ?></strong></td>
                            <td><?= $e($m['role'] ?? '') ?></td>
                            <td><?= $e($m['email'] ?? '') ?></td>
                            <td><?= $e($m['phone'] ?? '') ?></td>
                            <td><?= $e($m['joined'] ?? '') ?></td>
                            <td><?php $status = $m['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                            <?php if ($can_manage): ?>
                                <td>
                                    <div class="dw-row-actions">
                                        <?php if (($m['status_key'] ?? '') === 'pending'): ?>
                                            <button type="button" class="dw-button dw-button--ghost" data-action="member-review" data-modal-open="member-review-modal">Review</button>
                                        <?php else: ?>
                                            <button type="button" class="dw-button dw-button--ghost" data-action="assign" data-modal-open="assign-modal">Assign role</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $emptyTitle = 'No members match these filters';
        $emptyMessage = 'Try changing the current search or filters.';
        $emptyVisible = count($roster) === 0;
        require __DIR__ . '/../partials/divisional/empty-state.view.php';
        ?>
    </section>
</section>

<?php if ($can_register): ?>
    <div id="club-register-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="reg-modal-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Secretary action</p>
                    <h2 id="reg-modal-title">Register Member</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p>New members join as General Member once the president approves. NIC and email must be unique.</p>
                <form id="club-register-form" action="<?= ROOT ?>/club/register" method="post" data-existing-nics="<?= $e(json_encode(array_values($existing_nics))) ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                    <div class="dw-field">
                        <label for="reg-name">Full name</label>
                        <input id="reg-name" name="name" type="text" required autocomplete="off">
                    </div>
                    <div class="dw-field">
                        <label for="reg-nic">NIC</label>
                        <input id="reg-nic" name="nic" type="text" required autocomplete="off">
                    </div>
                    <div class="dw-field">
                        <label for="reg-email">Email</label>
                        <input id="reg-email" name="email" type="email" required autocomplete="off">
                    </div>
                    <div class="dw-field">
                        <label for="reg-phone">Phone</label>
                        <input id="reg-phone" name="phone" type="tel" required autocomplete="off">
                    </div>
                    <div class="dw-field dw-field--span-2">
                        <label for="reg-address">Address</label>
                        <input id="reg-address" name="address" type="text" required autocomplete="off">
                    </div>
                    <div class="dw-alert dw-alert--error" id="reg-error" role="alert" hidden></div>
                </form>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="submit" class="dw-button dw-button--primary" form="club-register-form">Register Member</button>
            </footer>
        </div>
    </div>
<?php endif; ?>

<?php if ($can_manage): ?>
    <div id="assign-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="assign-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>President action</p>
                    <h2 id="assign-title">Assign executive role</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p>Member: <strong id="assign-member"></strong> (<span id="assign-current"></span>)</p>
                <form id="assign-form" action="<?= ROOT ?>/president/assign" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                    <input type="hidden" id="assign-member-id" name="member_id" value="">
                    <div class="dw-field">
                        <label for="assign-role">Target role</label>
                        <select id="assign-role" name="role">
                            <option value="ClubSecretary">Secretary</option>
                            <option value="ClubTreasurer">Treasurer</option>
                            <option value="ClubMember">General Member</option>
                        </select>
                    </div>
                </form>
                <div class="dw-alert dw-alert--warning" id="assign-warning" hidden></div>
                <p>An audit record is logged on confirm.</p>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="submit" class="dw-button dw-button--primary" form="assign-form">Confirm assignment</button>
            </footer>
        </div>
    </div>
    <div id="member-review-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="mr-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>President decision</p>
                    <h2 id="mr-title">Review member</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <div id="mr-details" class="dw-metric-list"></div>
                <form id="member-review-form" action="<?= ROOT ?>/president/review" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                    <input type="hidden" id="mr-member-id" name="member_id" value="">
                    <div class="dw-field">
                        <label for="mr-result">Review result</label>
                        <select id="mr-result" name="result">
                            <option value="approve">Approve member</option>
                            <option value="reject">Reject application</option>
                        </select>
                    </div>
                    <div class="dw-field">
                        <label for="mr-remarks">Decision note (required if rejecting)</label>
                        <textarea id="mr-remarks" name="remarks" rows="3" placeholder="Reason for this decision..."></textarea>
                    </div>
                </form>
                <div class="dw-alert dw-alert--error" id="mr-error" role="alert" hidden></div>
                <div role="note">
                    <strong>What happens next</strong>
                    <p>Approving adds them as a General Member of the club. Rejecting discards the application with your note.</p>
                </div>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="submit" class="dw-button dw-button--primary" form="member-review-form">Confirm &amp; submit decision</button>
            </footer>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
