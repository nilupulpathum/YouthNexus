<?php

class AuditLogModel extends Model {

    public function log($actorUserId, $actionType, $targetEntity, $targetId, $details = '') {
        $this->query(
            "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
             VALUES (?, ?, ?, ?, ?)",
            [$actorUserId, $actionType, $targetEntity, $targetId, $details]
        );
    }

    public function findForTarget($targetEntity, $targetId) {
        return $this->resultSet(
            "SELECT l.*, CONCAT(u.first_name, ' ', u.last_name) AS actor_name
             FROM AuditLog l
             JOIN User u ON u.user_id = l.actor_user_id
             WHERE l.target_entity = ? AND l.target_id = ?
             ORDER BY l.timestamp ASC",
            [$targetEntity, $targetId]
        );
    }
}
