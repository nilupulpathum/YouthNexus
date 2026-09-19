<?php
/**
 * Announcements View — W.14 (+ C16 publish flow for president/secretary).
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$can_publish = !empty($can_publish);

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="announcements-container">
    <!-- Filter Sidebar -->
    <aside class="announcements-filters">
        <div class="filter-group">
            <h3>LEVEL</h3>
            <label class="filter-option">
                <input type="radio" name="level" value="all" checked>
                <span>All Levels</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="level" value="national">
                <span>National</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="level" value="zonal">
                <span>Zonal</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="level" value="divisional">
                <span>Divisional</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="level" value="club">
                <span>Club-specific</span>
            </label>
        </div>

        <div class="filter-group">
            <h3>DATE POSTED</h3>
            <label class="filter-option">
                <input type="radio" name="date" value="all" checked>
                <span>All Time</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="date" value="7days">
                <span>Last 7 Days</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="date" value="30days">
                <span>Last 30 Days</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="date" value="3months">
                <span>Last 3 Months</span>
            </label>
        </div>

        <div class="filter-group">
            <h3>READ STATUS</h3>
            <label class="filter-option">
                <input type="radio" name="status" value="all" checked>
                <span>All Status</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="status" value="read">
                <span>Read</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="status" value="unread">
                <span>Unread</span>
            </label>
        </div>

        <button type="button" class="clear-filters-btn">
            <span class="icon"><?= yn_icon('close') ?></span> Clear all filters
        </button>

        <div class="sidebar-bottom-action">
            <button type="button" class="mark-all-read-btn">
                <span class="icon"><?= yn_icon('check') ?></span> Mark all as read
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="announcements-main">
        <header class="announcements-header">
            <div class="results-info">
                Showing <strong><?= count($announcements) ?></strong> announcements · <span class="unread-highlight"><?= $unreadCount ?> unread</span>
            </div>
            <div class="sort-control">
                <span>Sort by:</span>
                <select class="sort-select">
                    <option value="newest">Newest first</option>
                    <option value="oldest">Oldest first</option>
                </select>
                <?php if ($can_publish): ?>
                    <button type="button" class="publish-open-btn" id="announce-open">Publish Announcement</button>
                <?php endif; ?>
            </div>
        </header>

        <div class="announcements-list">
            <?php
            $unread = array_filter($announcements, fn($a) => $a['is_unread']);
            $earlier = array_filter($announcements, fn($a) => !$a['is_unread']);
            ?>

            <?php if (!empty($unread)): ?>
                <section class="announcement-section">
                    <h2 class="section-title">Unread <span class="badge"><?= count($unread) ?></span></h2>
                    <?php foreach ($unread as $item): ?>
                        <?php require __DIR__ . '/../partials/announcement-card.view.php'; ?>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($earlier)): ?>
                <section class="announcement-section">
                    <h2 class="section-title">Earlier</h2>
                    <?php foreach ($earlier as $item): ?>
                        <?php require __DIR__ . '/../partials/announcement-card.view.php'; ?>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Detail Popup -->
<div id="announcement-popup" class="popup-overlay" hidden>
    <div class="popup-content">
        <button type="button" class="popup-close" aria-label="Close"><?= yn_icon('close') ?></button>
        <div class="popup-header">
            <p class="popup-meta">Preview</p>
            <h2 id="popup-title"></h2>
            <div class="popup-tags">
                <span id="popup-scope" class="scope-badge"></span>
                <span id="popup-new" class="new-badge" hidden>New</span>
            </div>
            <p class="popup-date-info">
                <span class="icon"><?= yn_icon('calendar') ?></span> <span id="popup-date"></span>
                <span class="separator">·</span>
                <span class="icon"><?= yn_icon('clock') ?></span> <span id="popup-age"></span>
            </p>
        </div>
        <div class="popup-body">
            <p id="popup-summary"></p>
            <div id="popup-attachment-container" class="attachment-box" hidden>
                <div class="attachment-info">
                    <span class="icon"><?= yn_icon('file') ?></span>
                    <div>
                        <p id="popup-attachment-name"></p>
                        <span id="popup-attachment-size"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="popup-footer">
            <button type="button" class="btn-download">
                <span class="icon"><?= yn_icon('download') ?></span> Download Attachment
            </button>
            <button type="button" class="btn-mark-read">
                <span class="icon"><?= yn_icon('check') ?></span> Mark as Read
            </button>
        </div>
    </div>
</div>

<?php if ($can_publish): ?>
    <div id="publish-modal" class="popup-overlay" hidden>
        <div class="popup-content" role="dialog" aria-modal="true" aria-labelledby="publish-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <p class="popup-meta">New club announcement</p>
            <h2 id="publish-title">Publish Announcement</h2>
            <form id="publish-form" novalidate>
                <div class="publish-field">
                    <label for="publish-heading">Title</label>
                    <input id="publish-heading" type="text" required maxlength="150" autocomplete="off" placeholder="e.g., Weekend practice cancelled">
                </div>
                <div class="publish-field">
                    <label for="publish-body">Body</label>
                    <textarea id="publish-body" rows="4" required maxlength="1000" placeholder="What should members know?"></textarea>
                </div>
                <div class="publish-field">
                    <label for="publish-attachment">Attachment (optional)</label>
                    <input id="publish-attachment" type="file" accept="image/*,.pdf,.doc,.docx">
                    <p id="publish-file-name" class="popup-text" hidden></p>
                </div>
                <div class="publish-grid">
                    <div class="publish-field">
                        <label for="publish-scope">Scope</label>
                        <input id="publish-scope" type="text" readonly value="Club — Gampaha Youth Development Club">
                    </div>
                    <div class="publish-field">
                        <label for="publish-priority">Priority</label>
                        <select id="publish-priority">
                            <option value="Normal">Normal</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <p id="publish-error" class="publish-error" hidden></p>
                <div class="publish-footer">
                    <button type="button" class="btn-cancel" data-close>Cancel</button>
                    <button type="submit" class="btn-publish">Publish</button>
                </div>
            </form>
        </div>
    </div>

    <div id="publish-email-modal" class="popup-overlay" hidden>
        <div class="popup-content popup-content--narrow" role="dialog" aria-modal="true" aria-labelledby="email-title">
            <p class="popup-meta">Urgent announcement</p>
            <h2 id="email-title">Send email as well?</h2>
            <p class="popup-text">Urgent announcements email every club member. Send the email together with this post?</p>
            <div class="publish-footer">
                <button type="button" class="btn-cancel" data-close-email>Post without email</button>
                <button type="button" class="btn-publish" id="email-confirm">Send email + post</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div id="announce-toast" class="announce-toast" role="status" hidden></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('announce-toast');
    let toastTimer = null;
    const showToast = (msg) => {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    // C16 publish flow (president + secretary only).
    const openBtn = document.getElementById('announce-open');
    const modal = document.getElementById('publish-modal');
    const form = document.getElementById('publish-form');
    if (!openBtn || !modal || !form) return;

    const err = document.getElementById('publish-error');
    const emailModal = document.getElementById('publish-email-modal');
    const attachInput = document.getElementById('publish-attachment');
    const attachName = document.getElementById('publish-file-name');
    let pendingUrgent = false;

    const open = () => {
        form.reset();
        err.hidden = true;
        pendingUrgent = false;
        if (attachName) attachName.hidden = true;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    };
    const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
    openBtn.addEventListener('click', open);
    modal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

    if (attachInput && attachName) {
        attachInput.addEventListener('change', () => {
            if (attachInput.files.length) {
                attachName.textContent = 'Attached: ' + attachInput.files[0].name;
                attachName.hidden = false;
            } else {
                attachName.hidden = true;
            }
        });
    }

    const fmtSize = (bytes) => {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    };

    const iconHtml = (selector) => {
        const el = document.querySelector(selector);
        return el ? el.innerHTML : '';
    };

    const bumpCounts = () => {
        const total = document.querySelector('.results-info strong');
        if (total) total.textContent = String((parseInt(total.textContent, 10) || 0) + 1);
        const unread = document.querySelector('.unread-highlight');
        if (unread) {
            const n = (parseInt(unread.textContent, 10) || 0) + 1;
            unread.textContent = n + ' unread';
        }
    };

    const bindView = (btn) => {
        btn.addEventListener('click', () => {
            const card = btn.closest('.announcement-card');
            try {
                openAnnouncement(JSON.parse(card.getAttribute('data-announcement') || '{}'));
            } catch (e) { /* malformed payload — leave popup closed */ }
        });
    };

    const publish = (emailed) => {
        const title = document.getElementById('publish-heading').value.trim();
        const body = document.getElementById('publish-body').value.trim();
        const priority = document.getElementById('publish-priority').value;
        const file = (attachInput && attachInput.files.length) ? attachInput.files[0] : null;
        const item = {
            title: title,
            summary: body,
            scope: 'Club',
            date: 'Just now',
            age: 'Just now',
            is_new: true,
            is_unread: true,
            priority: priority,
        };
        if (file) {
            item.attachment = file.name;
            item.attachment_size = fmtSize(file.size);
        }
        const card = document.createElement('article');
        card.className = 'announcement-card is-unread';
        card.setAttribute('data-announcement', JSON.stringify(item));
        const header = document.createElement('div');
        header.className = 'card-header';
        const left = document.createElement('div');
        left.className = 'header-left';
        const scope = document.createElement('span');
        scope.className = 'scope-badge club';
        scope.textContent = 'Club';
        const badge = document.createElement('span');
        badge.className = 'new-badge';
        badge.textContent = 'New';
        left.appendChild(scope);
        left.appendChild(badge);
        const age = document.createElement('span');
        age.className = 'age-text';
        age.textContent = 'Just now';
        header.appendChild(left);
        header.appendChild(age);
        const h3 = document.createElement('h3');
        h3.className = 'card-title';
        h3.textContent = title;
        const p = document.createElement('p');
        p.className = 'card-summary';
        p.textContent = body;
        const footer = document.createElement('div');
        footer.className = 'card-footer';
        const view = document.createElement('button');
        view.type = 'button';
        view.className = 'btn-read-more';
        view.setAttribute('data-action', 'view');
        const viewIcon = document.createElement('span');
        viewIcon.className = 'icon';
        viewIcon.innerHTML = iconHtml('.btn-read-more .icon');
        view.appendChild(viewIcon);
        view.appendChild(document.createTextNode(' Read More'));
        footer.appendChild(view);
        const mark = document.createElement('button');
        mark.type = 'button';
        mark.className = 'btn-mark-as-read';
        const markIcon = document.createElement('span');
        markIcon.className = 'icon';
        markIcon.innerHTML = iconHtml('.btn-mark-as-read .icon');
        mark.appendChild(markIcon);
        mark.appendChild(document.createTextNode(' Mark as Read'));
        footer.appendChild(mark);
        card.appendChild(header);
        card.appendChild(h3);
        card.appendChild(p);
        if (file) {
            const attRow = document.createElement('div');
            attRow.className = 'card-attachment';
            const fileIcon = document.createElement('span');
            fileIcon.className = 'icon';
            fileIcon.innerHTML = iconHtml('.card-attachment .icon');
            const fileName = document.createElement('span');
            fileName.className = 'filename';
            fileName.textContent = file.name + ' (' + fmtSize(file.size) + ')';
            const dlBtn = document.createElement('button');
            dlBtn.type = 'button';
            dlBtn.className = 'btn-download-attachment';
            const dlIcon = document.createElement('span');
            dlIcon.className = 'icon download-icon';
            dlIcon.innerHTML = iconHtml('.card-attachment .download-icon');
            dlBtn.appendChild(dlIcon);
            const objectUrl = URL.createObjectURL(file);
            const downloadFile = () => {
                const a = document.createElement('a');
                a.href = objectUrl;
                a.download = file.name;
                document.body.appendChild(a);
                a.click();
                a.remove();
            };
            dlBtn.addEventListener('click', downloadFile);
            attRow.appendChild(fileIcon);
            attRow.appendChild(fileName);
            attRow.appendChild(dlBtn);
            card.appendChild(attRow);
            const dlFooterBtn = document.createElement('button');
            dlFooterBtn.type = 'button';
            dlFooterBtn.className = 'btn-download-attachment';
            const dlFooterIcon = document.createElement('span');
            dlFooterIcon.className = 'icon';
            dlFooterIcon.innerHTML = iconHtml('.btn-download-attachment .icon');
            dlFooterBtn.appendChild(dlFooterIcon);
            dlFooterBtn.appendChild(document.createTextNode(' Download Attachment'));
            dlFooterBtn.addEventListener('click', downloadFile);
            footer.insertBefore(dlFooterBtn, mark);
        }
        card.appendChild(footer);
        const list = document.querySelector('.announcements-list');
        const firstSection = list ? list.querySelector('.announcement-section') : null;
        if (firstSection) {
            firstSection.insertBefore(card, firstSection.querySelector('.announcement-card'));
        } else if (list) {
            list.prepend(card);
        }
        bindView(view);
        bumpCounts();
        close();
        showToast(priority === 'Urgent' && emailed
            ? 'Urgent announcement published + emailed to members (demo).'
            : 'Announcement published to the club feed (demo — persists in C13 backend).');
    };

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const title = document.getElementById('publish-heading').value.trim();
        const body = document.getElementById('publish-body').value.trim();
        err.hidden = true;
        if (!title || !body) {
            err.textContent = 'Title and body are required.';
            err.hidden = false;
            return;
        }
        if (document.getElementById('publish-priority').value === 'Urgent') {
            pendingUrgent = true;
            emailModal.hidden = false;
            return;
        }
        publish(false);
    });

    if (emailModal) {
        const closeEmail = () => { emailModal.hidden = true; pendingUrgent = false; };
        emailModal.querySelectorAll('[data-close-email]').forEach(b => b.addEventListener('click', () => {
            closeEmail();
            publish(false);
        }));
        emailModal.addEventListener('click', (e) => { if (e.target === emailModal) closeEmail(); });
        document.getElementById('email-confirm').addEventListener('click', () => {
            emailModal.hidden = true;
            pendingUrgent = false;
            publish(true);
        });
    }
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/announcements.css">
<script src="<?= ROOT ?>/assets/js/announcements.js"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
