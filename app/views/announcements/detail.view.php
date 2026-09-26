<?php
/**
 * YouthNexus Announcements — Detail Page
 *
 * Supports:
 * - Club
 * - Divisional
 * - Zonal
 * - NYSC
 *
 * Audience targeting:
 * - All eligible users of a role
 * - Selected specific users of a role
 */

$title           = $title ?? 'Announcement Details — YouthNexus';
$pageTitle       = 'Announcement Details';
$pageDescription = 'View announcement information, recipients and attachments';
$currentRoute    = 'announcements';
$pageStyles      = [
    ROOT . '/assets/css/announcements.css',
    ROOT . '/assets/css/divisional-summary-standard.css',
];
$pageScripts     = [ROOT . '/assets/js/announcements.js'];

require __DIR__ . '/../layouts/dashboard-start.view.php';
require __DIR__ . '/helpers.php';


/**
 * Convert system role names into readable labels.
 */
$roleLabel = function ($role) {
    if ($role === 'NYSCAdministrator') {
        return 'NYSC Administrator';
    }

    return trim(
        preg_replace(
            '/(?<!^)([A-Z])/',
            ' $1',
            (string)$role
        )
    );
};

$statusLabel = static function ($status) {
    return $status === 'Retracted' ? 'Withdrawn from Publication' : (string) $status;
};


/**
 * New detailed audience structure supplied by controller:
 *
 * [
 *     [
 *         'target_role' => 'ClubSecretary',
 *         'selection_mode' => 'All',
 *         'users' => []
 *     ],
 *     [
 *         'target_role' => 'ClubMember',
 *         'selection_mode' => 'Selected',
 *         'users' => [...]
 *     ]
 * ]
 */
$audienceTargets =
    $audienceTargets ?? [];


/**
 * Human-readable organisational scope.
 */
$scopeLabel = 'National';


switch ($announcement->level ?? '') {

    case 'Club':

        $scopeLabel =
            $announcement->organizer_club_name
            ?? 'Club';

        break;


    case 'Divisional':

        $scopeLabel =
            $announcement->organizer_division_name
            ?? 'Division';

        break;


    case 'Zonal':

        $scopeLabel =
            $announcement->organizer_zonal_name
            ?? 'Zone';

        break;


    case 'NYSC':

        $scopeLabel =
            'National';

        break;
}
?>


<a
    href="<?= ROOT ?>/announcements"
    class="ann-card-link ann-back-link"
>
    <?= $annIcon('arrow') ?>
    Back to Announcements
</a>


