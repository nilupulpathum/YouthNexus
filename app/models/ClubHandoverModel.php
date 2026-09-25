<?php

/**
 * Club leadership handover (D6). Atomic demote-outgoing/promote-incoming
 * plus HandoverLog row, all scoped to the president's own club.
 */
class ClubHandoverModel extends Model {

    public function getLog(int $clubId): array {
        return $this->resultSet(
            "SELECT h.*,
                    CONCAT_WS(' ', o.first_name, o.last_name) AS outgoing_name,
                    CONCAT_WS(' ', i.first_name, i.last_name) AS incoming_name
             FROM HandoverLog h
             JOIN User o ON o.user_id = h.outgoing_user_id
             JOIN User i ON i.user_id = h.incoming_user_id
             WHERE h.club_id = ?
             ORDER BY h.created_at DESC",
            [$clubId]
        );
    }

    /**
     * Confirm a handover. Throws on any rule violation.
     * Returns the new handover_id.
     */
    public function confirm(int $clubId, int $outgoingId, int $incomingId, array $checkedItems): int {
        if ($incomingId < 1 || $incomingId === $outgoingId) {
            throw new InvalidArgumentException('Select a valid successor from the roster.');
        }
        $incoming = $this->single(
            "SELECT user_id, role FROM User
             WHERE user_id = ? AND club_id = ? AND status = 'Active'
               AND COALESCE(membership_status, 'Active') = 'Active' LIMIT 1",
            [$incomingId, $clubId]
        );
        if (!$incoming) {
            throw new InvalidArgumentException('Successor not found in your club.');
        }
        if ($incoming->role === 'ClubPresident') {
            throw new InvalidArgumentException('The successor already holds the presidency.');
        }
        $outgoing = $this->single(
            "SELECT user_id FROM User
             WHERE user_id = ? AND club_id = ? AND role = 'ClubPresident' AND status = 'Active' LIMIT 1",
            [$outgoingId, $clubId]
        );
        if (!$outgoing) {
            throw new InvalidArgumentException('Only the sitting president can hand over.');
        }

        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            $demote = $pdo->prepare(
                "UPDATE User SET role = 'ClubMember'
                 WHERE user_id = ? AND club_id = ? AND role = 'ClubPresident'"
            );
            $demote->execute([$outgoingId, $clubId]);
            if ($demote->rowCount() !== 1) {
                throw new RuntimeException('The presidency changed while you were confirming.');
            }
            $promote = $pdo->prepare(
                "UPDATE User SET role = 'ClubPresident'
                 WHERE user_id = ? AND club_id = ? AND status = 'Active'"
            );
            $promote->execute([$incomingId, $clubId]);
            if ($promote->rowCount() !== 1) {
                throw new RuntimeException('The successor could not be promoted.');
            }
            $log = $pdo->prepare(
                "INSERT INTO HandoverLog (club_id, outgoing_user_id, incoming_user_id, asset_checklist)
                 VALUES (?, ?, ?, ?)"
            );
            $log->execute([$clubId, $outgoingId, $incomingId, json_encode(array_values(array_unique(array_map('intval', $checkedItems))))]);
            $handoverId = (int) $pdo->lastInsertId();
            $audit = $pdo->prepare(
                "INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, details)
                 VALUES (?, 'HANDOVER', 'User', ?, ?)"
            );
            $audit->execute([$outgoingId, $incomingId, "Presidency handed over (log {$handoverId})"]);
            $pdo->commit();
            return $handoverId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
