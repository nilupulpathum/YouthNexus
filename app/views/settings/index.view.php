<?php
/**
 * Settings View — CRUD-03
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="settings-container">
    <!-- Tabs Navigation -->
    <nav class="settings-tabs">
        <button type="button" class="tab-btn is-active" data-target="profile-settings">
            <span class="icon">👤</span> Edit Profile
        </button>
        <button type="button" class="tab-btn" data-target="security-settings">
            <span class="icon">🔒</span> Security
        </button>
        <button type="button" class="tab-btn" data-target="notification-settings">
            <span class="icon">🔔</span> Notifications
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
                        <img src="<?= ROOT ?>/assets/images/avatar-placeholder.png" alt="Avatar">
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
            <form class="settings-form">
                <div class="form-group">
                    <label for="current-password">Current Password</label>
                    <input type="password" id="current-password" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label for="confirm-password">Re-enter New Password</label>
                    <input type="password" id="confirm-password" placeholder="••••••••">
                </div>
                <div class="form-footer">
                    <button type="button" class="btn-verify-code">Send Verification Code</button>
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

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/settings.css">
<script src="<?= ROOT ?>/assets/js/settings.js"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
