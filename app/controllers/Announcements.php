<?php

class Announcements extends Controller
{
    private const MANAGER_LEVELS = [
        'ClubSecretary'       => 'Club',
        'DivisionalSecretary' => 'Divisional',
        'ZonalSecretary'      => 'Zonal',
        'NYSCAdministrator'   => 'NYSC',
    ];

    private const ALLOWED_TARGET_ROLES = [
        'ClubSecretary' => [
            'ClubPresident',
            'ClubSecretary',
            'ClubTreasurer',
            'ClubMember',
        ],

        'DivisionalSecretary' => [
            'DivisionalCoordinator',
            'DivisionalSecretary',
            'DivisionalTreasurer',
            'ClubPresident',
            'ClubSecretary',
            'ClubTreasurer',
            'ClubMember',
        ],

        'ZonalSecretary' => [
            'ZonalCoordinator',
            'ZonalSecretary',
            'ZonalTreasurer',
            'DivisionalCoordinator',
            'DivisionalSecretary',
            'DivisionalTreasurer',
            'ClubPresident',
            'ClubSecretary',
            'ClubTreasurer',
            'ClubMember',
        ],

        'NYSCAdministrator' => [
            'NYSCAdministrator',
            'ZonalCoordinator',
            'ZonalSecretary',
            'ZonalTreasurer',
            'DivisionalCoordinator',
            'DivisionalSecretary',
            'DivisionalTreasurer',
            'ClubPresident',
            'ClubSecretary',
            'ClubTreasurer',
            'ClubMember',
            'UnassignedUser',
        ],
    ];


    // ================================================================
    // Authentication
    // ================================================================

