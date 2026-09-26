<?php
/**
 * YouthNexus Announcements — List Page
 *
 * Supports:
 * - Club
 * - Divisional
 * - Zonal
 * - NYSC
 *
 * Managers can target:
 * - All users of an allowed role
 * - Selected users of an allowed role
 */

$title           = $title ?? 'Announcements — YouthNexus';
$pageTitle       = 'Announcements';
$pageDescription = 'View communications relevant to your role and organisational scope';
$currentRoute    = 'announcements';
$pageStyles      = [
    ROOT . '/assets/css/announcements.css?v=20260924',
    ROOT . '/assets/css/divisional-summary-standard.css?v=20260924',
];
$pageScripts     = [ROOT . '/assets/js/announcements.js?v=20260924'];

require __DIR__ . '/../layouts/dashboard-start.view.php';
require __DIR__ . '/helpers.php';


$canManageAnnouncements =
    !empty($canManage);


/**
 * Convert role names such as:
 *
 * ClubSecretary
 * DivisionalCoordinator
 * NYSCAdministrator
 *
 * into readable labels.
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
?>


<?php if ($canManageAnnouncements): ?>

    <div class="ann-header-row">

        <button
            type="button"
            class="ann-btn ann-btn-primary db-primary-action"
            id="annOpenCreateBtn"
        >
            New Announcement
        </button>

    </div>

<?php endif; ?>


<!-- ========================================================= -->
<!-- Summary cards                                             -->
<!-- ========================================================= -->

<div class="ann-stats">

    <?php
    $statCards = [
        'All'       => 'Total Announcements',
        'Published' => 'Published',
        'Draft'     => 'Drafts',
    ];
    ?>


    <?php foreach ($statCards as $key => $label): ?>

        <?php
        if (
            $key === 'Draft'
            && !$canManageAnnouncements
        ) {
            continue;
        }

        $statusValue =
            $key === 'All'
                ? ''
                : $key;
        ?>


        <button
            type="button"
            class="ann-stat-card <?= $key === 'All' ? 'is-active' : '' ?>"
            data-ann-status="<?= htmlspecialchars(
                $statusValue,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            aria-pressed="<?= $key === 'All' ? 'true' : 'false' ?>"
            aria-controls="annResults"
        >

            <span class="ann-stat-icon <?= strtolower($key) ?>">

                <?= $annIcon(
                    $key === 'Published'
                        ? 'check'
                        : (
                            $key === 'Draft'
                                ? 'edit'
                                : 'broadcast'
                        )
                ) ?>

            </span>


            <span>

                <span class="ann-stat-number">
                    <?= (int)($counts[$key] ?? 0) ?>
                </span>

                <span class="ann-stat-label">
                    <?= htmlspecialchars(
                        $label,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

            </span>

        </button>

    <?php endforeach; ?>

</div>


<!-- ========================================================= -->
<!-- Search / filters                                          -->
<!-- ========================================================= -->

<div class="ann-toolbar">

    <div class="ann-search-wrap">

        <label
            class="visually-hidden"
            for="annSearchInput"
        >
            Search announcements
        </label>

        <input
            type="search"
            id="annSearchInput"
            placeholder="Search announcements..."
        >

        <?= $annIcon('search') ?>

    </div>


    <div class="ann-filter-actions">
        <button
            type="button"
            class="ann-btn ann-btn-secondary"
            id="annFilterBtn"
            aria-expanded="false"
            aria-controls="annFilterPanel"
        >
            <?= $annIcon('filter') ?>
            Filters
        </button>

    </div>

</div>


<div
    class="ann-filter-panel"
    id="annFilterPanel"
>

    <div class="ann-filter-field">

        <label for="annFilterStatus">
            Status
        </label>

        <select id="annFilterStatus">

            <option value="">
                All Statuses
            </option>

            <option value="Published">
                Published
            </option>

            <?php if ($canManageAnnouncements): ?>

                <option value="Draft">
                    Draft
                </option>

            <?php endif; ?>

        </select>

    </div>


    <?php if (
        $canManageAnnouncements
        && !empty($availableTargetRoles)
    ): ?>

        <div class="ann-filter-field">

            <label for="annFilterRole">
                Target Role
            </label>

            <select id="annFilterRole">

                <option value="">
                    All Roles
                </option>


                <?php foreach (
                    $availableTargetRoles
                    as $role
                ): ?>

                    <option
                        value="<?= htmlspecialchars(
                            $role,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        <?= htmlspecialchars(
                            $roleLabel($role),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

    <?php endif; ?>


    <div class="ann-filter-field">

        <label for="annFilterPriority">
            Priority
        </label>

        <select id="annFilterPriority">

            <option value="">
                Any
            </option>

            <option value="Normal">
                Normal
            </option>

            <option value="Urgent">
                Urgent
            </option>

        </select>

    </div>


    <button
        type="button"
        class="ann-btn ann-btn-secondary"
        id="annClearFilterBtn"
    >
        Clear Filter
    </button>

</div>


<!-- ========================================================= -->
<!-- Announcement results                                      -->
<!-- ========================================================= -->

<div id="annResults">

<?php if (empty($announcements)): ?>

    <div class="ann-empty">

        <p class="ann-section-subtitle">
            No announcements are currently available for your role and scope.
        </p>


        <?php if ($canManageAnnouncements): ?>

            <button
                type="button"
                class="ann-btn ann-btn-primary db-primary-action"
                onclick="document.getElementById('annOpenCreateBtn')?.click()"
            >
                New Announcement
            </button>

        <?php endif; ?>

    </div>


<?php else: ?>

    <div
        class="ann-grid"
        id="annGrid"
    >

        <?php foreach ($announcements as $a): ?>

            <?php
            $priorityClass =
                $a->status === 'Draft'
                    ? 'status-draft'
                    : (
                        $a->priority === 'Urgent'
                            ? 'priority-urgent'
                            : 'priority-normal'
                    );


            /*
             * target_roles_csv comes from AnnouncementModel.
             *
             * Example:
             * ClubMember,ClubPresident,ClubSecretary
             */
            $targetRoles = [];


            if (!empty($a->target_roles_csv)) {
                $targetRoles =
                    array_values(
                        array_filter(
                            array_map(
                                'trim',
                                explode(
                                    ',',
                                    $a->target_roles_csv
                                )
                            )
                        )
                    );
            }


            $targetRoleLabels =
                array_map(
                    $roleLabel,
                    $targetRoles
                );


            $targetLabel =
                !empty($targetRoleLabels)
                    ? implode(
                        ', ',
                        $targetRoleLabels
                    )
                    : 'No recipients selected';


            /*
             * Used only for browser-side filtering.
             */
            $targetRolesData =
                implode(
                    '|',
                    $targetRoles
                );
            ?>


            <div
                class="ann-card <?= htmlspecialchars(
                    $priorityClass,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                data-status="<?= htmlspecialchars(
                    $a->status,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                data-target-roles="<?= htmlspecialchars(
                    $targetRolesData,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                data-priority="<?= htmlspecialchars(
                    $a->priority,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                data-title="<?= htmlspecialchars(
                    $a->title,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                data-body="<?= htmlspecialchars(
                    $a->body,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

                <div class="ann-card-top">

                    <span class="ann-badge ann-badge-divisional">

                        <?= htmlspecialchars(
                            $a->level,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>


                    <div class="ann-badges-group">

                        <span
                            class="ann-badge <?= $a->status === 'Draft'
                                ? 'ann-badge-draft'
                                : 'ann-badge-published' ?>"
                        >
                            <?= htmlspecialchars(
                                $a->status,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>


                        <?php if (
                            $a->priority === 'Urgent'
                        ): ?>

                            <span class="ann-badge ann-badge-urgent">
                                Urgent
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <h4 class="ann-card-title">

                    <?= htmlspecialchars(
                        $a->title,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </h4>


                <p class="ann-card-preview">

                    <?= htmlspecialchars(
                        mb_strimwidth(
                            $a->body,
                            0,
                            150,
                            '…'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </p>


                <div class="ann-card-target">

                    Target roles:

                    <?= htmlspecialchars(
                        $targetLabel,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>


                <div class="ann-card-meta">

                    Created

                    <?= $annDate(
                        $a->created_at
                    ) ?>

                </div>


                <?php if (
                    $a->status === 'Published'
                ): ?>

                    <div class="ann-card-meta">

                        Published

                        <?= $annDate(
                            $a->published_at
                            ?? $a->created_at
                        ) ?>

                    </div>

                <?php endif; ?>


                <?php if (
                    !empty(
                        $a->content_edited_at
                    )
                ): ?>

                    <div class="ann-edited-note">

                        <?= $annIcon('edit') ?>

                        <span>

                            Edited

                            <?= $annDate(
                                $a->content_edited_at
                            ) ?>

                        </span>

                    </div>

                <?php endif; ?>


                <div class="ann-card-bottom">

                    <span>

                        <?php if (
                            !empty(
                                $a->attachment_count
                            )
                            &&
                            (int)$a->attachment_count > 0
                        ): ?>

                            <?= (int)$a->attachment_count ?>

                            file<?= (int)$a->attachment_count > 1
                                ? 's'
                                : '' ?>
                            attached

                        <?php else: ?>

                            No attachments

                        <?php endif; ?>

                    </span>


                    <?php if (
                        !empty($a->can_manage)
                    ): ?>

                        <button
                            type="button"
                            data-ann-edit="<?= (int)$a->announcement_id ?>"
                            class="ann-card-edit-btn db-secondary-action"
                        >

                            <?= $annIcon('edit') ?>

                            <?= $a->status === 'Draft'
                                ? 'Edit Draft'
                                : 'Edit' ?>

                        </button>

                    <?php endif; ?>


                    <a
                        href="<?= ROOT ?>/announcements/view/<?= (int)$a->announcement_id ?>"
                        class="ann-card-link db-view-button"
                    >

                        View Details

                    </a>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<p
    class="ann-empty"
    id="annNoMatches"
    role="status"
    hidden
>
    No announcements match these filters.
</p>

</div>


<!-- ========================================================= -->
<!-- Create / Edit modal                                       -->
<!-- ========================================================= -->

<?php if ($canManageAnnouncements): ?>

<div
    class="ann-modal-backdrop"
    id="annCreateModal"
>

    <div
        class="ann-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="annModalTitle"
    >

        <div class="ann-modal-header">

            <h2 id="annModalTitle">
                Create New Announcement
            </h2>


            <button
                type="button"
                class="ann-modal-close"
                aria-label="Close announcement form"
                onclick="closeCreateModal()"
            >
                <?= $annIcon('close') ?>
            </button>

        </div>


        <form
            id="annCreateForm"
            enctype="multipart/form-data"
        >

            <div class="ann-modal-body">


                <input
                    type="hidden"
                    id="annExpectedStatus"
                    name="expected_status"
                    value="Draft"
                >


                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrf_token,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >


                <!-- Title -->

                <div class="ann-field">

                    <label for="annTitle">
                        Title
                    </label>


                    <input
                        type="text"
                        id="annTitle"
                        name="title"
                        maxlength="150"
                        placeholder="e.g. Youth leadership programme update"
                        required
                    >

                </div>


                <!-- Body -->

                <div class="ann-field">

                    <label for="annBody">
                        Body
                    </label>


                    <textarea
                        id="annBody"
                        name="body"
                        placeholder="Write the announcement body..."
                        required
                    ></textarea>

                </div>


                <!-- ================================================= -->
                <!-- Target recipients                                 -->
                <!-- ================================================= -->

    <div class="ann-field">

    <label>
        Target Recipients
    </label>

    <p class="ann-field-help">
        Choose one or more actor types.
        For each actor type, send the announcement to
        all eligible users or only selected users.
        Drafts may be saved without choosing recipients.
    </p>

    <div class="ann-target-role-grid">

        <?php foreach (
            $availableTargetRoles
            as $role
        ): ?>

            <section
                class="ann-target-role-card"
                data-target-role-card
                data-role="<?= htmlspecialchars(
                    $role,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

                <label class="ann-target-role-header">

                    <input
                        type="checkbox"
                        class="ann-target-role-enable"
                    >

                    <span>
                        <?= htmlspecialchars(
                            $roleLabel($role),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                </label>


                <div
                    class="ann-target-role-settings"
                    hidden
                >

                    <label class="ann-target-mode">

                        <input
                            type="radio"
                            name="target_modes[<?= htmlspecialchars(
                                $role,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>]"
                            value="All"
                            checked
                            disabled
                        >

                        <span>
                            All eligible
                            <?= htmlspecialchars(
                                $roleLabel($role),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                            users in this scope
                        </span>

                    </label>


                    <label class="ann-target-mode">

                        <input
                            type="radio"
                            name="target_modes[<?= htmlspecialchars(
                                $role,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>]"
                            value="Selected"
                            disabled
                        >

                        <span>
                            Select specific users
                        </span>

                    </label>


                    <div
                        class="ann-recipient-picker"
                        data-recipient-picker
                        hidden
                    >

                        <input
                            type="search"
                            class="ann-recipient-search"
                            data-recipient-search
                            placeholder="Search people..."
                            autocomplete="off"
                        >

                        <div
                            class="ann-recipient-list"
                            data-recipient-list
                        >

                            <p class="ann-field-help">
                                Select "specific users" to load eligible recipients.
                            </p>

                        </div>

                    </div>

                </div>

            </section>

        <?php endforeach; ?>

    </div>

</div>


                <!-- Category -->

                <div class="ann-field">

                    <label for="annCategory">
                        Category (optional)
                    </label>


                    <input
                        type="text"
                        id="annCategory"
                        name="category"
                        maxlength="100"
                    >

                </div>


                <!-- Attachments -->

                <div class="ann-field">

                    <label for="annFileInput">
                        Attachments
                    </label>


                    <ul
                        class="ann-attach-list"
                        id="annExistingAttachments"
                    ></ul>


                    <button
                        type="button"
                        class="ann-dropzone"
                        id="annDropzone"
                        onclick="document.getElementById('annFileInput').click()"
                    >

                        <?= $annIcon('file') ?>

                        Click to browse, or drag files here

                    </button>


                    <input
                        type="file"
                        id="annFileInput"
                        name="attachments[]"
                        multiple
                        hidden
                        accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                    >


                    <p class="ann-field-help">
                        PDF, PNG, JPEG, DOC or DOCX.
                        Maximum 10 MB per file.
                        Attachment changes take effect only when you save.
                    </p>


                    <ul
                        class="ann-attach-list"
                        id="annAttachList"
                    ></ul>

                </div>


                <!-- Priority -->

                <div class="ann-field">

                    <label id="annPriorityLabel">
                        Priority
                    </label>


                    <div
                        class="ann-priority-toggle"
                        role="group"
                        aria-labelledby="annPriorityLabel"
                    >

                        <button
                            type="button"
                            class="active"
                            data-p="Normal"
                            onclick="setPriority('Normal')"
                        >
                            Normal
                        </button>


                        <button
                            type="button"
                            data-p="Urgent"
                            onclick="setPriority('Urgent')"
                        >
                            Urgent
                        </button>

                    </div>


                    <input
                        type="hidden"
                        id="annPriorityInput"
                        name="priority"
                        value="Normal"
                    >

                </div>


                <div class="ann-warning-note">

                    Published announcements may be edited later.
                    Changes display an Edited date while keeping the
                    original creation and publication dates.

                </div>

            </div>


            <!-- Footer -->

            <div class="ann-modal-footer">

                <button
                    type="button"
                    class="ann-btn ann-btn-secondary"
                    id="annSaveDraftBtn"
                    onclick="saveDraft()"
                >
                    Save Draft
                </button>


                <button
                    type="button"
                    class="ann-btn ann-btn-secondary db-close-action"
                    onclick="closeCreateModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    id="annSubmitBtn"
                    class="ann-btn ann-btn-primary"
                >
                    Publish Announcement
                </button>

            </div>

        </form>

    </div>

</div>

<?php endif; ?>


<!-- ========================================================= -->
<!-- Toast                                                     -->
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
