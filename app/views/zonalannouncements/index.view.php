<?php
$escape = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$can_publish = !empty($can_publish);
$announcements = $announcements ?? [];

require __DIR__ . '/../partials/icons.view.php';

$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/announcements.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/zonal.js'];

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
        <?php if (!empty($flash)): ?>
            <div class="dw-alert dw-alert--<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status"><?= $escape($flash['message'] ?? '') ?></div>
        <?php endif; ?>
        <header class="announcements-header">
            <div class="results-info">Showing <strong id="zonal-announcement-total"><?= count($announcements) ?></strong> announcements · <span class="unread-highlight" id="zonal-unread-count"><?= $escape($unreadCount) ?> unread</span></div>
            <div class="sort-control">
                <span>Sort by:</span>
                <select class="sort-select" id="zonal-announcement-sort"><option value="newest">Newest first</option><option value="oldest">Oldest first</option></select>
                <?php if ($can_publish): ?><button type="button" class="publish-open-btn" id="zonal-announce-open">Publish Announcement</button><?php endif; ?>
            </div>
        </header>

        <div class="announcements-list" id="zonal-announcement-list" data-can-publish="<?= !empty($can_publish) ? '1' : '0' ?>">
            <section class="announcement-section" id="zonal-unread-section">
                <h2 class="section-title">Unread <span class="badge" id="zonal-unread-badge">2</span></h2>
                <?php foreach ($announcements as $item): ?>
                    <?php if (!empty($item['is_unread'])): ?>
                        <article class="announcement-card is-unread" data-announcement-id="<?= (int) ($item['id'] ?? 0) ?>" data-level="<?= $escape(strtolower($item['scope'])) ?>" data-unread="true" data-announcement="<?= $escape(json_encode($item)) ?>">
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
                        <article class="announcement-card" data-announcement-id="<?= (int) ($item['id'] ?? 0) ?>" data-level="<?= $escape(strtolower($item['scope'])) ?>" data-unread="false" data-announcement="<?= $escape(json_encode($item)) ?>">
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

<div id="announcement-popup" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="popup-title" aria-hidden="true" hidden><div class="dw-modal__backdrop" data-modal-close></div><div class="dw-modal__dialog"><button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button><div class="popup-header"><p class="popup-meta">Preview</p><h2 id="popup-title"></h2><div class="popup-tags"><span id="popup-scope" class="scope-badge national"></span><span id="popup-new" class="new-badge" hidden>New</span></div><p class="popup-date-info"><span class="icon"><?= yn_icon('calendar') ?></span> <span id="popup-date"></span><span class="icon"><?= yn_icon('clock') ?></span> <span id="popup-age"></span></p></div><div class="popup-body"><p id="popup-summary"></p><div id="popup-attachment-container" class="attachment-box" hidden><div class="attachment-info"><span class="icon"><?= yn_icon('file') ?></span><div><p id="popup-attachment-name"></p><span id="popup-attachment-size"></span></div></div></div></div><div class="popup-footer"><button type="button" class="btn-download"><span class="icon"><?= yn_icon('download') ?></span> Download Attachment</button><button type="button" class="btn-mark-read"><span class="icon"><?= yn_icon('check') ?></span> Mark as Read</button></div></div></div>

<?php if ($can_publish): ?>
<div id="zonal-publish-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="zonal-publish-title" aria-hidden="true" hidden><div class="dw-modal__backdrop" data-modal-close></div><div class="dw-modal__dialog"><button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button><p class="popup-meta">New zone announcement</p><h2 id="zonal-publish-title">Publish Announcement</h2><form id="zonal-publish-form" action="<?= ROOT ?>/zonalannouncements/publish" method="post" enctype="multipart/form-data" novalidate><input type="hidden" name="csrf_token" value="<?= $escape($csrf_token ?? '') ?>"><div class="publish-field"><label for="zonal-publish-heading">Title</label><input id="zonal-publish-heading" name="title" type="text" required maxlength="150" autocomplete="off" placeholder="e.g., Zone training date changed"></div><div class="publish-field"><label for="zonal-publish-body">Body</label><textarea id="zonal-publish-body" name="body" rows="4" required maxlength="1000" placeholder="What should divisions and clubs know?"></textarea></div><div class="publish-field"><label for="zonal-publish-attachment">Attachment (optional)</label><input id="zonal-publish-attachment" name="attachment" type="file" accept="image/*,.pdf,.doc,.docx"><p id="zonal-publish-file-name" class="popup-text" hidden></p></div><div class="publish-grid"><div class="publish-field"><label for="zonal-publish-scope">Scope</label><input id="zonal-publish-scope" type="text" readonly value="Zone - divisions and clubs"></div><div class="publish-field"><label for="zonal-publish-priority">Priority</label><select id="zonal-publish-priority" name="priority"><option value="Normal">Normal</option><option value="Urgent">Urgent</option></select></div></div><p id="zonal-publish-error" class="publish-error" hidden></p><div class="publish-footer"><button type="button" class="btn-cancel" data-modal-close>Cancel</button><button type="submit" class="btn-publish">Publish</button></div></form></div></div>
<div id="zonal-publish-email-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="zonal-email-title" aria-hidden="true" hidden><div class="dw-modal__backdrop" data-modal-close></div><div class="dw-modal__dialog"><p class="popup-meta">Urgent announcement</p><h2 id="zonal-email-title">Send email as well?</h2><p class="popup-text">Urgent announcements notify divisions and their clubs by email. Send the email together with this post?</p><div class="publish-footer"><button type="button" class="btn-cancel" data-close-email>Post without email</button><button type="button" class="btn-publish" id="zonal-email-confirm">Send email and post</button></div></div></div>
<?php endif; ?>

<div id="zonal-announce-toast" class="announce-toast" role="status" hidden></div>


<script src="<?= ROOT ?>/assets/js/announcements.js"></script>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
