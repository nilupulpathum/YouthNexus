<?php

class AnnouncementAudienceModel extends Model
{
    /**
     * Replace the complete audience configuration
     * for an announcement.
     *
     * Example:
     *
     * [
     *     [
     *         'target_role' => 'ClubPresident',
     *         'selection_mode' => 'All',
     *         'user_ids' => []
     *     ],
     *     [
     *         'target_role' => 'ClubSecretary',
     *         'selection_mode' => 'Selected',
     *         'user_ids' => [12, 18, 25]
     *     ]
     * ]
     */
    public function replaceTargets(
        $announcementId,
        array $targets
    ) {
        $announcementId =
            (int)$announcementId;

        /*
         * AnnouncementAudienceUser rows are deleted
         * automatically because of ON DELETE CASCADE.
         */
        $this->query(
            "
            DELETE FROM AnnouncementAudience
            WHERE announcement_id = ?
            ",
            [$announcementId]
        );

        foreach ($targets as $target) {

            $role =
                (string)($target['target_role'] ?? '');

            $mode =
                (string)($target['selection_mode'] ?? '');

            $userIds =
                $target['user_ids'] ?? [];


            if (
                $role === ''
                ||
                !in_array(
                    $mode,
                    ['All', 'Selected'],
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    'Invalid announcement audience target.'
                );
            }


            $this->query(
                "
                INSERT INTO AnnouncementAudience (
                    announcement_id,
                    target_role,
                    selection_mode
                )
                VALUES (?, ?, ?)
                ",
                [
                    $announcementId,
                    $role,
                    $mode,
                ]
            );


            $audienceId =
                (int)Database::getInstance()
                    ->getConnection()
                    ->lastInsertId();


            /*
             * All mode does not need individual users.
             */
            if ($mode !== 'Selected') {
                continue;
            }


            if (!is_array($userIds)) {
                throw new InvalidArgumentException(
                    'Invalid selected announcement recipients.'
                );
            }


            $userIds =
                array_values(
                    array_unique(
                        array_map(
                            'intval',
                            $userIds
                        )
                    )
                );


            foreach ($userIds as $userId) {

                if ($userId <= 0) {
                    continue;
                }


                $this->query(
                    "
                    INSERT INTO AnnouncementAudienceUser (
                        audience_id,
                        user_id
                    )
                    VALUES (?, ?)
                    ",
                    [
                        $audienceId,
                        $userId,
                    ]
                );
            }
        }
    }


    /**
     * Return audience targets for editing.
     *
     * Result example:
     *
     * [
     *     [
     *         'audience_id' => 5,
     *         'target_role' => 'ClubSecretary',
     *         'selection_mode' => 'Selected',
     *         'user_ids' => [12, 18]
     *     ]
     * ]
     */
    public function findTargets(
        $announcementId
    ) {
        $rows =
            $this->resultSet(
                "
                SELECT
                    aa.audience_id,
                    aa.target_role,
                    aa.selection_mode,
                    aau.user_id

                FROM AnnouncementAudience aa

                LEFT JOIN AnnouncementAudienceUser aau
                    ON aau.audience_id =
                        aa.audience_id

                WHERE aa.announcement_id = ?

                ORDER BY
                    aa.target_role,
                    aau.user_id
                ",
                [
                    (int)$announcementId,
                ]
            );


        $targets = [];


        foreach ($rows as $row) {

            $audienceId =
                (int)$row->audience_id;


            if (
                !isset(
                    $targets[$audienceId]
                )
            ) {
                $targets[$audienceId] = [
                    'audience_id' =>
                        $audienceId,

                    'target_role' =>
                        $row->target_role,

                    'selection_mode' =>
                        $row->selection_mode,

                    'user_ids' =>
                        [],
                ];
            }


            if ($row->user_id !== null) {

                $targets[
                    $audienceId
                ]['user_ids'][] =
                    (int)$row->user_id;
            }
        }


        return array_values(
            $targets
        );
    }


    /**
     * Return audience targets plus user information
     * for the announcement detail page.
     */
    public function findTargetsWithUsers(
        $announcementId
    ) {
        $rows =
            $this->resultSet(
                "
                SELECT
                    aa.audience_id,
                    aa.target_role,
                    aa.selection_mode,

                    aau.user_id,

                    CONCAT(
                        u.first_name,
                        ' ',
                        u.last_name
                    ) AS user_name,

                    u.email,

                    c.club_name,

                    d.division_name,

                    z.zonal_name

                FROM AnnouncementAudience aa

                LEFT JOIN AnnouncementAudienceUser aau
                    ON aau.audience_id =
                        aa.audience_id

                LEFT JOIN User u
                    ON u.user_id =
                        aau.user_id

                LEFT JOIN Club c
                    ON c.club_id =
                        u.club_id

                LEFT JOIN Division d
                    ON d.division_id =
                        COALESCE(
                            u.division_id,
                            c.division_id
                        )

                LEFT JOIN Zone z
                    ON z.zonal_id =
                        COALESCE(
                            u.zonal_id,
                            d.zonal_id
                        )

                WHERE aa.announcement_id = ?

                ORDER BY
                    aa.target_role,
                    u.first_name,
                    u.last_name
                ",
                [
                    (int)$announcementId,
                ]
            );


        $targets = [];


        foreach ($rows as $row) {

            $audienceId =
                (int)$row->audience_id;


            if (
                !isset(
                    $targets[$audienceId]
                )
            ) {
                $targets[$audienceId] = [
                    'audience_id' =>
                        $audienceId,

                    'target_role' =>
                        $row->target_role,

                    'selection_mode' =>
                        $row->selection_mode,

                    'users' =>
                        [],
                ];
            }


            if ($row->user_id !== null) {

                $targets[
                    $audienceId
                ]['users'][] = [
                    'user_id' =>
                        (int)$row->user_id,

                    'name' =>
                        trim(
                            (string)$row->user_name
                        ),

                    'email' =>
                        $row->email,

                    'club_name' =>
                        $row->club_name,

                    'division_name' =>
                        $row->division_name,

                    'zonal_name' =>
                        $row->zonal_name,
                ];
            }
        }


        return array_values(
            $targets
        );
    }


    /**
     * Check whether one specific user is an intended
     * recipient of an announcement.
     *
     * The controller separately checks organisational scope.
     */
    public function isUserTargeted(
        $announcementId,
        $role,
        $userId
    ) {
        return (bool)$this->single(
            "
            SELECT
                aa.audience_id

            FROM AnnouncementAudience aa

            WHERE aa.announcement_id = ?

              AND aa.target_role = ?

              AND (
                    aa.selection_mode = 'All'

                    OR (
                        aa.selection_mode = 'Selected'

                        AND EXISTS (
                            SELECT 1

                            FROM AnnouncementAudienceUser aau

                            WHERE aau.audience_id =
                                aa.audience_id

                              AND aau.user_id = ?
                        )
                    )
              )

            LIMIT 1
            ",
            [
                (int)$announcementId,
                (string)$role,
                (int)$userId,
            ]
        );
    }
}