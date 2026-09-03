<?php
/**
 * Broadcast Announcements — Detail Page (Divisional Level)
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 */
$title           = $title ?? 'Announcement Details — YouthNexus';
$pageTitle       = 'Broadcast Announcement';
$pageDescription = 'Create and publish announcements to clubs across the division';
$currentRoute    = 'announcements';

require __DIR__ . '/../layouts/dashboard-start.view.php';
require __DIR__ . '/helpers.php';

$targetLabels = [
    'AllDivisionalClubs'        => 'All Divisional Clubs',
    'ClubPresidentsSecretaries' => 'Club Presidents, Secretaries',
    'AllMembers'                => 'All Members',
];
$targetLabel = $targetLabels[$announcement->target_audience ?? ''] ?? 'Pending';

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 0) . ' KB';
    }
    return $bytes . ' B';
}
?>

<a href="<?= ROOT ?>/announcements" class="ann-card-link ann-back-link">&larr; Back to Broadcast</a>

<div class="ann-detail-grid">
  <div class="ann-detail-main">
    <div class="ann-detail-badges">
      <span class="ann-badge-divisional"><?= htmlspecialchars($announcement->level ?? 'Divisional') ?> Level</span>
      <?php if (empty($hasRead) && $announcement->status === 'Published'): ?>
        <span class="ann-badge-new" id="annNewBadge">New</span>
      <?php endif; ?>
      <?php if ($announcement->status === 'Draft'): ?>
        <span class="ann-badge ann-badge-draft">DRAFT</span>
      <?php elseif ($announcement->priority === 'Urgent'): ?>
        <span class="ann-badge ann-badge-urgent">URGENT</span>
      <?php endif; ?>
    </div>

    <h2 class="ann-detail-title"><?= htmlspecialchars($announcement->title) ?></h2>

    <div class="ann-detail-meta">
      <div>Created <?= $annDate($announcement->created_at) ?></div>
      <?php if ($announcement->status === 'Published'): ?>
        <div>Published <?= $annDate($announcement->published_at ?? $announcement->created_at) ?></div>
      <?php endif; ?>
      <?php if (!empty($announcement->content_edited_at)): ?>
        <div class="ann-edited-note"><?= $annIcon('edit') ?> <span>Edited <?= $annDate($announcement->content_edited_at) ?></span></div>
      <?php endif; ?>
      By <?= htmlspecialchars($announcement->posted_by_name ?? 'Secretary') ?>, <?= htmlspecialchars($announcement->posted_by_role ?? 'DivisionalSecretary') ?>
      &middot; <?= (int)$announcement->view_count ?> Views
    </div>

    <div class="ann-detail-body">
      <?= nl2br(htmlspecialchars($announcement->body)) ?>
    </div>

    <?php if (!empty($attachments)): ?>
    <div class="ann-attachments-box">
      <b>Attachments (<?= count($attachments) ?>)</b>
      <?php foreach ($attachments as $att): ?>
        <div class="ann-attachment-row">
          <span><?= $annIcon('file') ?> <?= htmlspecialchars($att->file_name) ?> &middot; <?= formatFileSize($att->file_size) ?></span>
          <a href="<?= ROOT ?>/announcements/download/<?= (int)$att->attachment_id ?>" download aria-label="Download <?= htmlspecialchars($att->file_name, ENT_QUOTES) ?>"><?= $annIcon('download') ?> Download</a>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="ann-side-card">
      <?php if (!empty($isOwnAnnouncement)): ?>
        <a class="ann-btn ann-btn-primary" href="<?= ROOT ?>/announcements?edit=<?= (int)$announcement->announcement_id ?>"><?= $annIcon('edit') ?> <?= $announcement->status === 'Draft' ? 'Edit Draft' : 'Edit Announcement' ?></a>
      <?php endif; ?>
      <?php if ($announcement->status === 'Published' && !empty($hasRead)): ?>
        <button type="button" class="ann-btn ann-btn-secondary" disabled><?= $annIcon('check') ?> Read</button>
      <?php elseif ($announcement->status === 'Published'): ?>
        <button type="button" class="ann-btn ann-btn-primary" id="annMarkReadBtn" onclick="markAsRead(<?= (int)$announcement->announcement_id ?>)"><?= $annIcon('check') ?> Mark as Read</button>
      <?php endif; ?>
    </div>

    <div class="ann-side-card">
      <h3 class="ann-side-heading">Announcement Details</h3>
      <div class="ann-side-details">
        <div class="ann-detail-row">
          <div class="lbl">Level</div>
          <div class="val"><?= htmlspecialchars($announcement->level ?? 'Divisional') ?> Level</div>
        </div>
        <div class="ann-detail-row">
          <div class="lbl">Category</div>
          <div class="val"><?= htmlspecialchars($announcement->category ?? '—') ?></div>
        </div>
        <div class="ann-detail-row">
          <div class="lbl">Posted By</div>
          <div class="val">
            <?= htmlspecialchars($announcement->posted_by_name ?? 'Secretary') ?><br>
            <small class="ann-field-help"><?= htmlspecialchars($announcement->posted_by_role ?? 'DivisionalSecretary') ?></small>
          </div>
        </div>
        <div class="ann-detail-row">
          <div class="lbl">Target Audience</div>
          <div class="val"><?= htmlspecialchars($targetLabel) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="ann-toast" id="annToast" role="status" aria-live="polite"
     data-root="<?= htmlspecialchars(rtrim(ROOT, '/'), ENT_QUOTES, 'UTF-8') ?>"
     data-csrf="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>"></div>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/announcements.css">
<script src="<?= ROOT ?>/assets/js/announcements.js?v=<?= time() ?>"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
