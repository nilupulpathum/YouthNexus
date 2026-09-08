<?php
/**
 * Social CV View — A.08
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

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
                        <span>NIC: <?= $escape($profile['nic']) ?></span>
                        <span class="separator">·</span>
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

        <!-- Skills & Badges -->
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

        <!-- Event Timeline -->
        <section class="cv-section">
            <h2 class="cv-section-title"><span class="icon"><?= yn_icon('calendar') ?></span> Event Participation Timeline</h2>
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
                                <span class="role-tag"><?= $escape($event['role']) ?></span>
                                <span class="hours-tag"><span class="icon"><?= yn_icon('clock') ?></span> <?= $escape($event['hours']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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
                        <p class="pos-desc"><?= $escape($pos['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Endorsements -->
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
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<!-- Public Profile URL Footer -->
<div class="cv-footer-actions">
    <div class="public-url-box">
        <span class="label">PUBLIC PROFILE URL</span>
        <div class="url-input-group">
            <input type="text" value="pulse.nysc.lk/cv/nuwan-bandara-0142" readonly>
            <button type="button" class="btn-copy">Copy</button>
        </div>
        <span class="last-updated">Last updated: Sep 10, 2026</span>
    </div>
    <div class="footer-buttons">
        <button type="button" class="btn-download-pdf-large"><span class="icon"><?= yn_icon('download') ?></span> Download as PDF</button>
    </div>
</div>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/profile.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
