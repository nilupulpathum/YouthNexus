<?php
/**
 * Broadcast Announcements — List Page (Divisional Level)
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 */
$title           = $title ?? 'Broadcast Announcement — YouthNexus';
$pageTitle       = 'Broadcast Announcement';
$pageDescription = 'Create and publish announcements to clubs across the division';
$currentRoute    = 'announcements';

require __DIR__ . '/../layouts/dashboard-start.view.php';
require __DIR__ . '/helpers.php';
?>

<div class="ann-header-row">
  <div><h2 class="ann-section-title">Announcements</h2><p class="ann-section-subtitle">Manage divisional updates and keep your clubs informed.</p></div>
  <?php if (!empty($canCreate)): ?>
    <button type="button" class="ann-btn ann-btn-primary" id="annOpenCreateBtn"><?= $annIcon('plus') ?> New Announcement</button>
  <?php endif; ?>
</div>
<div class="ann-stats">
  <?php foreach (['All' => 'Total Announcements', 'Published' => 'Published', 'Draft' => 'Your Drafts'] as $key => $label):
    if ($key === 'Draft' && empty($canCreate)) continue; ?>
    <button type="button" class="ann-stat-card <?= $key === 'All' ? 'is-active' : '' ?>"
            data-ann-status="<?= $key === 'All' ? '' : $key ?>"
            aria-pressed="<?= $key === 'All' ? 'true' : 'false' ?>" aria-controls="annResults">
      <span class="ann-stat-icon <?= strtolower($key) ?>"><?= $annIcon($key === 'Published' ? 'check' : ($key === 'Draft' ? 'edit' : 'broadcast')) ?></span>
      <span><span class="ann-stat-number"><?= (int)($counts[$key] ?? 0) ?></span><span class="ann-stat-label"><?= $label ?></span></span>
    </button>
  <?php endforeach; ?>
</div>
<div class="ann-toolbar">
  <div class="ann-search-wrap">
    <?= $annIcon('search') ?>
    <label class="visually-hidden" for="annSearchInput">Search announcements</label>
    <input type="search" id="annSearchInput" placeholder="Search announcements...">
  </div>
  <div class="ann-filter-actions">
    <label class="visually-hidden" for="annTabSelect">Announcement status</label>
    <select class="ann-tab-select" id="annTabSelect">
      <option>All Announcements</option><option>Published</option>
      <?php if (!empty($canCreate)): ?><option>Drafts</option><?php endif; ?>
    </select>
    <button type="button" class="ann-btn ann-btn-secondary" id="annFilterBtn" aria-expanded="false" aria-controls="annFilterPanel"><?= $annIcon('filter') ?> Filters</button>
  </div>
</div>

<div class="ann-filter-panel" id="annFilterPanel">
  <div class="ann-filter-field">
    <label for="annFilterStatus">Status</label>
    <select id="annFilterStatus">
      <option value="">All Statuses</option>
      <option value="Published">Published</option>
      <?php if (!empty($canCreate)): ?><option value="Draft">Draft</option><?php endif; ?>
    </select>
  </div>
  <div class="ann-filter-field">
    <label for="annFilterAudience">Target Audience</label>
    <select id="annFilterAudience">
      <option value="">All Audiences</option>
      <option value="AllDivisionalClubs">All Divisional Clubs</option>
      <option value="ClubPresidentsSecretaries">Club Presidents &amp; Secretaries</option>
      <option value="AllMembers">All Members</option>
    </select>
  </div>
  <div class="ann-filter-field">
    <label for="annFilterPriority">Priority</label>
    <select id="annFilterPriority">
      <option value="">Any</option>
      <option value="Normal">Normal</option>
      <option value="Urgent">Urgent</option>
    </select>
  </div>
  <button type="button" class="ann-btn ann-btn-secondary" id="annClearFilterBtn">Clear Filter</button>
</div>



<div id="annResults">
<?php if (empty($announcements)): ?>
  <div class="ann-empty">
    <p class="ann-section-subtitle">No announcements found in this division.</p>
    <?php if (!empty($canCreate)): ?>
      <button type="button" class="ann-btn ann-btn-primary" onclick="document.getElementById('annOpenCreateBtn').click()">+ New Announcement</button>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="ann-grid" id="annGrid">
    <?php foreach ($announcements as $a):
      $priorityClass = $a->status === 'Draft' ? 'status-draft' : ($a->priority === 'Urgent' ? 'priority-urgent' : 'priority-normal');

      $targetLabels = [
        'AllDivisionalClubs'       => 'All Divisional Clubs',
        'ClubPresidentsSecretaries' => 'Club Presidents, Secretaries',
        'AllMembers'               => 'All Members',
      ];
      $targetLabel = $targetLabels[$a->target_audience ?? ''] ?? 'Pending';
    ?>
    <div class="ann-card <?= $priorityClass ?>"
         data-status="<?= htmlspecialchars($a->status) ?>"
         data-audience="<?= htmlspecialchars($a->target_audience ?? '') ?>"
         data-priority="<?= htmlspecialchars($a->priority) ?>"
         data-title="<?= htmlspecialchars($a->title) ?>"
         data-body="<?= htmlspecialchars($a->body) ?>">
      <div class="ann-card-top">
        <span class="ann-badge ann-badge-divisional">Divisional</span>
        <div class="ann-badges-group"><span class="ann-badge <?= $a->status === 'Draft' ? 'ann-badge-draft' : 'ann-badge-published' ?>"><?= htmlspecialchars($a->status) ?></span>
        <?php if ($a->priority === 'Urgent'): ?><span class="ann-badge ann-badge-urgent">Urgent</span><?php endif; ?></div>
      </div>
      <h4 class="ann-card-title"><?= htmlspecialchars($a->title) ?></h4>
      <p class="ann-card-preview"><?= htmlspecialchars(mb_strimwidth($a->body, 0, 150, '…')) ?></p>
      <div class="ann-card-target">Target: <?= htmlspecialchars($targetLabel) ?></div>
      <div class="ann-card-meta">Created <?= $annDate($a->created_at) ?></div>
      <?php if ($a->status === 'Published'): ?><div class="ann-card-meta">Published <?= $annDate($a->published_at ?? $a->created_at) ?></div><?php endif; ?>
      <?php if (!empty($a->content_edited_at)): ?><div class="ann-edited-note"><?= $annIcon('edit') ?> <span>Edited <?= $annDate($a->content_edited_at) ?></span></div><?php endif; ?>
      <div class="ann-card-bottom">
        <span>
          <?php if (!empty($a->attachment_count) && (int)$a->attachment_count > 0): ?>
            <?= (int)$a->attachment_count ?> file<?= (int)$a->attachment_count > 1 ? 's' : '' ?> attached
          <?php else: ?>
            No attachments
          <?php endif; ?>
        </span>
        <?php if (!empty($canCreate) && (int)$a->created_by === (int)$_SESSION['user_id']): ?>
          <button type="button" data-ann-edit="<?= (int)$a->announcement_id ?>" class="ann-card-edit-btn"><?= $annIcon('edit') ?> <?= $a->status === 'Draft' ? 'Edit Draft' : 'Edit' ?></button>
        <?php endif; ?>
          <a href="<?= ROOT ?>/announcements/view/<?= (int)$a->announcement_id ?>" class="ann-card-link">View Details <?= $annIcon('arrow') ?></a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<p class="ann-empty" id="annNoMatches" role="status" hidden>No announcements match these filters.</p>
