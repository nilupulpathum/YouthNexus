<?php

class AnnouncementAudienceModel extends Model
{
    public function replaceRoles($announcementId, array $roles)
    {
        $announcementId = (int)$announcementId;

        $this->query(
            "DELETE FROM AnnouncementAudience
             WHERE announcement_id = ?",
            [$announcementId]
        );

        $roles = array_values(array_unique($roles));

        foreach ($roles as $role) {
            $this->query(
                "INSERT INTO AnnouncementAudience
                    (announcement_id, target_role)
                 VALUES (?, ?)",
                [$announcementId, $role]
            );
        }
    }

    public function findRoles($announcementId)
    {
        $rows = $this->resultSet(
            "SELECT target_role
             FROM AnnouncementAudience
             WHERE announcement_id = ?
             ORDER BY target_role",
            [(int)$announcementId]
        );

        $roles = [];

        foreach ($rows as $row) {
            $roles[] = $row->target_role;
        }

        return $roles;
    }

    public function isRoleTargeted($announcementId, $role)
    {
        return (bool)$this->single(
            "SELECT audience_id
             FROM AnnouncementAudience
             WHERE announcement_id = ?
               AND target_role = ?
             LIMIT 1",
            [(int)$announcementId, $role]
        );
    }
}