    private function requireAuthenticated()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] =
                bin2hex(random_bytes(32));
        }
    }


    private function currentUser()
    {
        $this->requireAuthenticated();

        $user =
            $this->model('UserModel')
                ->findByUserIdWithHierarchy(
                    (int)$_SESSION['user_id']
                );

        if (!$user) {
            $this->jsonResponse(
                403,
                [
                    'error' =>
                        'Your user account could not be loaded.',
                ]
            );
        }

        return $user;
    }


    // ================================================================
    // Manager scope
    // ================================================================

    private function managerScope(
        $user,
        $strict = false
    ) {
        $role =
            $user->role ?? '';

        if (
            !isset(
                self::MANAGER_LEVELS[$role]
            )
        ) {
            return null;
        }

        $level =
            self::MANAGER_LEVELS[$role];


        switch ($level) {
            case 'Club':

                if (empty($user->club_id)) {
                    if ($strict) {
                        $this->jsonResponse(
                            403,
                            [
                                'error' =>
                                    'Your account has no club assigned.',
                            ]
                        );
                    }

                    return null;
                }

                return [
                    'level' =>
                        'Club',

                    'scope_id' =>
                        (int)$user->club_id,

                    'organizer_club_id' =>
                        (int)$user->club_id,

                    'organizer_division_id' =>
                        null,

                    'organizer_zonal_id' =>
                        null,
                ];


            case 'Divisional':

                if (
                    empty(
                        $user->effective_division_id
                    )
                ) {
                    if ($strict) {
                        $this->jsonResponse(
                            403,
                            [
                                'error' =>
                                    'Your account has no division assigned.',
                            ]
                        );
                    }

                    return null;
                }

                return [
                    'level' =>
                        'Divisional',

                    'scope_id' =>
                        (int)$user
                            ->effective_division_id,

                    'organizer_club_id' =>
                        null,

                    'organizer_division_id' =>
                        (int)$user
                            ->effective_division_id,

                    'organizer_zonal_id' =>
                        null,
                ];


            case 'Zonal':

                if (
                    empty(
                        $user->effective_zonal_id
                    )
                ) {
                    if ($strict) {
                        $this->jsonResponse(
                            403,
                            [
                                'error' =>
                                    'Your account has no zone assigned.',
                            ]
                        );
                    }

                    return null;
                }

                return [
                    'level' =>
                        'Zonal',

                    'scope_id' =>
                        (int)$user
                            ->effective_zonal_id,

                    'organizer_club_id' =>
                        null,

                    'organizer_division_id' =>
                        null,

                    'organizer_zonal_id' =>
                        (int)$user
                            ->effective_zonal_id,
                ];


            case 'NYSC':

                return [
                    'level' =>
                        'NYSC',

                    'scope_id' =>
                        null,

                    'organizer_club_id' =>
                        null,

                    'organizer_division_id' =>
                        null,

                    'organizer_zonal_id' =>
                        null,
                ];
        }

        return null;
    }


    private function requireManagerScope(
        $user
    ) {
        $scope =
            $this->managerScope(
                $user,
                true
            );

        if (!$scope) {
            $this->jsonResponse(
                403,
                [
                    'error' =>
                        'Only an authorized Secretary or NYSC Administrator can manage announcements.',
                ]
            );
        }

        return $scope;
    }


    // ================================================================
    // Authorization
    // ================================================================

    private function canManageAnnouncement(
        $user,
        $announcement
    ) {
        $scope =
            $this->managerScope($user);

        if (!$scope) {
            return false;
        }

        if (
            ($announcement->level ?? '')
            !== $scope['level']
        ) {
            return false;
        }


        switch ($scope['level']) {
            case 'Club':

                return
                    !empty(
                        $announcement
                            ->organizer_club_id
                    )
                    &&
                    (int)$announcement
                        ->organizer_club_id
                    ===
                    (int)$scope['scope_id'];


            case 'Divisional':

                return
                    !empty(
                        $announcement
                            ->organizer_division_id
                    )
                    &&
                    (int)$announcement
                        ->organizer_division_id
                    ===
                    (int)$scope['scope_id'];


            case 'Zonal':

                return
                    !empty(
                        $announcement
                            ->organizer_zonal_id
                    )
                    &&
                    (int)$announcement
                        ->organizer_zonal_id
                    ===
                    (int)$scope['scope_id'];


            case 'NYSC':

                return true;
        }

        return false;
    }


    private function isInAnnouncementScope(
        $user,
        $announcement
    ) {
        switch (
            $announcement->level ?? ''
        ) {
            case 'Club':

                return
                    !empty($user->club_id)
                    &&
                    !empty(
                        $announcement
                            ->organizer_club_id
                    )
                    &&
                    (int)$user->club_id
                    ===
                    (int)$announcement
                        ->organizer_club_id;


            case 'Divisional':

                return
                    !empty(
                        $user
                            ->effective_division_id
                    )
                    &&
                    !empty(
                        $announcement
                            ->organizer_division_id
                    )
                    &&
                    (int)$user
                        ->effective_division_id
                    ===
                    (int)$announcement
                        ->organizer_division_id;


            case 'Zonal':

                return
                    !empty(
                        $user
                            ->effective_zonal_id
                    )
                    &&
                    !empty(
                        $announcement
                            ->organizer_zonal_id
                    )
                    &&
                    (int)$user
                        ->effective_zonal_id
                    ===
                    (int)$announcement
                        ->organizer_zonal_id;


            case 'NYSC':

                return true;
        }

        return false;
    }


    /**
     * Published recipient rule:
     *
     * 1. Announcement is Published.
     * 2. User is inside the organisational scope.
     * 3. Their role is targeted.
     * 4. Audience mode is either:
     *      All
     *    OR
     *      Selected + their user_id exists.
     */
    private function isPublishedRecipient(
        $user,
        $announcement
    ) {
        if (
            ($announcement->status ?? '')
            !== 'Published'
        ) {
            return false;
        }

        if (
            !$this->isInAnnouncementScope(
                $user,
                $announcement
            )
        ) {
            return false;
        }

        return
            $this->model(
                'AnnouncementAudienceModel'
            )
            ->isUserTargeted(
                (int)$announcement
                    ->announcement_id,

                $user->role,

                (int)$user->user_id
            );
    }


    private function visibleAnnouncement(
        $id,
        $user = null
    ) {
        if (!$user) {
            $user =
                $this->currentUser();
        }

        $id =
            $this->positiveId($id);

        $announcement =
            $this->model(
                'AnnouncementModel'
            )->findById($id);


        if (!$announcement) {
            $this->jsonResponse(
                404,
                [
                    'error' =>
                        'Announcement not found.',
                ]
            );
        }


        /*
         * Managers may view Draft and Published
         * announcements belonging to their own scope.
         */
        if (
            $this->canManageAnnouncement(
                $user,
                $announcement
            )
        ) {
            return $announcement;
        }


        /*
         * Everyone else must be an intended
         * Published recipient.
         */
        if (
            !$this->isPublishedRecipient(
                $user,
                $announcement
            )
        ) {
            $this->jsonResponse(
                404,
                [
                    'error' =>
                        'Announcement not found.',
                ]
            );
        }

        return $announcement;
    }


    // ================================================================
    // General validation helpers
    // ================================================================

    private function requirePost()
    {
        if (
            $_SERVER['REQUEST_METHOD']
            !== 'POST'
        ) {
            header('Allow: POST');

            $this->jsonResponse(
                405,
                [
                    'error' =>
                        'This action requires POST.',
                ]
            );
        }


        $token =
            $_POST['csrf_token']
            ?? null;


        if (
            !is_string($token)
            ||
            $token === ''
            ||
            empty($_SESSION['csrf_token'])
            ||
            !hash_equals(
                $_SESSION['csrf_token'],
                $token
            )
        ) {
            $this->jsonResponse(
                403,
                [
                    'error' =>
                        'Invalid CSRF token. Please refresh the page.',
                ]
            );
        }
    }


    private function positiveId($id)
    {
        $value =
            filter_var(
                $id,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1,
                    ],
                ]
            );


        if ($value === false) {
            $this->jsonResponse(
                404,
                [
                    'error' =>
                        'Announcement or attachment not found.',
                ]
            );
        }

        return (int)$value;
    }


    private function formText($key)
    {
        if (
            isset($_POST[$key])
            &&
            !is_string($_POST[$key])
        ) {
            $this->jsonResponse(
                422,
                [
                    'error' =>
                        'Invalid form field: '
                        . $key,
                ]
            );
        }

        return trim(
            $_POST[$key] ?? ''
        );
    }


    // ================================================================
    // Audience / recipient validation
    // ================================================================

    /**
     * Convert audience data into a stable string representation
     * so old and new values can be compared regardless of ordering.
     */
    private function canonicalAudienceTargets(
        array $targets
    ) {
        $values = [];


        foreach ($targets as $target) {
            $ids =
                $target['user_ids']
                ?? [];


            $ids =
                array_values(
                    array_unique(
                        array_map(
                            'intval',
                            $ids
                        )
                    )
                );


            sort($ids);


            $values[] =
                (string)$target['target_role']
                . '|'
                . (string)$target[
                    'selection_mode'
                ]
                . '|'
                . implode(',', $ids);
        }


        sort($values);

        return $values;
    }


    /**
     * Validate submitted audience rules.
     *
     * Form structure:
     *
     * target_modes[ClubSecretary] = All
     *
     * OR
     *
     * target_modes[ClubSecretary] = Selected
     * target_users[ClubSecretary][] = 14
     * target_users[ClubSecretary][] = 29
     */
    private function validateAudienceTargets(
        $user,
        array $scope,
        $requireAtLeastOne
    ) {
        $modes =
            $_POST['target_modes']
            ?? [];


        $submittedUsers =
            $_POST['target_users']
            ?? [];


        if (!is_array($modes)) {
            $this->jsonResponse(
                422,
                [
                    'error' =>
                        'Invalid target recipient selection.',
                ]
            );
        }


        if (!is_array($submittedUsers)) {
            $this->jsonResponse(
                422,
                [
                    'error' =>
                        'Invalid selected recipients.',
                ]
            );
        }


        $allowedRoles =
            self::ALLOWED_TARGET_ROLES[
                $user->role
            ]
            ?? [];


        $userModel =
            $this->model('UserModel');


        $targets = [];


        foreach (
            $modes
            as $role => $mode
        ) {
            if (
                !is_string($role)
                ||
                !in_array(
                    $role,
                    $allowedRoles,
                    true
                )
            ) {
                $this->jsonResponse(
                    422,
                    [
                        'error' =>
                            'One or more target roles are not allowed.',
                    ]
                );
            }


            if (
                !is_string($mode)
                ||
                !in_array(
                    $mode,
                    ['All', 'Selected'],
                    true
                )
            ) {
                $this->jsonResponse(
                    422,
                    [
                        'error' =>
                            'Invalid recipient selection mode.',
                    ]
                );
            }


            $userIds = [];


            if ($mode === 'Selected') {
                $values =
                    $submittedUsers[$role]
                    ?? [];


                if (!is_array($values)) {
                    $this->jsonResponse(
                        422,
                        [
                            'error' =>
                                'Invalid selected recipients.',
                        ]
                    );
                }


                foreach ($values as $value) {
                    $selectedId =
                        filter_var(
                            $value,
                            FILTER_VALIDATE_INT,
                            [
                                'options' => [
                                    'min_range' => 1,
                                ],
                            ]
                        );


                    if ($selectedId === false) {
                        $this->jsonResponse(
                            422,
                            [
                                'error' =>
                                    'Invalid recipient selected.',
                            ]
                        );
                    }


                    $userIds[
                        (int)$selectedId
                    ] =
                        (int)$selectedId;
                }


                $userIds =
                    array_values($userIds);


                if (empty($userIds)) {
                    $this->jsonResponse(
                        422,
                        [
                            'error' =>
                                'Select at least one specific recipient for '
                                . $role
                                . '.',
                        ]
                    );
                }


                /*
                 * Do NOT trust submitted IDs.
                 *
                 * Build the list of users this manager
                 * is actually allowed to target.
                 */
                $eligibleUsers =
                    $userModel
                        ->findAnnouncementRecipients(
                            $role,
                            $scope['level'],
                            $scope['scope_id']
                        );


                $eligibleIds = [];


                foreach (
                    $eligibleUsers
                    as $eligibleUser
                ) {
                    $eligibleIds[
                        (int)$eligibleUser
                            ->user_id
                    ] = true;
                }


                foreach (
                    $userIds
                    as $selectedUserId
                ) {
                    if (
                        !isset(
                            $eligibleIds[
                                $selectedUserId
                            ]
                        )
                    ) {
                        $this->jsonResponse(
                            422,
                            [
                                'error' =>
                                    'A selected recipient is outside your allowed scope or does not have the selected role.',
                            ]
                        );
                    }
                }


                sort($userIds);
            }


            $targets[] = [
                'target_role' =>
                    $role,

                'selection_mode' =>
                    $mode,

                'user_ids' =>
                    $userIds,
            ];
        }


        if (
            $requireAtLeastOne
            &&
            empty($targets)
        ) {
            $this->jsonResponse(
                422,
                [
                    'error' =>
                        'Select at least one target role before publishing.',
                ]
            );
        }


        usort(
            $targets,
            function ($a, $b) {
                return strcmp(
                    $a['target_role'],
                    $b['target_role']
                );
            }
        );


        return $targets;
    }


    // ================================================================
    // List
    // ================================================================

    public function index()
    {
        $user =
            $this->currentUser();


        $scope =
            $this->managerScope($user);


        $model =
            $this->model(
                'AnnouncementModel'
            );


        $announcements =
            $model->findForUser(
                (int)$user->user_id,

                $user->role,

                $user->club_id
                    ?? null,

                $user->effective_division_id
                    ?? null,

                $user->effective_zonal_id
                    ?? null,

                $scope['level']
                    ?? null,

                $scope['scope_id']
                    ?? null
            );


        $counts = [
            'All' =>
                count($announcements),

            'Published' =>
                0,

            'Draft' =>
                0,
        ];


        foreach (
            $announcements
            as $announcement
        ) {
            $announcement->can_manage =
                $scope !== null
                &&
                $this->canManageAnnouncement(
                    $user,
                    $announcement
                );


            if (
                $announcement->status
                === 'Published'
            ) {
                $counts['Published']++;
            }


            if (
                $announcement->status
                === 'Draft'
                &&
                $announcement->can_manage
            ) {
                $counts['Draft']++;
            }
        }


        parent::view(
            'announcements/list',
            [
                'title' =>
                    'Announcements — YouthNexus',

                'announcements' =>
                    $announcements,

                'counts' =>
                    $counts,

                'csrf_token' =>
                    $_SESSION['csrf_token'],

                'userName' =>
                    $_SESSION['user_name']
                    ??
                    $_SESSION['username']
                    ??
                    'YouthNexus User',

                'userRole' =>
                    $user->role,

                'canManage' =>
                    $scope !== null,

                'managerLevel' =>
                    $scope['level']
                    ?? null,

                'availableTargetRoles' =>
                    self::ALLOWED_TARGET_ROLES[
                        $user->role
                    ]
                    ?? [],
            ]
        );
    }


    // ================================================================
    // Create / draft / update / publish
    // ================================================================

    public function create()
    {
        $this->saveAnnouncement(
            true
        );
    }


    public function saveDraft()
    {
        $this->saveAnnouncement(
            false
        );
    }


    public function update(
        $id = null
    ) {
        $this->saveAnnouncement(
            false,
            $this->positiveId($id)
        );
    }


    public function publish(
        $id = null
    ) {
        $this->saveAnnouncement(
            true,
            $this->positiveId($id)
        );
    }


    /**
     * Return editable announcement data to JavaScript.
     */
    public function edit(
        $id = null
    ) {
        $user =
            $this->currentUser();


        $scope =
            $this->requireManagerScope(
                $user
            );


        $id =
            $this->positiveId($id);


        $announcement =
            $this->model(
                'AnnouncementModel'
            )
            ->findManageableById(
                $id,
                $scope['level'],
                $scope['scope_id']
            );


        if (!$announcement) {
            $this->jsonResponse(
                404,
                [
                    'error' =>
                        'Announcement not found.',
                ]
            );
        }


        $attachments =
            $this->model(
                'AnnouncementAttachmentModel'
            )
            ->findByAnnouncementId(
                $id
            );


        $audienceTargets =
            $this->model(
                'AnnouncementAudienceModel'
            )
            ->findTargets($id);


        $this->jsonResponse(
            200,
            [
                'announcement' =>
                    $announcement,

                'audience_targets' =>
                    $audienceTargets,

                'attachments' =>
                    array_map(
                        function (
                            $attachment
                        ) {
                            return [
                                'attachment_id' =>
                                    (int)$attachment
                                        ->attachment_id,

                                'file_name' =>
                                    $attachment
                                        ->file_name,
                            ];
                        },

                        $attachments
                    ),
            ]
        );
    }


    /**
     * Return eligible specific users for a selected role.
     *
     * Example:
     *
     * /announcements/recipients/ClubSecretary
     */
    public function recipients(
        $role = null
    ) {
        $user =
            $this->currentUser();


        $scope =
            $this->requireManagerScope(
                $user
            );


        if (
            $_SERVER['REQUEST_METHOD']
            !== 'GET'
        ) {
            header('Allow: GET');

            $this->jsonResponse(
                405,
                [
                    'error' =>
                        'This action requires GET.',
                ]
            );
        }


        $role =
            rawurldecode(
                (string)$role
            );


        $allowedRoles =
            self::ALLOWED_TARGET_ROLES[
                $user->role
            ]
            ?? [];


        if (
            !in_array(
                $role,
                $allowedRoles,
                true
            )
        ) {
            $this->jsonResponse(
                403,
                [
                    'error' =>
                        'You cannot target that role.',
                ]
            );
        }


        $rows =
            $this->model('UserModel')
                ->findAnnouncementRecipients(
                    $role,
                    $scope['level'],
                    $scope['scope_id']
                );


        $recipients = [];


        foreach ($rows as $row) {
            $name =
                trim(
                    ($row->first_name ?? '')
                    . ' '
                    . ($row->last_name ?? '')
                );


            $recipients[] = [
                'user_id' =>
                    (int)$row->user_id,

                'name' =>
                    $name !== ''
                        ? $name
                        : $row->email,

                'email' =>
                    $row->email,

                'club_name' =>
                    $row->club_name
                    ?? null,

                'division_name' =>
                    $row->division_name
                    ?? null,

                'zonal_name' =>
                    $row->zonal_name
                    ?? null,
            ];
        }


        $this->jsonResponse(
            200,
            [
                'role' =>
                    $role,

                'recipients' =>
                    $recipients,
            ]
        );
    }


    private function saveAnnouncement(
        $publish,
        $id = null
    ) {
        $user =
            $this->currentUser();


        $scope =
            $this->requireManagerScope(
                $user
            );


        $this->requirePost();


        $title =
            $this->formText('title');

        $body =
            $this->formText('body');

        $priority =
            $this->formText('priority');

        $category =
            $this->formText('category');


        if (
            $title === ''
            ||
            $body === ''
            ||
            mb_strlen($title) > 150
            ||
            strlen($body) > 65535
            ||
            mb_strlen($category) > 100
        ) {
            $this->jsonResponse(
                422,
                [
                    'error' =>
                        'Title and body are required. '
                        . 'Limit the title to 150 characters, '
                        . 'category to 100 characters, '
                        . 'and body to 65 KB.',
                ]
            );
        }


        if (
            !in_array(
                $priority,
                ['Normal', 'Urgent'],
                true
            )
        ) {
            $this->jsonResponse(
                422,
                [
                    'error' =>
                        'Select Normal or Urgent priority.',
                ]
            );
        }


        /*
         * New Published announcement:
         * at least one audience required.
         *
         * Draft:
         * may have no audience yet.
         */
        $audienceTargets =
            $this->validateAudienceTargets(
                $user,
                $scope,
                $publish
            );


        $uploads =
            $this->validatedUploads();


        $removalIds =
            $this->requestedAttachmentRemovals();


        if (
            $id === null
            &&
            !empty($removalIds)
        ) {
            $this->jsonResponse(
                422,
                [
                    'error' =>
                        'Only existing announcements have attachments to remove.',
                ]
            );
        }


        $model =
            $this->model(
                'AnnouncementModel'
            );


        $audienceModel =
            $this->model(
                'AnnouncementAudienceModel'
            );


        $attachmentModel =
            $this->model(
                'AnnouncementAttachmentModel'
            );


        $auditModel =
            $this->model(
                'AuditLogModel'
            );


        $db =
            Database::getInstance()
                ->getConnection();


        $savedFiles = [];

        $removedAttachments = [];

        $changed = true;

        $wasPublished = false;


        $data = [
            'title' =>
                $title,

            'body' =>
                $body,

            'priority' =>
                $priority,

            'category' =>
                $category !== ''
                    ? $category
                    : null,

            /*
             * Scope comes only from authenticated
             * user data.
             *
             * Never accept scope from POST.
             */
            'level' =>
                $scope['level'],

            'organizer_club_id' =>
                $scope[
                    'organizer_club_id'
                ],

            'organizer_division_id' =>
                $scope[
                    'organizer_division_id'
                ],

            'organizer_zonal_id' =>
                $scope[
                    'organizer_zonal_id'
                ],

            'created_by' =>
                (int)$user->user_id,

            'status' =>
                $publish
                    ? 'Published'
                    : 'Draft',
        ];


        try {
            $db->beginTransaction();


            if ($id !== null) {
                /*
                 * Lock row during edit/publish.
                 */
                $existing =
                    $model
                        ->findManageableById(
                            $id,
                            $scope['level'],
                            $scope['scope_id'],
                            true
                        );


                if (!$existing) {
                    $db->rollBack();

                    $this->jsonResponse(
                        404,
                        [
                            'error' =>
                                'Announcement not found or you cannot manage it.',
                        ]
                    );
                }


                /*
                 * Publish route is for Draft only.
                 */
                if (
                    $publish
                    &&
                    $existing->status
                    !== 'Draft'
                ) {
                    $db->rollBack();

                    $this->jsonResponse(
                        409,
                        [
                            'error' =>
                                'This announcement is already published or its status changed.',
                        ]
                    );
                }


                /*
                 * Normal edit route checks the state
                 * that existed when the editor opened.
                 */
                if (!$publish) {
                    $expectedStatus =
                        $_POST[
                            'expected_status'
                        ]
                        ?? '';


                    if (
                        !is_string(
                            $expectedStatus
                        )
                        ||
                        $expectedStatus
                        !== $existing->status
                    ) {
                        $db->rollBack();

                        $this->jsonResponse(
                            409,
                            [
                                'error' =>
                                    'This announcement changed since you opened it. Reopen it before saving.',
                            ]
                        );
                    }
                }


                $wasPublished =
                    $existing->status
                    === 'Published';


                /*
                 * Published announcements must
                 * always retain a real audience.
                 */
                if (
                    $wasPublished
                    &&
                    empty(
                        $audienceTargets
                    )
                ) {
                    $db->rollBack();

                    $this->jsonResponse(
                        422,
                        [
                            'error' =>
                                'A published announcement must target at least one role.',
                        ]
                    );
                }


                /*
                 * Validate attachment removals.
                 */
                foreach (
                    $removalIds
                    as $attachmentId
                ) {
                    $attachment =
                        $attachmentModel
                            ->findById(
                                $attachmentId
                            );


                    if (
                        !$attachment
                        ||
                        (int)$attachment
                            ->announcement_id
                        !==
                        (int)$id
                    ) {
                        $db->rollBack();

                        $this->jsonResponse(
                            409,
                            [
                                'error' =>
                                    'An attachment is unavailable. Reopen the editor before saving.',
                            ]
                        );
                    }


                    $removedAttachments[] =
                        $attachment;
                }


                /*
                 * Compare old audience configuration
                 * with the newly submitted one.
                 */
                $existingTargets =
                    $audienceModel
                        ->findTargets($id);


                $audienceChanged =
                    $this
                        ->canonicalAudienceTargets(
                            $existingTargets
                        )
                    !==
                    $this
                        ->canonicalAudienceTargets(
                            $audienceTargets
                        );


                $contentChanged =
                    (string)$existing->title
                    !==
                    (string)$data['title']

                    ||

                    (string)$existing->body
                    !==
                    (string)$data['body']

                    ||

                    (string)$existing->category
                    !==
                    (string)$data['category']

                    ||

                    (string)$existing->priority
                    !==
                    (string)$data['priority'];


                $changed =
                    $contentChanged
                    ||
                    $audienceChanged
                    ||
                    count($uploads) > 0
                    ||
                    count(
                        $removedAttachments
                    ) > 0;


                /*
                 * Publish Draft.
                 */
                if ($publish) {
                    $published =
                        $model->publish(
                            $id,
                            $data
                        );


                    if (!$published) {
                        $db->rollBack();

                        $this->jsonResponse(
                            409,
                            [
                                'error' =>
                                    'Unable to publish because the announcement status changed.',
                            ]
                        );
                    }
                } elseif (
                    $contentChanged
                    ||
                    (
                        $wasPublished
                        &&
                        $audienceChanged
                    )
                ) {
                    /*
                     * Audience-only change on a Published
                     * announcement should also update the
                     * Edited timestamp.
                     */
                    $model->updateContent(
                        $id,
                        $data
                    );
                }


                if ($audienceChanged) {
                    $audienceModel
                        ->replaceTargets(
                            $id,
                            $audienceTargets
                        );
                }
            } else {
                /*
                 * New Published announcement must
                 * contain at least one audience.
                 */
                if (
                    $publish
                    &&
                    empty(
                        $audienceTargets
                    )
                ) {
                    $db->rollBack();

                    $this->jsonResponse(
                        422,
                        [
                            'error' =>
                                'Select at least one target role before publishing.',
                        ]
                    );
                }


                $id =
                    $model->create(
                        $data
                    );


                $audienceModel
                    ->replaceTargets(
                        $id,
                        $audienceTargets
                    );
            }


            /*
             * Store new attachments.
             */
            foreach (
                $uploads
                as $upload
            ) {
                $path =
                    $this->storeUpload(
                        $upload
                    );


                $savedFiles[] =
                    $path;


                $attachmentModel->create(
                    $id,

                    $upload['name'],

                    'uploads/announcement_attachments/'
                    . basename($path),

                    $upload['size']
                );
            }


            /*
             * Remove attachment DB rows.
             */
            foreach (
                $removedAttachments
                as $attachment
            ) {
                $attachmentModel
                    ->deleteFromAnnouncement(
                        $attachment
                            ->attachment_id,

                        $id
                    );
            }


            /*
             * Audit meaningful changes.
             */
            if (
                $publish
                ||
                $changed
            ) {
                if ($publish) {
                    $action =
                        'PUBLISH_ANNOUNCEMENT';

                } elseif ($wasPublished) {
                    $action =
                        'EDIT_ANNOUNCEMENT';

                } else {
                    $action =
                        'SAVE_DRAFT_ANNOUNCEMENT';
                }


                $auditModel->log(
                    (int)$user->user_id,

                    $action,

                    'Announcement',

                    $id,

                    "Saved announcement '{$title}'"
                );
            }


            $db->commit();

        } catch (Throwable $error) {
            if (
                $db->inTransaction()
            ) {
                $db->rollBack();
            }


            /*
             * Uploaded files are outside the
             * database transaction.
             */
            foreach (
                $savedFiles
                as $path
            ) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }


            error_log(
                'Announcement save failed: '
                . $error->getMessage()
            );


            $this->jsonResponse(
                500,
                [
                    'error' =>
                        'Unable to save the announcement and its attachments. Please try again.',
                ]
            );
        }


        /*
         * Delete physical old files only
         * after DB commit succeeds.
         */
        foreach (
            $removedAttachments
            as $attachment
        ) {
            $this->removeStoredAttachment(
                $attachment->file_path
            );
        }


        $this->jsonResponse(
            200,
            [
                'success' =>
                    true,

                'id' =>
                    $id,

                'message' =>
                    $publish
                        ? 'Announcement published successfully.'
                        : 'Announcement saved successfully.',
            ]
        );
    }


    // ================================================================
    // Delete
    // ================================================================

    public function delete(
        $id = null
    ) {
        $user =
            $this->currentUser();


        $scope =
            $this->requireManagerScope(
                $user
            );


        $this->requirePost();


        $id =
            $this->positiveId($id);


        $model =
            $this->model(
                'AnnouncementModel'
            );


        $auditModel =
            $this->model(
                'AuditLogModel'
            );


        $db =
            Database::getInstance()
                ->getConnection();


        try {
            $db->beginTransaction();


            $announcement =
                $model
                    ->findManageableById(
                        $id,
                        $scope['level'],
                        $scope['scope_id'],
                        true
                    );


            if (!$announcement) {
                $db->rollBack();

                $this->jsonResponse(
                    404,
                    [
                        'error' =>
                            'Announcement not found.',
                    ]
                );
            }


            if (
                !$model->softDelete(
                    $id
                )
            ) {
                $db->rollBack();

                $this->jsonResponse(
                    409,
                    [
                        'error' =>
                            'The announcement could not be deleted because its state changed.',
                    ]
                );
            }


            $auditModel->log(
                (int)$user->user_id,

                'DELETE_ANNOUNCEMENT',

                'Announcement',

                $id,

                "Deleted announcement '{$announcement->title}'"
            );


            $db->commit();

        } catch (Throwable $error) {
            if (
                $db->inTransaction()
            ) {
                $db->rollBack();
            }


            error_log(
                'Announcement delete failed: '
                . $error->getMessage()
            );


            $this->jsonResponse(
                500,
                [
                    'error' =>
                        'Unable to delete the announcement.',
                ]
            );
        }


        $this->jsonResponse(
            200,
            [
                'success' =>
                    true,

                'message' =>
                    'Announcement deleted successfully.',
            ]
        );
    }


    // ================================================================
    // Detail page
    // ================================================================

    public function view(
        $id = null,
        $data = []
    ) {
        $user =
            $this->currentUser();


        $announcement =
            $this->visibleAnnouncement(
                $id,
                $user
            );


        $id =
            (int)$announcement
                ->announcement_id;


        $canManage =
            $this->canManageAnnouncement(
                $user,
                $announcement
            );


        $isRecipient =
            $this->isPublishedRecipient(
                $user,
                $announcement
            );


        /*
         * One view per session.
         */
        $viewKey =
            $_SESSION['user_id']
            . ':'
            . $id;


        if (
            $announcement->status
            === 'Published'
            &&
            empty(
                $_SESSION[
                    'viewed_announcements'
                ][$viewKey]
            )
        ) {
            $this->model(
                'AnnouncementModel'
            )->incrementViewCount(
                $id
            );


            $_SESSION[
                'viewed_announcements'
            ][$viewKey] = true;


            $announcement->view_count++;
        }


        parent::view(
            'announcements/detail',
            [
                'title' =>
                    $announcement->title
                    . ' — YouthNexus',

                'announcement' =>
                    $announcement,

                'attachments' =>
                    $this->model(
                        'AnnouncementAttachmentModel'
                    )
                    ->findByAnnouncementId(
                        $id
                    ),

                /*
                 * New detailed audience structure.
                 */
                'audienceTargets' =>
                    $this->model(
                        'AnnouncementAudienceModel'
                    )
                    ->findTargetsWithUsers(
                        $id
                    ),

                'hasRead' =>
                    $isRecipient
                        ?
                        $this->model(
                            'AnnouncementReadModel'
                        )
                        ->hasRead(
                            $id,
                            (int)$user->user_id
                        )
                        :
                        false,

                'isRecipient' =>
                    $isRecipient,

                'canManage' =>
                    $canManage,

                'csrf_token' =>
                    $_SESSION['csrf_token'],
            ]
        );
    }


    // ================================================================
    // Read receipt
    // ================================================================

    public function markRead(
        $id = null
    ) {
        $user =
            $this->currentUser();


        $this->requirePost();


        $announcement =
            $this->visibleAnnouncement(
                $id,
                $user
            );


        if (
            $announcement->status
            !== 'Published'
        ) {
            $this->jsonResponse(
                409,
                [
                    'error' =>
                        'Only published announcements can be marked as read.',
                ]
            );
        }


        if (
            !$this->isPublishedRecipient(
                $user,
                $announcement
            )
        ) {
            $this->jsonResponse(
                403,
                [
                    'error' =>
                        'This announcement was not addressed to you.',
                ]
            );
        }


        $this->model(
            'AnnouncementReadModel'
        )->markRead(
            (int)$announcement
                ->announcement_id,

            (int)$user->user_id
        );


        $this->jsonResponse(
            200,
            [
                'success' =>
                    true,
            ]
        );
    }


    // ================================================================
    // Secure attachment download
    // ================================================================

    public function download(
        $id = null
    ) {
        $user =
            $this->currentUser();


        $attachment =
            $this->model(
                'AnnouncementAttachmentModel'
            )
            ->findById(
                $this->positiveId($id)
            );


        if (!$attachment) {
            $this->jsonResponse(
                404,
                [
                    'error' =>
                        'Attachment not found.',
                ]
            );
        }


        /*
         * Authorization comes from parent
         * announcement visibility.
         */
        $this->visibleAnnouncement(
            $attachment
                ->announcement_id,

            $user
        );


        $directory =
            realpath(
                dirname(__DIR__, 2)
                . '/uploads/announcement_attachments'
            );


        $path =
            realpath(
                dirname(__DIR__, 2)
                . '/'
                . $attachment->file_path
            );


        if (
            !$directory
            ||
            !$path
            ||
            strpos(
                $path,
                $directory
                . DIRECTORY_SEPARATOR
            ) !== 0
            ||
            !is_file($path)
            ||
            !is_readable($path)
        ) {
            $this->jsonResponse(
                404,
                [
                    'error' =>
                        'Attachment file not found.',
                ]
            );
        }


        $name =
            basename(
                str_replace(
                    '\\',
                    '/',
                    $attachment->file_name
                )
            );


        $name =
            str_replace(
                ["\r", "\n", '"'],
                '_',
                $name
            );


        header(
            'Content-Type: application/octet-stream'
        );

        header(
            'X-Content-Type-Options: nosniff'
        );

        header(
            'Cache-Control: private, no-store'
        );

        header(
            'Content-Disposition: attachment; '
            . 'filename="download"; '
            . "filename*=UTF-8''"
            . rawurlencode($name)
        );

        header(
            'Content-Length: '
            . filesize($path)
        );


        session_write_close();

        readfile($path);

        exit();
    }


    // ================================================================
    // Attachment helpers
    // ================================================================

    private function requestedAttachmentRemovals()
    {
        $values =
            $_POST[
                'remove_attachments'
            ]
            ?? [];


        if (!is_array($values)) {
            $this->jsonResponse(
                422,
                [
                    'error' =>
                        'Invalid attachment removal selection.',
                ]
            );
        }


        $ids = [];


        foreach ($values as $value) {
            $id =
                is_string($value)
                    ?
                    filter_var(
                        $value,
                        FILTER_VALIDATE_INT,
                        [
                            'options' => [
                                'min_range' =>
                                    1,
                            ],
                        ]
                    )
                    :
                    false;


            if ($id === false) {
                $this->jsonResponse(
                    422,
                    [
                        'error' =>
                            'Invalid attachment removal selection.',
                    ]
                );
            }


            $ids[(int)$id] =
                (int)$id;
        }


        return array_values(
            $ids
        );
    }


    private function removeStoredAttachment(
        $storedPath
    ) {
        $directory =
            realpath(
                dirname(__DIR__, 2)
                . '/uploads/announcement_attachments'
            );


        $path =
            realpath(
                dirname(__DIR__, 2)
                . '/'
                . $storedPath
            );


        if (
            $directory
            &&
            $path
            &&
            strpos(
                $path,
                $directory
                . DIRECTORY_SEPARATOR
            ) === 0
            &&
            is_file($path)
        ) {
            if (!@unlink($path)) {
                error_log(
                    'Unable to clean up removed announcement attachment: '
                    . $storedPath
                );
            }
        }
    }


    private function validatedUploads()
    {
        if (
            !isset(
                $_FILES['attachments']
            )
        ) {
            return [];
        }


        $files =
            $_FILES['attachments'];


        if (
            !isset($files['error'])
            ||
            !is_array(
                $files['error']
            )
        ) {
            $this->jsonResponse(
                422,
                [
                    'error' =>
                        'Invalid attachment upload.',
                ]
            );
        }


        /*
         * PHP Fileinfo is a built-in PHP extension,
         * not an external library/framework.
         */
        $types = [
            'pdf' => [
                'application/pdf',
            ],

            'png' => [
                'image/png',
            ],

            'jpg' => [
                'image/jpeg',
            ],

            'jpeg' => [
                'image/jpeg',
            ],

            'doc' => [
                'application/msword',
                'application/x-ole-storage',
            ],

            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ];


        $uploads = [];


        $finfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );


        foreach (
            $files['error']
            as $i => $error
        ) {
            if (
                $error
                === UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }


            $name =
                $files['name'][$i]
                ?? null;


            $tmp =
                $files['tmp_name'][$i]
                ?? null;


            if (
                $error
                !== UPLOAD_ERR_OK
                ||
                !is_string($name)
                ||
                !is_string($tmp)
                ||
                !is_uploaded_file($tmp)
            ) {
                $this->jsonResponse(
                    422,
                    [
                        'error' =>
                            'An attachment could not be uploaded. Please select it again.',
                    ]
                );
            }


            $name =
                basename(
                    str_replace(
                        '\\',
                        '/',
                        $name
                    )
                );


            $extension =
                strtolower(
                    pathinfo(
                        $name,
                        PATHINFO_EXTENSION
                    )
                );


            $size =
                filesize($tmp);


            if (
                $size === false
                ||
                $size >
                    10 * 1024 * 1024
                ||
                mb_strlen($name) > 255
                ||
                !isset(
                    $types[$extension]
                )
                ||
                !in_array(
                    $finfo->file($tmp),
                    $types[$extension],
                    true
                )
            ) {
                $this->jsonResponse(
                    422,
                    [
                        'error' =>
                            'Attachments must be PDF, PNG, JPEG, DOC or DOCX files of at most 10 MB each.',
                    ]
                );
            }


            $uploads[] = [
                'name' =>
                    $name,

                'tmp' =>
                    $tmp,

                'size' =>
                    $size,

                'extension' =>
                    $extension,
            ];
        }


        return $uploads;
    }


    private function storeUpload(
        $upload
    ) {
        $directory =
            dirname(__DIR__, 2)
            . '/uploads/announcement_attachments';


        if (
            !is_dir($directory)
            &&
            !mkdir(
                $directory,
                0775,
                true
            )
            &&
            !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Attachment directory is unavailable.'
            );
        }


        $path =
            $directory
            . '/'
            . bin2hex(
                random_bytes(16)
            )
            . '.'
            . $upload['extension'];


        if (
            !move_uploaded_file(
                $upload['tmp'],
                $path
            )
        ) {
            throw new RuntimeException(
                'Unable to store attachment.'
            );
        }


        return $path;
    }


    // ================================================================
    // JSON helper
    // ================================================================

    private function jsonResponse(
        $statusCode,
        array $data
    ) {
        header(
            'Content-Type: application/json'
        );

        http_response_code(
            $statusCode
        );

        echo json_encode($data);

        exit();
    }
}