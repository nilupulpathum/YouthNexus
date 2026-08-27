<?php

class ClubFlagModel extends Model {

    protected $table = 'ClubFlag';

    public function create($clubId, $flaggedBy, $role, $severity, $comment) {
        try {
            $data = [
                'club_id'         => $clubId,
                'flagged_by'      => $flaggedBy,
                'flagged_by_role' => $role,
                'severity'        => $severity,
                'comment'         => $comment,
                'status'          => 'PendingReview'
            ];
            
            $this->insert($data);
            
            // Update Club.flagged to true
            $query = "UPDATE Club SET flagged = 1 WHERE club_id = :club_id";
            $this->query($query, ['club_id' => $clubId]);
            
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function findByClub($clubId) {
        try {
            $query = "
                SELECT 
                    cf.*,
                    CONCAT(u.first_name, ' ', SUBSTRING(u.last_name, 1, 1), '.') as flagged_by_name
                FROM ClubFlag cf
                JOIN User u ON cf.flagged_by = u.user_id
                WHERE cf.club_id = :club_id
                ORDER BY cf.flagged_at DESC
            ";
            $res = $this->query($query, ['club_id' => $clubId]);
            return is_array($res) ? $res : [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
