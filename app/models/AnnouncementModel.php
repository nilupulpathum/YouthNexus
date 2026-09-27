<?php

class AnnouncementModel extends Model
{
    private const VALID_LEVELS = [
        'Club',
        'Divisional',
        'Zonal',
        'NYSC',
    ];

    /**
     * Build the SQL rule for announcements managed by a secretary/admin.
     */
    private function managerScopeCondition($level, $scopeId, $alias = 'a')
    {
        if (!in_array($level, self::VALID_LEVELS, true)) {
            throw new InvalidArgumentException(
                'Invalid announcement level.'
            );
        }

        switch ($level) {
            case 'Club':
                return [
                    "{$alias}.level = 'Club'
                     AND {$alias}.organizer_club_id = ?",
                    [(int)$scopeId],
                ];

            case 'Divisional':
                return [
                    "{$alias}.level = 'Divisional'
                     AND {$alias}.organizer_division_id = ?",
                    [(int)$scopeId],
                ];

            case 'Zonal':
                return [
                    "{$alias}.level = 'Zonal'
                     AND {$alias}.organizer_zonal_id = ?",
                    [(int)$scopeId],
                ];

            case 'NYSC':
                return [
                    "{$alias}.level = 'NYSC'",
                    [],
                ];
        }

        throw new InvalidArgumentException(
            'Invalid announcement level.'
        );
    }

    /**
     * Find an announcement which the current secretary/admin
     * is allowed to manage.
     *
     * Management is based on organisational scope,
     * NOT created_by.
     */
    public function findManageableById(
        $id,
        $level,
        $scopeId = null,
        $lock = false
    ) {
        [$scopeSql, $scopeParams] =
            $this->managerScopeCondition(
                $level,
                $scopeId
            );

        $sql = "
            SELECT a.*
            FROM Announcement a
            WHERE a.announcement_id = ?
              AND a.deleted_at IS NULL
              AND a.status IN ('Draft', 'Published', 'Retracted', 'Archived')
              AND {$scopeSql}
        ";

        if ($lock) {
            $sql .= ' FOR UPDATE';
        }

        return $this->single(
            $sql,
            array_merge(
                [(int)$id],
                $scopeParams
            )
        );
    }

