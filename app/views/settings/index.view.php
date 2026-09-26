<?php
/**
 * Settings View — CRUD-03
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/settings.css'];
$pageScripts = [ROOT . '/assets/js/settings.js'];
$maskedEmail = $maskedEmail ?? '';
$csrfToken = $csrf_token ?? '';

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$settingsInitials = strtoupper(
    mb_substr($user['first_name'] ?? 'Y', 0, 1) . mb_substr($user['last_name'] ?? 'N', 0, 1)
);
?>

<div class="settings-container">
    <!-- Tabs Navigation -->
    <nav class="settings-tabs">
        <button type="button" class="tab-btn is-active" data-target="profile-settings">
            <span class="icon"><?= yn_icon('user') ?></span> Edit Profile
        </button>
        <button type="button" class="tab-btn" data-target="security-settings">
            <span class="icon"><?= yn_icon('lock') ?></span> Security
        </button>
        <button type="button" class="tab-btn" data-target="notification-settings">
            <span class="icon"><?= yn_icon('bell') ?></span> Notifications
        </button>
    </nav>

    <!-- Settings Content -->
    <div class="settings-content">
        <!-- Profile Settings -->
        <section id="profile-settings" class="settings-pane is-active">
            <div class="pane-header">
                <h2>Profile Information</h2>
                <p>Update your personal details and how others see you.</p>
            </div>
            <form class="settings-form">
                <div class="avatar-edit-section">
                    <div class="profile-avatar-preview">
                        <span class="profile-initials" aria-hidden="true"><?= $escape($settingsInitials) ?></span>
                    </div>
                    <div class="avatar-actions">
                        <button type="button" class="btn-upload">Upload new picture</button>
                        <button type="button" class="btn-delete">Delete</button>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" value="<?= $escape($user['first_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" value="<?= $escape($user['last_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?= $escape($user['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" value="<?= $escape($user['phone']) ?>">
                    </div>
                    <div class="form-group full-width">
                        <label for="address">Address</label>
                        <input type="text" id="address" value="<?= $escape($user['address']) ?>">
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <button type="button" class="btn-cancel">Cancel</button>
                </div>
            </form>
        </section>

        <!-- Security Settings -->
        <section id="security-settings" class="settings-pane">
            <div class="pane-header">
                <h2>Security</h2>
                <p>Manage your password and account security.</p>
            </div>
            <form class="settings-form" id="security-form" novalidate data-action-base="<?= ROOT ?>/settings">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <div class="form-group">
                    <label for="current-password">Current Password</label>
                    <input type="password" id="current-password" name="current" placeholder="••••••••" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new" placeholder="••••••••" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm-password">Re-enter New Password</label>
                    <input type="password" id="confirm-password" name="confirm" placeholder="••••••••" autocomplete="new-password">
                </div>
                <p id="pw-form-error" class="dw-field-error" hidden></p>
                <div class="form-footer">
                    <button type="submit" class="btn-save">Update Password</button>
                </div>
            </form>
        </section>

        <!-- Notification Settings -->
        <section id="notification-settings" class="settings-pane">
            <div class="pane-header">
                <h2>Notifications</h2>
                <p>Control which notifications you receive.</p>
            </div>
            <div class="notification-list">
                <div class="notification-item">
                    <div class="item-info">
                        <h3>Enable Email Notifications</h3>
                        <p>Receive general account updates via email.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" <?= $user['notifications']['email_enabled'] ? 'checked' : '' ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="notification-item">
                    <div class="item-info">
                        <h3>Send Announcements via Email</h3>
                        <p>Get notified about new announcements directly in your inbox.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" <?= $user['notifications']['announcements_email'] ? 'checked' : '' ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            <div class="form-footer">
                <button type="button" class="btn-save">Save Preferences</button>
            </div>
        </section>
    </div>
</div>

<!-- Password verification code modal -->
<div id="pw-code-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="pw-code-title" hidden>
    <div class="dw-modal__backdrop" data-modal-close></div>
    <div class="dw-modal__dialog" role="document">
        <header class="dw-modal__header">
            <div>
                <p>Security check</p>
                <h2 id="pw-code-title">Enter verification code</h2>
            </div>
            <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
        </header>
        <div class="dw-modal__body" id="pw-code-body">
            <p>We sent a 6-digit code<?php if ($maskedEmail !== ''): ?> to <?= $escape($maskedEmail) ?><?php endif; ?>. It expires in 15 minutes.</p>
            <div class="dw-field dw-field--span-2">
                <label for="pw-code-input">Verification code</label>
                <input id="pw-code-input" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="••••••">
            </div>
            <p id="pw-code-error" class="dw-field-error" hidden></p>
            <p id="pw-code-resend-wrap">Didn't get it? <button type="button" id="pw-code-resend" class="dw-button dw-button--ghost">Resend code</button> <span id="pw-code-cooldown"></span></p>
        </div>
        <div class="dw-modal__body" id="pw-code-success" hidden>
            <p>Your password has been changed.</p>
        </div>
        <footer class="dw-modal__footer" id="pw-code-footer">
            <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
            <button type="button" class="dw-button dw-button--primary" id="pw-code-confirm">Confirm</button>
        </footer>
    </div>
</div>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
