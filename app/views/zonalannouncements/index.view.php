<?php
$escape = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$can_publish = !empty($can_publish);
$announcements = $announcements ?? [];

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="announcements-container">
    <aside class="announcements-filters">
        <div class="filter-group">
            <h3>LEVEL</h3>
            <label class="filter-option"><input type="radio" name="level" value="all" checked><span>All levels</span></label>
            <label class="filter-option"><input type="radio" name="level" value="zonal"><span>Gampaha Zone</span></label>
            <label class="filter-option"><input type="radio" name="level" value="national"><span>National</span></label>
        </div>
        <div class="filter-group">
            <h3>DATE POSTED</h3>
            <label class="filter-option"><input type="radio" name="date" value="all" checked><span>All time</span></label>
            <label class="filter-option"><input type="radio" name="date" value="7days"><span>Last 7 days</span></label>
            <label class="filter-option"><input type="radio" name="date" value="30days"><span>Last 30 days</span></label>
        </div>
        <div class="filter-group">
            <h3>READ STATUS</h3>
            <label class="filter-option"><input type="radio" name="status" value="all" checked><span>All status</span></label>
            <label class="filter-option"><input type="radio" name="status" value="unread"><span>Unread</span></label>
            <label class="filter-option"><input type="radio" name="status" value="read"><span>Read</span></label>
        </div>
        <button type="button" class="clear-filters-btn" id="zonal-clear-filters"><span class="icon"><?= yn_icon('close') ?></span> Clear all filters</button>
        <div class="sidebar-bottom-action"><button type="button" class="mark-all-read-btn" id="zonal-mark-all-read"><span class="icon"><?= yn_icon('check') ?></span> Mark all as read</button></div>
    </aside>

    <main class="announcements-main">
        <header class="announcements-header">
            <div class="results-info">Showing <strong id="zonal-announcement-total"><?= count($announcements) ?></strong> announcements · <span class="unread-highlight" id="zonal-unread-count"><?= $escape($unreadCount) ?> unread</span></div>
            <div class="sort-control">
                <span>Sort by:</span>
                <select class="sort-select" id="zonal-announcement-sort"><option value="newest">Newest first</option><option value="oldest">Oldest first</option></select>
                <?php if ($can_publish): ?><button type="button" class="publish-open-btn" id="zonal-announce-open">Publish Announcement</button><?php endif; ?>
            </div>
        </header>

        <div class="announcements-list" id="zonal-announcement-list">
            <section class="announcement-section" id="zonal-unread-section">
                <h2 class="section-title">Unread <span class="badge" id="zonal-unread-badge">2</span></h2>
                <?php foreach ($announcements as $item): ?>
                    <?php if (!empty($item['is_unread'])): ?>
                        <article class="announcement-card is-unread" data-level="<?= $escape(strtolower($item['scope'])) ?>" data-unread="true" data-announcement="<?= $escape(json_encode($item)) ?>">
                            <div class="card-header"><div class="header-left"><span class="scope-badge national"><?= $escape($item['scope']) ?></span><?php if (!empty($item['is_new'])): ?><span class="new-badge">New</span><?php endif; ?></div><span class="age-text"><?= $escape($item['age']) ?></span></div>
                            <h3 class="card-title"><?= $escape($item['title']) ?></h3><p class="card-summary"><?= $escape($item['summary']) ?></p>
                            <?php if (!empty($item['attachment'])): ?><div class="card-attachment"><span class="icon"><?= yn_icon('file') ?></span><span class="filename"><?= $escape($item['attachment']) ?></span><span class="icon download-icon"><?= yn_icon('download') ?></span></div><?php endif; ?>
                            <div class="card-footer"><button type="button" class="btn-read-more" data-action="view"><span class="icon"><?= yn_icon('eye') ?></span> Read More</button><?php if (!empty($item['attachment'])): ?><button type="button" class="btn-download-attachment"><span class="icon"><?= yn_icon('download') ?></span> Download Attachment</button><?php endif; ?><button type="button" class="btn-mark-as-read" data-action="mark-read"><span class="icon"><?= yn_icon('check') ?></span> Mark as Read</button></div>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
            <section class="announcement-section" id="zonal-earlier-section">
                <h2 class="section-title">Earlier</h2>
                <?php foreach ($announcements as $item): ?>
                    <?php if (empty($item['is_unread'])): ?>
                        <article class="announcement-card" data-level="<?= $escape(strtolower($item['scope'])) ?>" data-unread="false" data-announcement="<?= $escape(json_encode($item)) ?>">
                            <div class="card-header"><div class="header-left"><span class="scope-badge national"><?= $escape($item['scope']) ?></span></div><span class="age-text"><?= $escape($item['age']) ?></span></div>
                            <h3 class="card-title"><?= $escape($item['title']) ?></h3><p class="card-summary"><?= $escape($item['summary']) ?></p>
                            <?php if (!empty($item['attachment'])): ?><div class="card-attachment"><span class="icon"><?= yn_icon('file') ?></span><span class="filename"><?= $escape($item['attachment']) ?></span><span class="icon download-icon"><?= yn_icon('download') ?></span></div><?php endif; ?>
                            <div class="card-footer"><button type="button" class="btn-read-more" data-action="view"><span class="icon"><?= yn_icon('eye') ?></span> Read More</button><?php if (!empty($item['attachment'])): ?><button type="button" class="btn-download-attachment"><span class="icon"><?= yn_icon('download') ?></span> Download Attachment</button><?php endif; ?></div>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
        </div>
    </main>