    /**
     * Create a new announcement.
     *
     * The audience roles themselves are stored separately in
     * AnnouncementAudience.
     */
    public function create(array $data)
    {
        $level =
            $data['level'] ?? '';

        if (
            !in_array(
                $level,
                self::VALID_LEVELS,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement level.'
            );
        }

        $status =
            $data['status'] ?? 'Draft';

        if (
            !in_array(
                $status,
                ['Draft', 'Published'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement status.'
            );
        }

        $publishedAt =
            $status === 'Published'
                ? date('Y-m-d H:i:s')
                : null;

        $sql = "
            INSERT INTO Announcement (
                title,
                body,
                level,
                organizer_club_id,
                organizer_division_id,
                organizer_zonal_id,
                target_audience,
                category,
                priority,
                status,
                view_count,
                created_by,
                published_at,
                created_at
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NULL,
                ?,
                ?,
                ?,
                0,
                ?,
                ?,
                NOW()
            )
        ";

        $params = [
            $data['title'],
            $data['body'],
            $level,

            $data['organizer_club_id']
                ?? null,

            $data['organizer_division_id']
                ?? null,

            $data['organizer_zonal_id']
                ?? null,

            $data['category']
                ?? null,

            $data['priority']
                ?? 'Normal',

            $status,

            (int)$data['created_by'],

            $publishedAt,
        ];

        $this->query(
            $sql,
            $params
        );

        return (int)Database::getInstance()
            ->getConnection()
            ->lastInsertId();
    }

    /**
     * Update editable announcement content.
     *
     * Authorization must already have been checked by
     * findManageableById().
     */
    public function updateContent(
        $id,
        array $data
    ) {
        $sql = "
            UPDATE Announcement
            SET
                title = ?,
                body = ?,
                category = ?,
                priority = ?,
                content_edited_at = NOW()
            WHERE announcement_id = ?
              AND deleted_at IS NULL
              AND status IN ('Draft', 'Published')
        ";

        $stmt = $this->query(
            $sql,
            [
                $data['title'],
                $data['body'],

                $data['category']
                    ?? null,

                $data['priority']
                    ?? 'Normal',

                (int)$id,
            ]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Publish an existing Draft.
     */
    public function publish(
        $id,
        array $data
    ) {
        $sql = "
            UPDATE Announcement
            SET
                title = ?,
                body = ?,
                category = ?,
                priority = ?,
                status = 'Published',
                published_at = NOW()
            WHERE announcement_id = ?
              AND deleted_at IS NULL
              AND status = 'Draft'
        ";

        $stmt = $this->query(
            $sql,
            [
                $data['title'],
                $data['body'],

                $data['category']
                    ?? null,

                $data['priority']
                    ?? 'Normal',

                (int)$id,
            ]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Soft-delete an announcement.
     */
    public function softDelete($id)
    {
        $stmt = $this->query(
            "
            UPDATE Announcement
            SET deleted_at = NOW()
            WHERE announcement_id = ?
              AND deleted_at IS NULL
              AND status = 'Draft'
            ",
            [(int)$id]
        );

        return $stmt->rowCount() > 0;
    }

    public function transitionLifecycle(
        int $id,
        string $fromStatus,
        string $toStatus,
        int $userId,
        string $reason
    ): bool {
        $allowed = [
            'Published:Retracted',
            'Retracted:Archived',
            'Published:Archived',
            'Archived:Retracted',
        ];
        if (!in_array($fromStatus . ':' . $toStatus, $allowed, true)) {
            throw new InvalidArgumentException('This announcement transition is not permitted.');
        }
        $reason = trim($reason);
        if (strlen($reason) < 5 || strlen($reason) > 1000) {
            throw new InvalidArgumentException('Provide a reason between 5 and 1000 characters.');
        }
        $stmt = $this->query(
            "UPDATE Announcement
             SET status = ?, lifecycle_reason = ?, lifecycle_changed_by = ?, lifecycle_changed_at = NOW()
             WHERE announcement_id = ? AND deleted_at IS NULL AND status = ?",
            [$toStatus, $reason, $userId, $id, $fromStatus]
        );
        return $stmt->rowCount() === 1;
    }

    /**
     * Find one announcement and its creator/scope information.
     */
    public function findById($id)
    {
        $sql = "
            SELECT
                a.*,

                c.club_name
                    AS organizer_club_name,

                d.division_name
                    AS organizer_division_name,

                z.zonal_name
                    AS organizer_zonal_name,

                CONCAT(
                    u.first_name,
                    ' ',
                    u.last_name
                ) AS posted_by_name,

                u.role
                    AS posted_by_role,

                u.email
                    AS posted_by_email,

                (
                    SELECT COUNT(*)
                    FROM AnnouncementAttachment att
                    WHERE att.announcement_id =
                        a.announcement_id
                ) AS attachment_count,

                (
                    SELECT GROUP_CONCAT(
                        aa.target_role
                        ORDER BY aa.target_role
                        SEPARATOR ','
                    )
                    FROM AnnouncementAudience aa
                    WHERE aa.announcement_id =
                        a.announcement_id
                ) AS target_roles_csv

            FROM Announcement a

            LEFT JOIN Club c
                ON a.organizer_club_id =
                    c.club_id

            LEFT JOIN Division d
                ON a.organizer_division_id =
                    d.division_id

            LEFT JOIN Zone z
                ON a.organizer_zonal_id =
                    z.zonal_id

            JOIN User u
                ON a.created_by =
                    u.user_id

            WHERE a.announcement_id = ?
              AND a.deleted_at IS NULL

            LIMIT 1
        ";

        return $this->single(
            $sql,
            [(int)$id]
        );
    }

    /**
     * Find every announcement visible to the current user.
     *
     * A normal viewer sees only:
     *
     * - Published announcements
     * - targeted to their exact role
     * - within their Club / Division / Zone / NYSC scope
     *
     * A manager additionally sees Draft/Published announcements
     * belonging to the level and scope they manage.
     */


    public function findForUser(
    $userId,
    $role,
    $clubId,
    $divisionId,
    $zonalId,
    $managerLevel = null,
    $managerScopeId = null,
    array $filters = []
) {
    $params = [
        (string)$role,
        (int)$userId,

        $zonalId !== null
            ? (int)$zonalId
            : null,

        $divisionId !== null
            ? (int)$divisionId
            : null,

        $clubId !== null
            ? (int)$clubId
            : null,
    ];


    $recipientSql = "
        (
            a.status = 'Published'

            AND EXISTS (
                SELECT 1

                FROM AnnouncementAudience aa

                WHERE aa.announcement_id =
                    a.announcement_id

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
            )

            AND (
                a.level = 'NYSC'

                OR (
                    a.level = 'Zonal'
                    AND a.organizer_zonal_id = ?
                )

                OR (
                    a.level = 'Divisional'
                    AND a.organizer_division_id = ?
                )

                OR (
                    a.level = 'Club'
                    AND a.organizer_club_id = ?
                )
            )
        )
    ";


    $managerSql =
        '1 = 0';


    if (
        $managerLevel !== null
        && in_array(
            $managerLevel,
            self::VALID_LEVELS,
            true
        )
    ) {
        switch ($managerLevel) {

            case 'Club':

                $managerSql = "
                    (
                        a.level = 'Club'
                        AND a.organizer_club_id = ?
                    )
                ";

                $params[] =
                    (int)$managerScopeId;

                break;


            case 'Divisional':

                $managerSql = "
                    (
                        a.level = 'Divisional'
                        AND a.organizer_division_id = ?
                    )
                ";

                $params[] =
                    (int)$managerScopeId;

                break;


            case 'Zonal':

                $managerSql = "
                    (
                        a.level = 'Zonal'
                        AND a.organizer_zonal_id = ?
                    )
                ";

                $params[] =
                    (int)$managerScopeId;

                break;


            case 'NYSC':

                $managerSql = "
                    a.level = 'NYSC'
                ";

                break;
        }
    }


    $sql = "
        SELECT
            a.*,

            c.club_name
                AS organizer_club_name,

            d.division_name
                AS organizer_division_name,

            z.zonal_name
                AS organizer_zonal_name,

            CONCAT(
                u.first_name,
                ' ',
                u.last_name
            ) AS creator_name,

            u.role
                AS creator_role,

            (
                SELECT COUNT(*)

                FROM AnnouncementAttachment att

                WHERE att.announcement_id =
                    a.announcement_id
            ) AS attachment_count,

            (
                SELECT GROUP_CONCAT(
                    aa2.target_role
                    ORDER BY aa2.target_role
                    SEPARATOR ','
                )

                FROM AnnouncementAudience aa2

                WHERE aa2.announcement_id =
                    a.announcement_id
            ) AS target_roles_csv

        FROM Announcement a

        JOIN User u
            ON u.user_id =
                a.created_by

        LEFT JOIN Club c
            ON c.club_id =
                a.organizer_club_id

        LEFT JOIN Division d
            ON d.division_id =
                a.organizer_division_id

        LEFT JOIN Zone z
            ON z.zonal_id =
                a.organizer_zonal_id

        WHERE a.deleted_at IS NULL

          AND (
                {$recipientSql}
                OR
                {$managerSql}
          )
    ";


    if (
        !empty($filters['status'])
        && in_array(
            $filters['status'],
            ['Draft', 'Published', 'Retracted', 'Archived'],
            true
        )
    ) {
        $sql .= "
            AND a.status = ?
        ";

        $params[] =
            $filters['status'];
    }


    if (
        !empty($filters['level'])
        && in_array(
            $filters['level'],
            self::VALID_LEVELS,
            true
        )
    ) {
        $sql .= "
            AND a.level = ?
        ";

        $params[] =
            $filters['level'];
    }


    if (
        !empty($filters['priority'])
        && in_array(
            $filters['priority'],
            ['Normal', 'Urgent'],
            true
        )
    ) {
        $sql .= "
            AND a.priority = ?
        ";

        $params[] =
            $filters['priority'];
    }


    if (!empty($filters['search'])) {

        $search =
            '%' .
            trim($filters['search'])
            . '%';

        $sql .= "
            AND (
                a.title LIKE ?
                OR a.body LIKE ?
                OR a.category LIKE ?
            )
        ";

        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }


    $sql .= "
        ORDER BY

            CASE
                WHEN a.priority = 'Urgent'
                    THEN 0
                ELSE 1
            END,

            CASE
                WHEN a.status = 'Draft'
                    THEN 1
                ELSE 0
            END,

            COALESCE(
                a.published_at,
                a.created_at
            ) DESC,

            a.created_at DESC
    ";


    return $this->resultSet(
        $sql,
        $params
    );
}

    /**
     * Find all announcements belonging to a manager's own scope.
     */
    public function findManaged(
        $level,
        $scopeId = null,
        array $filters = []
    ) {
        [$scopeSql, $scopeParams] =
            $this->managerScopeCondition(
                $level,
                $scopeId
            );

        $sql = "
            SELECT
                a.*,

                CONCAT(
                    u.first_name,
                    ' ',
                    u.last_name
                ) AS creator_name,

                u.role
                    AS creator_role,

                (
                    SELECT COUNT(*)
                    FROM AnnouncementAttachment att
                    WHERE att.announcement_id =
                        a.announcement_id
                ) AS attachment_count,

                (
                    SELECT GROUP_CONCAT(
                        aa.target_role
                        ORDER BY aa.target_role
                        SEPARATOR ','
                    )
                    FROM AnnouncementAudience aa
                    WHERE aa.announcement_id =
                        a.announcement_id
                ) AS target_roles_csv

            FROM Announcement a

            JOIN User u
                ON a.created_by =
                    u.user_id

            WHERE a.deleted_at IS NULL
              AND {$scopeSql}
        ";

        $params =
            $scopeParams;

        if (
            !empty($filters['status'])
            && in_array(
                $filters['status'],
                ['Draft', 'Published', 'Retracted', 'Archived'],
                true
            )
        ) {
            $sql .= "
                AND a.status = ?
            ";

            $params[] =
                $filters['status'];
        }

        if (
            !empty($filters['priority'])
            && in_array(
                $filters['priority'],
                ['Normal', 'Urgent'],
                true
            )
        ) {
            $sql .= "
                AND a.priority = ?
            ";

            $params[] =
                $filters['priority'];
        }

        if (!empty($filters['search'])) {
            $search =
                '%' .
                trim($filters['search'])
                . '%';

            $sql .= "
                AND (
                    a.title LIKE ?
                    OR a.body LIKE ?
                    OR a.category LIKE ?
                )
            ";

            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= "
            ORDER BY
                CASE
                    WHEN a.status = 'Draft'
                        THEN 1
                    ELSE 0
                END,

                COALESCE(
                    a.published_at,
                    a.created_at
                ) DESC
        ";

        return $this->resultSet(
            $sql,
            $params
        );
    }

    /**
     * Count announcements managed by this scope.
     */
    public function countManagedByStatus(
        $level,
        $scopeId = null
    ) {
        [$scopeSql, $scopeParams] =
            $this->managerScopeCondition(
                $level,
                $scopeId
            );

        $sql = "
            SELECT
                SUM(
                    CASE
                        WHEN status = 'Published'
                            THEN 1
                        ELSE 0
                    END
                ) AS total_published,

                SUM(
                    CASE
                        WHEN status = 'Draft'
                            THEN 1
                        ELSE 0
                    END
                ) AS total_drafts,

                COUNT(*)
                    AS total_all

            FROM Announcement a

            WHERE a.deleted_at IS NULL
              AND {$scopeSql}
        ";

        $result =
            $this->single(
                $sql,
                $scopeParams
            );

        return [
            'Published' =>
                (int)(
                    $result->total_published
                    ?? 0
                ),

            'Draft' =>
                (int)(
                    $result->total_drafts
                    ?? 0
                ),

            'All' =>
                (int)(
                    $result->total_all
                    ?? 0
                ),
        ];
    }

    /**
     * Increment Published announcement view count.
     */
    public function incrementViewCount($id)
    {
        $this->query(
            "
            UPDATE Announcement
            SET view_count =
                view_count + 1
            WHERE announcement_id = ?
              AND deleted_at IS NULL
              AND status = 'Published'
            ",
            [(int)$id]
        );
    }
}
