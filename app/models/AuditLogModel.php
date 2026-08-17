<?php

class AuditLogModel extends Model {

    /**
     * Log an action.
     * @param int    $actorUserId
     * @param string $actionType   e.g. 'SUBMIT_CLUB_APPLICATION', 'APPROVE_CLUB', 'REJECT_CLUB'
     * @param string $targetEntity e.g. 'ClubApplication'
     * @param int    $targetId
     * @param string $details
     * @return bool
     */
    public function logAction($actorUserId, $actionType, $targetEntity, $targetId, $details = '') {
        $this->query(
            "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
             VALUES (?, ?, ?, ?, ?)",
            [$actorUserId, $actionType, $targetEntity, $targetId, $details]
        );
        return true;
    }

    /**
     * Get audit log entries for a specific target.
     * @param string $targetEntity
     * @param int    $targetId
     * @return array
     */
    public function getByTarget($targetEntity, $targetId) {
        return $this->resultSet(
            "SELECT a.*, u.first_name, u.last_name
             FROM AuditLog a
             JOIN User u ON a.actor_user_id = u.user_id
             WHERE a.target_entity = ? AND a.target_id = ?
             ORDER BY a.timestamp DESC",
            [$targetEntity, $targetId]
        );
    }

    /**
     * Get recent audit log entries.
     * @param int $limit
     * @return array
     */
    public function getRecent($limit = 50) {
        return $this->resultSet(
            "SELECT a.*, u.first_name, u.last_name
             FROM AuditLog a
             JOIN User u ON a.actor_user_id = u.user_id
             ORDER BY a.timestamp DESC
             LIMIT ?",
            [$limit]
        );
    }
}
