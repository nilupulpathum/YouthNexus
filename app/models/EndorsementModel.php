<?php

/**
 * EndorsementModel — Social CV endorsements (OUTSIDE item 1 follow-up).
 *
 * One endorsement per (member, endorser) pair, enforced by the UNIQUE key;
 * re-endorsing revises the text. Eligibility (role + scope) lives in the
 * controller, which also audit-logs writes.
 */
class EndorsementModel extends Model {

    /**
     * Latest endorsements received by one member, with the endorser's
     * current display name. Role shown is the snapshot taken at write time.
     */
    public function forMember($memberId, $limit = 6) {
        $rows = $this->resultSet(
            "SELECT en.endorsement_id, en.member_user_id, en.endorser_user_id,
                    en.endorser_role, en.text, en.created_at,
                    TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS endorser_name
             FROM Endorsement en
             JOIN User u ON en.endorser_user_id = u.user_id
             WHERE en.member_user_id = ?
             ORDER BY en.created_at DESC
             LIMIT " . max(1, (int) $limit),
            [(int) $memberId]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id'      => (int) $row->endorsement_id,
                'endorser_user_id' => (int) $row->endorser_user_id,
                'name'    => trim((string) ($row->endorser_name ?? '')) !== ''
                    ? trim((string) $row->endorser_name)
                    : 'YouthNexus User',
                'role'    => (string) ($row->endorser_role ?? ''),
                'text'    => (string) ($row->text ?? ''),
                'date'    => $row->created_at ? date('M Y', strtotime((string) $row->created_at)) : '',
            ];
        }
        return $out;
    }

    public function findById($endorsementId) {
        return $this->single(
            "SELECT * FROM Endorsement WHERE endorsement_id = ? LIMIT 1",
            [(int) $endorsementId]
        );
    }

    /**
     * Write (or revise) an endorsement. Returns true on success.
     */
    public function upsert($memberId, $endorserId, $endorserRole, $text) {
        $this->query(
            "INSERT INTO Endorsement (member_user_id, endorser_user_id, endorser_role, text)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                endorser_role = VALUES(endorser_role),
                text          = VALUES(text),
                created_at    = CURRENT_TIMESTAMP",
            [(int) $memberId, (int) $endorserId, (string) $endorserRole, (string) $text]
        );
        return true;
    }

    /**
     * Delete one endorsement row. Returns affected rows.
     */
    public function delete($endorsementId) {
        return $this->rowCount(
            "DELETE FROM Endorsement WHERE endorsement_id = ?",
            [(int) $endorsementId]
        );
    }
}