</div>

<!-- Create / Edit Announcement Modal -->
<?php if (!empty($canCreate)): ?>
<div class="ann-modal-backdrop" id="annCreateModal">
  <div class="ann-modal-card" role="dialog" aria-modal="true" aria-labelledby="annModalTitle">
    <div class="ann-modal-header">
      <h2 id="annModalTitle">Create New Announcement</h2>
      <button type="button" class="ann-modal-close" aria-label="Close announcement form" onclick="closeCreateModal()"><?= $annIcon('close') ?></button>
    </div>
    <form id="annCreateForm" enctype="multipart/form-data">
      <div class="ann-modal-body">
      <input type="hidden" id="annExpectedStatus" name="expected_status" value="Draft">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="ann-field">
        <label for="annTitle">Title</label>
        <input type="text" id="annTitle" name="title" maxlength="150" placeholder="e.g. Divisional Leadership Summit — Confirmation Required" required>
      </div>
      <div class="ann-field">
        <label for="annBody">Body</label>
        <textarea id="annBody" name="body" placeholder="Write the announcement body..." required></textarea>
      </div>
      <div class="ann-field">
        <label for="annAudience">Target Audience</label>
        <select id="annAudience" name="target_audience" required>
          <option value="">Select an audience</option>
          <option value="AllDivisionalClubs">All Divisional Clubs</option>
          <option value="ClubPresidentsSecretaries">Club Presidents &amp; Secretaries</option>
          <option value="AllMembers">All Members</option>
        </select>
      </div>
      <div class="ann-field">
        <label for="annCategory">Category (optional)</label>
        <input type="text" id="annCategory" name="category" maxlength="100">
      </div>
      <div class="ann-field">
        <label for="annFileInput">Attachments</label>
        <ul class="ann-attach-list" id="annExistingAttachments"></ul>
        <button type="button" class="ann-dropzone" id="annDropzone" onclick="document.getElementById('annFileInput').click()">
          <?= $annIcon('file') ?> Click to browse, or drag files here
        </button>
        <input type="file" id="annFileInput" name="attachments[]" multiple hidden accept=".pdf,.png,.jpg,.jpeg,.doc,.docx">
        <p class="ann-field-help">PDF, PNG, JPEG, DOC or DOCX · Maximum 10 MB per file. Remove existing files or add replacements; changes take effect only when you save.</p>
        <ul class="ann-attach-list" id="annAttachList"></ul>
      </div>
      <div class="ann-field">
        <label id="annPriorityLabel">Priority</label>
        <div class="ann-priority-toggle" role="group" aria-labelledby="annPriorityLabel">
          <button type="button" class="active" data-p="Normal" onclick="setPriority('Normal')">Normal</button>
          <button type="button" data-p="Urgent" onclick="setPriority('Urgent')">Urgent</button>
        </div>
        <input type="hidden" id="annPriorityInput" name="priority" value="Normal">
      </div>
      <div class="ann-warning-note">
        You can edit an announcement after publishing. Changes display an Edited date alongside its original creation and publication dates.
      </div>
      </div>
      <div class="ann-modal-footer">
        <button type="button" class="ann-btn ann-btn-secondary" id="annSaveDraftBtn" onclick="saveDraft()">Save Draft</button>
        <button type="button" class="ann-btn ann-btn-secondary" onclick="closeCreateModal()">Cancel</button>
        <button type="submit" id="annSubmitBtn" class="ann-btn ann-btn-primary">Publish Announcement</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="ann-toast" id="annToast" role="status" aria-live="polite"
     data-root="<?= htmlspecialchars(rtrim(ROOT, '/'), ENT_QUOTES, 'UTF-8') ?>"
     data-csrf="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>"></div>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/announcements.css">
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/divisional-components.css?v=<?= time() ?>">
<script src="<?= ROOT ?>/assets/js/announcements.js?v=<?= time() ?>"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