</div>

<div id="announcement-popup" class="popup-overlay" hidden><div class="popup-content"><button type="button" class="popup-close" aria-label="Close"><?= yn_icon('close') ?></button><div class="popup-header"><p class="popup-meta">Preview</p><h2 id="popup-title"></h2><div class="popup-tags"><span id="popup-scope" class="scope-badge national"></span><span id="popup-new" class="new-badge" hidden>New</span></div><p class="popup-date-info"><span class="icon"><?= yn_icon('calendar') ?></span> <span id="popup-date"></span><span class="icon"><?= yn_icon('clock') ?></span> <span id="popup-age"></span></p></div><div class="popup-body"><p id="popup-summary"></p><div id="popup-attachment-container" class="attachment-box" hidden><div class="attachment-info"><span class="icon"><?= yn_icon('file') ?></span><div><p id="popup-attachment-name"></p><span id="popup-attachment-size"></span></div></div></div></div><div class="popup-footer"><button type="button" class="btn-download"><span class="icon"><?= yn_icon('download') ?></span> Download Attachment</button><button type="button" class="btn-mark-read"><span class="icon"><?= yn_icon('check') ?></span> Mark as Read</button></div></div></div>

<?php if ($can_publish): ?>
<div id="zonal-publish-modal" class="popup-overlay" hidden><div class="popup-content" role="dialog" aria-modal="true" aria-labelledby="zonal-publish-title"><button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button><p class="popup-meta">New zone announcement</p><h2 id="zonal-publish-title">Publish Announcement</h2><form id="zonal-publish-form" novalidate><div class="publish-field"><label for="zonal-publish-heading">Title</label><input id="zonal-publish-heading" type="text" required maxlength="150" autocomplete="off" placeholder="e.g., Zone training date changed"></div><div class="publish-field"><label for="zonal-publish-body">Body</label><textarea id="zonal-publish-body" rows="4" required maxlength="1000" placeholder="What should divisions and clubs know?"></textarea></div><div class="publish-field"><label for="zonal-publish-attachment">Attachment (optional)</label><input id="zonal-publish-attachment" type="file" accept="image/*,.pdf,.doc,.docx"><p id="zonal-publish-file-name" class="popup-text" hidden></p></div><div class="publish-grid"><div class="publish-field"><label for="zonal-publish-scope">Scope</label><input id="zonal-publish-scope" type="text" readonly value="Gampaha Zone — divisions and clubs"></div><div class="publish-field"><label for="zonal-publish-priority">Priority</label><select id="zonal-publish-priority"><option value="Normal">Normal</option><option value="Urgent">Urgent</option></select></div></div><p id="zonal-publish-error" class="publish-error" hidden></p><div class="publish-footer"><button type="button" class="btn-cancel" data-close>Cancel</button><button type="submit" class="btn-publish">Publish</button></div></form></div></div>
<div id="zonal-publish-email-modal" class="popup-overlay" hidden><div class="popup-content popup-content--narrow" role="dialog" aria-modal="true" aria-labelledby="zonal-email-title"><p class="popup-meta">Urgent announcement</p><h2 id="zonal-email-title">Send email as well?</h2><p class="popup-text">Urgent announcements notify divisions and their clubs by email. Send the email together with this post?</p><div class="publish-footer"><button type="button" class="btn-cancel" data-close-email>Post without email</button><button type="button" class="btn-publish" id="zonal-email-confirm">Send email and post</button></div></div></div>
<?php endif; ?>

