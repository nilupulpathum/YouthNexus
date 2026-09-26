<?php
/**
 * Social CV View — A.08
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$pageStyles = [ROOT . '/assets/css/profile.css'];
$pageScripts = [ROOT . '/assets/js/profile.js'];
$isSelf = $isSelf ?? true;
$canEndorse = ($canEndorse ?? false) && !$isSelf;
$flash = $flash ?? null;
$deletableIds = (isset($deletableIds) && is_array($deletableIds)) ? array_map('intval', $deletableIds) : [];

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
    <div class="cv-flash cv-flash--<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status">
        <?= $escape($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="profile-actions-top">
    <button type="button" class="btn-share"><span class="icon"><?= yn_icon('link') ?></span> Share Profile</button>
    <button type="button" class="btn-download-pdf"><span class="icon"><?= yn_icon('download') ?></span> Download PDF</button>
</div>

<div class="social-cv-container">
    <!-- Left Column -->
    <div class="cv-main-col">
        <!-- Profile Info Card -->
        <section class="cv-section profile-hero-card">
            <div class="hero-content">
                <div class="profile-avatar-large">
                    <span class="profile-initials" aria-hidden="true"><?= $escape(mb_substr($profile['name'] ?? 'Y', 0, 1)) ?></span>
                    <span class="verified-badge"><?= yn_icon('check') ?></span>
                </div>
                <div class="hero-details">
                    <h1><?= $escape($profile['name']) ?></h1>
                    <div class="meta-row">
                        <span>ID: <?= $escape($profile['member_id']) ?></span>
                    </div>
                    <div class="meta-row secondary">
                        <span class="icon"><?= yn_icon('pin') ?></span> <?= $escape($profile['location']) ?>
                        <span class="separator">·</span>
                        <span class="icon"><?= yn_icon('calendar') ?></span> Member since <?= $escape($profile['member_since']) ?>
                    </div>
                    <p class="profile-bio"><?= $escape($profile['bio']) ?></p>
                </div>
            </div>
        </section>

        <!-- Quick Stats -->
        <div class="cv-stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <span class="stat-icon"><?= yn_icon($stat['icon'] ?? 'check') ?></span>
                    <div class="stat-value"><?= $escape($stat['value']) ?></div>
                    <div class="stat-label"><?= $escape($stat['label']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Skills & Badges (hidden until a skill taxonomy exists) -->
        <?php if (!empty($skills)): ?>
        <section class="cv-section">
            <h2 class="cv-section-title"><span class="icon"><?= yn_icon('check') ?></span> Skills & Competency Badges</h2>
            <div class="skills-grid">
                <?php foreach ($skills as $skill): ?>
                    <div class="skill-badge-card">
                        <div class="skill-icon"><?= yn_icon($skill['icon'] ?? 'check') ?></div>
                        <h3><?= $escape($skill['name']) ?></h3>
                        <span class="skill-level <?= strtolower($skill['level']) ?>">● <?= $escape($skill['level']) ?></span>
                        <span class="skill-events"><?= $escape($skill['events']) ?> events</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Event Timeline -->
        <section class="cv-section">
            <h2 class="cv-section-title"><span class="icon"><?= yn_icon('calendar') ?></span> Event Participation Timeline</h2>
            <?php if (empty($timeline)): ?>
                <p class="profile-bio">No attended events yet.</p>
            <?php else: ?>
            <div class="timeline-list">
                <?php foreach ($timeline as $event): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker verified"><?= yn_icon('check') ?></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h3><?= $escape($event['title']) ?></h3>
                                <span class="scope-badge <?= strtolower($event['scope']) ?>"><?= $escape($event['scope']) ?></span>
                            </div>
                            <div class="timeline-meta">
                                <span><span class="icon"><?= yn_icon('calendar') ?></span> <?= $escape($event['date']) ?></span>
                                <span><span class="icon"><?= yn_icon('pin') ?></span> <?= $escape($event['location']) ?></span>
                                <?php if (($event['role'] ?? '') !== ''): ?>
                                    <span class="role-tag"><?= $escape($event['role']) ?></span>
                                <?php endif; ?>
                                <?php if (($event['hours'] ?? '') !== ''): ?>
                                    <span class="hours-tag"><span class="icon"><?= yn_icon('clock') ?></span> <?= $escape($event['hours']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Right Column -->
    <div class="cv-side-col">
        <!-- Positions Held -->
        <section class="cv-section side-section">
            <h2 class="cv-section-title"><span class="icon"><?= yn_icon('user') ?></span> Positions Held</h2>
            <div class="positions-list">
                <?php foreach ($positions as $pos): ?>
                    <div class="position-item">
                        <div class="pos-header">
                            <h3><?= $escape($pos['role']) ?></h3>
                            <span class="pos-date"><?= $escape($pos['date']) ?></span>
                        </div>
                        <p class="pos-club"><?= $escape($pos['club']) ?></p>
                        <?php if (($pos['description'] ?? '') !== ''): ?>
                            <p class="pos-desc"><?= $escape($pos['description']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Endorsements (hidden until an endorsement source exists) -->
        <?php if (!empty($endorsements)): ?>
        <section class="cv-section side-section">
            <h2 class="cv-section-title"><span class="icon"><?= yn_icon('pen') ?></span> Endorsements</h2>
            <div class="endorsements-list">
                <?php foreach ($endorsements as $end): ?>
                    <div class="endorsement-item">
                        <div class="end-header">
                            <div class="end-avatar" aria-hidden="true"><?= $escape(mb_strtoupper(mb_substr($end['name'] ?? '?', 0, 1))) ?></div>
                            <div>
                                <h4><?= $escape($end['name']) ?></h4>
                                <p><?= $escape($end['role']) ?></p>
                            </div>
                        </div>
                        <blockquote class="end-text">"<?= $escape($end['text']) ?>"</blockquote>
                        <span class="end-date"><?= $escape($end['date']) ?></span>
                        <?php if (in_array((int) ($end['id'] ?? 0), $deletableIds, true)): ?>
                            <form class="endorse-delete" method="post" action="<?= ROOT ?>/profile/deleteEndorsement">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrf_token ?? '') ?>">
                                <input type="hidden" name="endorsement_id" value="<?= (int) ($end['id'] ?? 0) ?>">
                                <button type="submit" class="btn-copy">Remove</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php if ($canEndorse): ?>
            <section class="cv-section side-section">
                <h2 class="cv-section-title"><span class="icon"><?= yn_icon('pen') ?></span> Write an endorsement</h2>
                <form class="endorse-form" method="post" action="<?= ROOT ?>/profile/endorse">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrf_token ?? '') ?>">
                    <input type="hidden" name="member_id" value="<?= (int) ($targetUserId ?? 0) ?>">
                    <label class="endorse-label" for="endorse-text">Your endorsement (10–1000 characters)</label>
                    <textarea id="endorse-text" name="text" rows="4" maxlength="1000" required placeholder="What has this member contributed?"></textarea>
                    <button type="submit" class="btn-download-pdf">Save endorsement</button>
                </form>
            </section>
        <?php endif; ?>
    </div>
</div>

<!-- Public Profile URL Footer -->
<div class="cv-footer-actions">
    <div class="public-url-box">
        <span class="label">PUBLIC PROFILE URL</span>
        <div class="url-input-group">
            <input type="text" value="<?= $escape($publicUrl ?? '') ?>" readonly>
            <button type="button" class="btn-copy">Copy</button>
        </div>
        <span class="last-updated">Last updated: <?= $escape($lastUpdated ?? '') ?></span>
    </div>
    <div class="footer-buttons">
        <button type="button" class="btn-download-pdf-large"><span class="icon"><?= yn_icon('download') ?></span> Download as PDF</button>
    </div>
</div>


<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
