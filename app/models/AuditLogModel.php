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

    // -----------------------------------------------------------------
    // RECONCILIATION with feat-club_registration — same purpose, different
    // naming. Kept as aliases so either branch's controller works here.
    // -----------------------------------------------------------------

    /** Alias of log() — matches feat-club_registration's naming. */
    public function logAction($actorUserId, $actionType, $targetEntity, $targetId, $details = '') {
        $this->log($actorUserId, $actionType, $targetEntity, $targetId, $details);
        return true;
    }

    /** Alias of findForTarget() — matches feat-club_registration's naming. */
    public function getByTarget($targetEntity, $targetId) {
        return $this->findForTarget($targetEntity, $targetId);
    }

    public function getRecent($limit = 50) {
        return $this->resultSet(
            "SELECT a.*, u.first_name, u.last_name FROM AuditLog a
             JOIN User u ON a.actor_user_id = u.user_id
             ORDER BY a.timestamp DESC LIMIT ?",
            [$limit]
        );
    }
}