<div id="zonal-announce-toast" class="announce-toast" role="status" hidden></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('zonal-announcement-list');
    const toast = document.getElementById('zonal-announce-toast');
    const showToast = (message) => { toast.textContent = message; toast.hidden = false; window.setTimeout(() => { toast.hidden = true; }, 3500); };
    const readCard = (card) => { card.classList.remove('is-unread'); card.dataset.unread = 'false'; card.querySelector('[data-action="mark-read"]')?.remove(); updateUnread(); };
    const updateUnread = () => { const count = list.querySelectorAll('[data-unread="true"]').length; document.getElementById('zonal-unread-count').textContent = count + ' unread'; document.getElementById('zonal-unread-badge').textContent = count; };
    document.querySelectorAll('[data-action="mark-read"]').forEach((button) => button.addEventListener('click', () => readCard(button.closest('.announcement-card'))));
    document.getElementById('zonal-mark-all-read').addEventListener('click', () => { list.querySelectorAll('[data-unread="true"]').forEach(readCard); showToast('All zone announcements marked as read.'); });
    document.getElementById('zonal-clear-filters').addEventListener('click', () => document.querySelectorAll('.announcements-filters input[value="all"]').forEach((input) => { input.checked = true; }));
    document.querySelectorAll('.announcements-filters input').forEach((input) => input.addEventListener('change', () => { const level = document.querySelector('input[name="level"]:checked').value; const state = document.querySelector('input[name="status"]:checked').value; list.querySelectorAll('.announcement-card').forEach((card) => { card.hidden = !((level === 'all' || card.dataset.level === level) && (state === 'all' || (state === 'unread') === (card.dataset.unread === 'true'))); }); }));
    <?php if ($can_publish): ?>
    const modal = document.getElementById('zonal-publish-modal'); const emailModal = document.getElementById('zonal-publish-email-modal'); const form = document.getElementById('zonal-publish-form'); const error = document.getElementById('zonal-publish-error'); const file = document.getElementById('zonal-publish-attachment'); const fileName = document.getElementById('zonal-publish-file-name');
    const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
    document.getElementById('zonal-announce-open').addEventListener('click', () => { form.reset(); error.hidden = true; fileName.hidden = true; modal.hidden = false; document.body.style.overflow = 'hidden'; }); modal.querySelectorAll('[data-close]').forEach((button) => button.addEventListener('click', close)); modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    file.addEventListener('change', () => { fileName.hidden = !file.files.length; if (file.files.length) fileName.textContent = 'Attached: ' + file.files[0].name; });
    const publish = (emailed) => { const title = document.getElementById('zonal-publish-heading').value.trim(); const body = document.getElementById('zonal-publish-body').value.trim(); const priority = document.getElementById('zonal-publish-priority').value; const card = document.createElement('article'); card.className = 'announcement-card is-unread'; card.dataset.level = 'zonal'; card.dataset.unread = 'true'; card.dataset.announcement = JSON.stringify({ title, summary: body, scope: 'Zonal', date: 'Just now', age: 'Just now', is_new: true, is_unread: true }); card.innerHTML = '<div class="card-header"><div class="header-left"><span class="scope-badge national">Zonal</span><span class="new-badge">New</span></div><span class="age-text">Just now</span></div><h3 class="card-title"></h3><p class="card-summary"></p><div class="card-footer"><button type="button" class="btn-mark-as-read" data-action="mark-read">Mark as Read</button></div>'; card.querySelector('.card-title').textContent = title; card.querySelector('.card-summary').textContent = body; card.querySelector('[data-action="mark-read"]').addEventListener('click', () => readCard(card)); document.getElementById('zonal-unread-section').appendChild(card); document.getElementById('zonal-announcement-total').textContent = String(Number(document.getElementById('zonal-announcement-total').textContent) + 1); updateUnread(); close(); showToast(priority === 'Urgent' && emailed ? 'Urgent zone announcement published and emailed.' : 'Zone announcement published.'); };
    form.addEventListener('submit', (event) => { event.preventDefault(); const title = document.getElementById('zonal-publish-heading').value.trim(); const body = document.getElementById('zonal-publish-body').value.trim(); if (!title || !body) { error.textContent = 'Title and body are required.'; error.hidden = false; return; } if (document.getElementById('zonal-publish-priority').value === 'Urgent') { emailModal.hidden = false; return; } publish(false); }); emailModal.querySelectorAll('[data-close-email]').forEach((button) => button.addEventListener('click', () => { emailModal.hidden = true; publish(false); })); document.getElementById('zonal-email-confirm').addEventListener('click', () => { emailModal.hidden = true; publish(true); });
    <?php endif; ?>
});
</script>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/announcements.css">
<script src="<?= ROOT ?>/assets/js/announcements.js"></script>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