<div class="ann-detail-grid">

    <!-- ====================================================== -->
    <!-- Main announcement content                              -->
    <!-- ====================================================== -->

    <div class="ann-detail-main">

        <div class="ann-detail-badges">

            <span class="ann-badge-divisional">

                <?= htmlspecialchars(
                    $announcement->level ?? 'Announcement',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </span>


            <?php if (
                !empty($isRecipient)
                && empty($hasRead)
                && $announcement->status === 'Published'
            ): ?>

                <span
                    class="ann-badge-new"
                    id="annNewBadge"
                >
                    New
                </span>

            <?php endif; ?>


            <?php if ($announcement->status === 'Draft'): ?>

                <span class="ann-badge ann-badge-draft">
                    DRAFT
                </span>

            <?php elseif ($announcement->status === 'Published'): ?>

                <span class="ann-badge ann-badge-published">
                    PUBLISHED
                </span>

            <?php else: ?>

                <span class="ann-badge ann-badge-draft">
                    <?= htmlspecialchars(strtoupper($statusLabel($announcement->status)), ENT_QUOTES, 'UTF-8') ?>
                </span>

            <?php endif; ?>


            <?php if (
                $announcement->priority === 'Urgent'
            ): ?>

                <span class="ann-badge ann-badge-urgent">
                    URGENT
                </span>

            <?php endif; ?>

        </div>


        <h2 class="ann-detail-title">

            <?= htmlspecialchars(
                $announcement->title,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </h2>


        <!-- Metadata -->

        <div class="ann-detail-meta">

            <div>

                Created

                <?= $annDate(
                    $announcement->created_at
                ) ?>

            </div>


            <?php if (
                $announcement->status === 'Published'
            ): ?>

                <div>

                    Published

                    <?= $annDate(
                        $announcement->published_at
                        ?? $announcement->created_at
                    ) ?>

                </div>

            <?php endif; ?>


            <?php if (
                !empty(
                    $announcement->content_edited_at
                )
            ): ?>

                <div class="ann-edited-note">

                    <?= $annIcon('edit') ?>

                    <span>

                        Edited

                        <?= $annDate(
                            $announcement->content_edited_at
                        ) ?>

                    </span>

                </div>

            <?php endif; ?>


            <div>

                By

                <?= htmlspecialchars(
                    $announcement->posted_by_name
                    ?? 'YouthNexus User',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

                &middot;

                <?= htmlspecialchars(
                    $roleLabel(
                        $announcement->posted_by_role
                        ?? ''
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>


            <div>

                <?= (int)$announcement->view_count ?>

                View<?= (int)$announcement->view_count === 1
                    ? ''
                    : 's' ?>

            </div>

        </div>


        <!-- Announcement body -->

        <div class="ann-detail-body">

            <?= nl2br(
                htmlspecialchars(
                    $announcement->body,
                    ENT_QUOTES,
                    'UTF-8'
                )
            ) ?>

        </div>


        <!-- ================================================== -->
        <!-- Attachments                                        -->
        <!-- ================================================== -->

        <?php if (!empty($attachments)): ?>

            <div class="ann-attachments-box">

                <b>

                    Attachments
                    (<?= count($attachments) ?>)

                </b>


                <?php foreach (
                    $attachments
                    as $att
                ): ?>

                    <div class="ann-attachment-row">

                        <span>

                            <?= $annIcon('file') ?>

                            <?= htmlspecialchars(
                                $att->file_name,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                            &middot;

                            <?= $annFormatFileSize(
                                $att->file_size
                            ) ?>

                        </span>


                        <a
                            href="<?= ROOT ?>/announcements/download/<?= (int)$att->attachment_id ?>"
                            aria-label="Download <?= htmlspecialchars(
                                $att->file_name,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                            <?= $annIcon('download') ?>

                            Download

                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>


    <!-- ====================================================== -->
    <!-- Right sidebar                                          -->
    <!-- ====================================================== -->

    <div>

        <!-- ================================================== -->
        <!-- Actions                                            -->
        <!-- ================================================== -->

        <div class="ann-side-card">

            <?php if (!empty($canManage)): ?>

                <?php if (in_array($announcement->status, ['Draft', 'Published'], true)): ?>
                <a
                    class="ann-btn ann-btn-secondary db-secondary-action"
                    href="<?= ROOT ?>/announcements?edit=<?= (int)$announcement->announcement_id ?>"
                >

                    <?= $annIcon('edit') ?>

                    <?= $announcement->status === 'Draft'
                        ? 'Edit Draft'
                        : 'Edit Announcement' ?>

                </a>
                <?php endif; ?>

                <?php if ($announcement->status === 'Draft'): ?>
                <button
                    type="button"
                    class="ann-btn ann-btn-danger"
                    onclick="deleteAnnouncement(<?= (int)$announcement->announcement_id ?>)"
                >
                    Delete Draft
                </button>
                <?php elseif ($announcement->status === 'Published'): ?>
                    <button type="button" class="ann-btn ann-btn-danger" data-ann-lifecycle-action="retract" data-announcement-id="<?= (int)$announcement->announcement_id ?>">Withdraw from Publication</button>
                    <button type="button" class="ann-btn ann-btn-secondary" data-ann-lifecycle-action="archive" data-announcement-id="<?= (int)$announcement->announcement_id ?>">Archive Announcement</button>
                <?php elseif ($announcement->status === 'Retracted'): ?>
                    <button type="button" class="ann-btn ann-btn-secondary" data-ann-lifecycle-action="archive" data-announcement-id="<?= (int)$announcement->announcement_id ?>">Archive Announcement</button>
                <?php elseif ($announcement->status === 'Archived'): ?>
                    <button type="button" class="ann-btn ann-btn-primary" data-ann-lifecycle-action="restore" data-announcement-id="<?= (int)$announcement->announcement_id ?>">Restore Announcement</button>
                <?php endif; ?>

            <?php endif; ?>


            <?php if (
                $announcement->status === 'Published'
                && !empty($isRecipient)
                && !empty($hasRead)
            ): ?>

                <button
                    type="button"
                    class="ann-btn ann-btn-secondary"
                    disabled
                >

                    <?= $annIcon('check') ?>

                    Read

                </button>


            <?php elseif (
                $announcement->status === 'Published'
                && !empty($isRecipient)
            ): ?>

                <button
                    type="button"
                    class="ann-btn ann-btn-primary db-confirm-action"
                    id="annMarkReadBtn"
                    onclick="markAsRead(<?= (int)$announcement->announcement_id ?>)"
                >

                    <?= $annIcon('check') ?>

                    Mark as Read

                </button>

            <?php endif; ?>

        </div>


        <!-- ================================================== -->
        <!-- General announcement details                       -->
        <!-- ================================================== -->

        <div class="ann-side-card">

            <h3 class="ann-side-heading">
                Announcement Details
            </h3>


            <div class="ann-side-details">


                <!-- Level -->

                <div class="ann-detail-row">

                    <div class="lbl">
                        Level
                    </div>

                    <div class="val">

                        <?= htmlspecialchars(
                            $announcement->level
                            ?? '—',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                </div>


                <!-- Scope -->

                <div class="ann-detail-row">

                    <div class="lbl">
                        Scope
                    </div>

                    <div class="val">

                        <?= htmlspecialchars(
                            $scopeLabel,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                </div>


                <!-- Category -->

                <div class="ann-detail-row">

                    <div class="lbl">
                        Category
                    </div>

                    <div class="val">

                        <?= htmlspecialchars(
                            !empty($announcement->category)
                                ? $announcement->category
                                : '—',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                </div>


                <!-- Priority -->

                <div class="ann-detail-row">

                    <div class="lbl">
                        Priority
                    </div>

                    <div class="val">

                        <?= htmlspecialchars(
                            $announcement->priority
                            ?? 'Normal',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                </div>


                <!-- Status -->

                <div class="ann-detail-row">

                    <div class="lbl">
                        Status
                    </div>

                    <div class="val">

                        <?= htmlspecialchars(
                            $announcement->status
                            ?? '—',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                </div>


                <!-- Posted by -->

                <div class="ann-detail-row">

                    <div class="lbl">
                        Posted By
                    </div>

                    <div class="val">

                        <?= htmlspecialchars(
                            $announcement->posted_by_name
                            ?? 'YouthNexus User',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                        <br>

                        <small class="ann-field-help">

                            <?= htmlspecialchars(
                                $roleLabel(
                                    $announcement->posted_by_role
                                    ?? ''
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </small>

                    </div>

                </div>

            </div>

        </div>


        <!-- ================================================== -->
        <!-- Target recipients                                  -->
        <!-- ================================================== -->

        <div class="ann-side-card">

            <h3 class="ann-side-heading">
                Target Recipients
            </h3>


            <?php if (
                empty($audienceTargets)
            ): ?>

                <p class="ann-field-help">
                    No recipients have been selected yet.
                </p>


            <?php else: ?>


                <div class="ann-audience-list">


                    <?php foreach (
                        $audienceTargets
                        as $target
                    ): ?>


                        <?php
                        $targetRole =
                            $target['target_role']
                            ?? '';

                        $selectionMode =
                            $target['selection_mode']
                            ?? 'All';

                        $selectedUsers =
                            $target['users']
                            ?? [];
                        ?>


                        <div class="ann-audience-detail">


                            <div class="ann-audience-role">

                                <strong>

                                    <?= htmlspecialchars(
                                        $roleLabel(
                                            $targetRole
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </strong>


                                <?php if (
                                    $selectionMode === 'All'
                                ): ?>

                                    <span class="ann-audience-mode">
                                        All
                                    </span>

                                <?php else: ?>

                                    <span class="ann-audience-mode">
                                        Selected
                                    </span>

                                <?php endif; ?>

                            </div>


                            <?php if (
                                $selectionMode === 'All'
                            ): ?>

                                <div class="ann-field-help">

                                    All eligible

                                    <?= htmlspecialchars(
                                        $roleLabel(
                                            $targetRole
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    users in this announcement's scope.

                                </div>


                            <?php else: ?>

                                <div class="ann-field-help">

                                    <?= count(
                                        $selectedUsers
                                    ) ?>

                                    selected
                                    recipient<?= count($selectedUsers) === 1
                                        ? ''
                                        : 's' ?>

                                </div>


                                <?php if (
                                    !empty($canManage)
                                    && !empty($selectedUsers)
                                ): ?>

                                    <!--
                                        Only the announcement manager sees
                                        the full list of specifically selected
                                        people.

                                        Normal recipients do not need to see
                                        everyone else who was selected.
                                    -->

                                    <div class="ann-selected-recipient-list">


                                        <?php foreach (
                                            $selectedUsers
                                            as $recipient
                                        ): ?>


                                            <?php
                                            $recipientName =
                                                $recipient['name']
                                                ?? 'YouthNexus User';


                                            $recipientContext =
                                                $recipient['club_name']
                                                ?? $recipient['division_name']
                                                ?? $recipient['zonal_name']
                                                ?? null;
                                            ?>


                                            <div class="ann-selected-recipient">

                                                <span>

                                                    <?= htmlspecialchars(
                                                        $recipientName,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                </span>


                                                <?php if (
                                                    !empty(
                                                        $recipientContext
                                                    )
                                                ): ?>

                                                    <small>

                                                        <?= htmlspecialchars(
                                                            $recipientContext,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </small>

                                                <?php endif; ?>

                                            </div>


                                        <?php endforeach; ?>


                                    </div>

                                <?php endif; ?>


                                <?php if (
                                    empty($canManage)
                                    && !empty($isRecipient)
                                ): ?>

                                    <div class="ann-field-help">
                                        You are one of the selected recipients.
                                    </div>

                                <?php endif; ?>

                            <?php endif; ?>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>

        </div>

    </div>

</div>


<?php if (!empty($canManage) && in_array($announcement->status, ['Published', 'Retracted', 'Archived'], true)): ?>
<div class="ann-modal-backdrop" id="annLifecycleModal" aria-hidden="true">
    <div class="ann-modal-card" role="dialog" aria-modal="true" aria-labelledby="annLifecycleTitle">
        <div class="ann-modal-header"><h2 id="annLifecycleTitle">Update Announcement Lifecycle</h2><button type="button" class="ann-modal-close" data-ann-lifecycle-close aria-label="Close"><?= $annIcon('close') ?></button></div>
        <form id="annLifecycleForm">
            <div class="ann-modal-body"><div class="ann-field"><label for="annLifecycleReason">Reason</label><textarea id="annLifecycleReason" name="reason" minlength="5" maxlength="1000" required></textarea></div></div>
            <div class="ann-modal-footer"><button type="button" class="ann-btn ann-btn-secondary" data-ann-lifecycle-close>Cancel</button><button type="submit" class="ann-btn ann-btn-primary" id="annLifecycleSubmit">Save Change</button></div>
        </form>
    </div>
</div>
<?php endif; ?>


<!-- ========================================================= -->
<!-- Toast / JS data                                            -->
<!-- ========================================================= -->

<div
    class="ann-toast"
    id="annToast"
    role="status"
    aria-live="polite"
    data-root="<?= htmlspecialchars(
        rtrim(ROOT, '/'),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    data-csrf="<?= htmlspecialchars(
        $csrf_token,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
></div>


<?php
require __DIR__ . '/../layouts/dashboard-end.view.php';
?>
