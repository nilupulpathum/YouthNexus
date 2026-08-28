<?php
/**
 * Help View
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="help-container">
    <div class="help-main-content">
        <!-- FAQ Section -->
        <section class="help-section">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-list">
                <?php foreach ($faqs as $index => $faq): ?>
                    <details class="faq-item">
                        <summary><?= $escape($faq['question']) ?></summary>
                        <div class="faq-answer">
                            <p><?= $escape($faq['answer']) ?></p>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Video Section -->
        <section class="help-section">
            <h2 class="section-title">Platform Walkthrough</h2>
            <div class="video-placeholder">
                <div class="video-overlay">
                    <span class="play-icon">▶</span>
                    <p>Watch Explainer Video</p>
                </div>
            </div>
        </section>
    </div>

    <!-- Contact Sidebar -->
    <aside class="help-sidebar">
        <section class="help-section contact-section">
            <h2 class="section-title">Contact Us</h2>
            <p class="contact-desc">Need direct assistance? Send us a message.</p>
            <form class="contact-form">
                <div class="form-group">
                    <label for="contact-name">Full Name</label>
                    <input type="text" id="contact-name" placeholder="Jamie Dela Cruz" required>
                </div>
                <div class="form-group">
                    <label for="contact-email">Email Address</label>
                    <input type="email" id="contact-email" placeholder="jamie@example.test" required>
                </div>
                <div class="form-group">
                    <label for="contact-message">Message</label>
                    <textarea id="contact-message" rows="5" placeholder="How can we help you?" required></textarea>
                </div>
                <button type="submit" class="btn-submit-contact">Submit Message</button>
            </form>
        </section>
    </aside>
</div>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/help.css">
<script src="<?= ROOT ?>/assets/js/help.js"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
