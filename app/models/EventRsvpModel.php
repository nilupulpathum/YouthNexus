<?php

/**
 * EventRsvpModel — member event responses (OUTSIDE item 6 follow-up).
 *
 * One response per (member, event) pair, enforced by the UNIQUE key;
 * re-responding revises the row. Eligibility (event visible to the
 * requester, still respondable) lives in the controller.
 */
class EventRsvpModel extends Model {

    /**
     * This viewer's responses for a set of events, keyed by event id.
     *
     * @return array<int, string>  event_id => 'attending'|'declined'
     */
    public function responsesForEvents($userId, array $eventIds) {
        $ids = array_values(array_unique(array_map('intval', $eventIds)));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->resultSet(
            "SELECT event_id, response FROM EventRsvp
             WHERE user_id = ? AND event_id IN ({$placeholders})",
            array_merge([(int) $userId], $ids)
        );
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $out[(int) $row->event_id] = (string) $row->response;
        }
        return $out;
    }

    /**
     * Write (or revise) a response. Returns true on success.
     */
    public function upsert($userId, $eventId, $response) {
        $this->query(
            "INSERT INTO EventRsvp (user_id, event_id, response)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                response = VALUES(response)",
            [(int) $userId, (int) $eventId, (string) $response]
        );
        return true;
    }

    /**
     * Clear a response (toggle back to undecided). Returns affected rows.
     */
    public function deleteByPair($userId, $eventId) {
        return $this->rowCount(
            "DELETE FROM EventRsvp WHERE user_id = ? AND event_id = ?",
            [(int) $userId, (int) $eventId]
        );
    }
}
