<?php

class AnnouncementModel extends Model {

    /** Lock an owned announcement before changing its content or publication state. */
    public function findEditable($id, $divisionId, $userId, $lock = false) {
        $sql = "SELECT * FROM Announcement WHERE announcement_id = ?
                AND organizer_division_id = ? AND created_by = ? AND status IN ('Draft', 'Published')";
        return $this->single($sql . ($lock ? ' FOR UPDATE' : ''), [(int)$id, (int)$divisionId, (int)$userId]);
    }

    /**
     * Create a new announcement (Draft or Published).
     *
     * @param  array $data
     * @return int   Inserted announcement_id
     */
    public function create(array $data) {
        $status = $data['status'] ?? 'Draft';
        $publishedAt = ($status === 'Published') ? date('Y-m-d H:i:s') : null;

        $sql = "INSERT INTO Announcement (
                    title, body, level, organizer_division_id,
                    target_audience, category, priority, status,
                    view_count, created_by, published_at, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW())";

        $params = [
            $data['title'],
            $data['body'],
            $data['level'] ?? 'Divisional',
            $data['organizer_division_id'] ?? null,
            $data['target_audience'] ?? null,
            $data['category'] ?? null,
            $data['priority'] ?? 'Normal',
            $status,
            $data['created_by'],
            $publishedAt,
        ];

        $this->query($sql, $params);
        return (int)Database::getInstance()->getConnection()->lastInsertId();
    }

    /** Content edits preserve the original creation/publication dates and status. */
    public function updateContent($id, $divisionId, $userId, array $data) {
        $this->query("UPDATE Announcement SET title = ?, body = ?, target_audience = ?,
            category = ?, priority = ?, content_edited_at = NOW()
            WHERE announcement_id = ? AND organizer_division_id = ? AND created_by = ?
            AND status IN ('Draft', 'Published')", [
                $data['title'], $data['body'], $data['target_audience'], $data['category'],
                $data['priority'], (int)$id, (int)$divisionId, (int)$userId,
            ]);
    }

    /**
     * Publish an existing draft announcement.
     *
     * @param  int   $id
     * @param  int   $divisionId
     * @param  int   $userId
     * @param  array $data
     * @return bool
     */
    public function publish($id, $divisionId, $userId, array $data) {
        $sql = "UPDATE Announcement SET
                    title = ?,
                    body = ?,
                    target_audience = ?,
                    category = ?,
                    priority = ?,
                    status = 'Published',
                    published_at = NOW()
                WHERE announcement_id = ?
                  AND organizer_division_id = ?
                  AND created_by = ?
                  AND status = 'Draft'";

        $params = [
            $data['title'],
            $data['body'],
            $data['target_audience'],
            $data['category'] ?? null,
            $data['priority'] ?? 'Normal',
            (int)$id,
            (int)$divisionId,
            (int)$userId,
        ];

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Find announcement details by ID with creator info.
     *
     * @param  int $id
     * @return object|false
     */
    public function findById($id) {
        $sql = "SELECT 
                    a.*,
                    d.division_name AS organizer_division_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS posted_by_name,
                    u.role AS posted_by_role,
                    u.email AS posted_by_email
                FROM Announcement a
                LEFT JOIN Division d ON a.organizer_division_id = d.division_id
                JOIN User u ON a.created_by = u.user_id
                WHERE a.announcement_id = ?
                LIMIT 1";

        return $this->single($sql, [(int)$id]);
    }

    /**
     * Find all announcements for a division with filtering and search.
     *
     * @param  int   $divisionId
     * @param  array $filters
     * @return array
     */
    public function findByDivision($divisionId, array $filters = []) {
        $sql = "SELECT 
                    a.*,
                    CONCAT(u.first_name, ' ', u.last_name) AS creator_name,
                    u.role AS creator_role,
                    (SELECT COUNT(*) FROM AnnouncementAttachment att WHERE att.announcement_id = a.announcement_id) AS attachment_count
                FROM Announcement a
                JOIN User u ON a.created_by = u.user_id
                WHERE a.organizer_division_id = :division_id
                  AND (a.status = 'Published' OR a.created_by = :draft_owner_id)";

        $params = ['division_id' => (int)$divisionId, 'draft_owner_id' => (int)($filters['draft_owner_id'] ?? 0)];

        // Filter: Status
        if (!empty($filters['status'])) {
            $sql .= " AND a.status = :status";
            $params['status'] = $filters['status'];
        }

        // Filter: Target Audience
        if (!empty($filters['target_audience'])) {
            $sql .= " AND a.target_audience = :target_audience";
            $params['target_audience'] = $filters['target_audience'];
        }

        // Filter: Priority
        if (!empty($filters['priority'])) {
            $sql .= " AND a.priority = :priority";
            $params['priority'] = $filters['priority'];
        }

        // Filter: Free-text search
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (a.title LIKE :s_title OR a.body LIKE :s_body)";
            $params['s_title'] = $search;
            $params['s_body']  = $search;
        }

        $sql .= " ORDER BY CASE WHEN a.published_at IS NULL THEN 1 ELSE 0 END, a.published_at DESC, a.created_at DESC";

        return $this->resultSet($sql, $params);
    }

    /**
     * Increment view count for an announcement.
     *
     * @param  int $id
     * @return void
     */
    public function incrementViewCount($id) {
        $sql = "UPDATE Announcement SET view_count = view_count + 1 WHERE announcement_id = ?";
        $this->query($sql, [(int)$id]);
    }

    /**
     * Count announcements by status for a division.
     *
     * @param  int $divisionId
     * @return array
     */
    public function countByStatus($divisionId, $draftOwnerId = 0) {
        $sql = "SELECT 
                    SUM(CASE WHEN status = 'Published' THEN 1 ELSE 0 END) AS total_published,
                    SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) AS total_drafts,
                    COUNT(*) AS total_all
                FROM Announcement
                WHERE organizer_division_id = ? AND (status = 'Published' OR created_by = ?)";

        $res = $this->single($sql, [(int)$divisionId, (int)$draftOwnerId]);
        return [
            'Published' => (int)($res->total_published ?? 0),
            'Draft'     => (int)($res->total_drafts ?? 0),
            'All'       => (int)($res->total_all ?? 0),
        ];
    }
}
