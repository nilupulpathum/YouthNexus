<?php

/**
 * SkillModel — Social CV competency badges (OUTSIDE item 1 follow-up).
 *
 * No award workflow: levels derive live from attended events per category,
 * so badges can never name a category the member did not earn.
 * Thresholds (owner-approved 2026-09-26): Bronze 1-2, Silver 3-5, Gold 6+.
 */
class SkillModel extends Model {

    const BRONZE_MIN = 1;
    const SILVER_MIN = 3;
    const GOLD_MIN = 6;

    public static function levelForCount($count) {
        $count = (int) $count;
        if ($count >= self::GOLD_MIN) {
            return 'Gold';
        }
        if ($count >= self::SILVER_MIN) {
            return 'Silver';
        }
        if ($count >= self::BRONZE_MIN) {
            return 'Bronze';
        }
        return '';
    }

    /**
     * Full skill catalog (for admin/reference use).
     */
    public function catalog() {
        return $this->resultSet("SELECT skill_id, name, icon FROM Skill ORDER BY name");
    }

    /**
     * Earned badges for one member: attended (Present) club events grouped
     * by live event type, joined to the Skill catalog. Rows without a
     * catalog entry are skipped, never invented.
     *
     * @return array  each: name, level, events, icon
     */
    public function forMember($clubId, $userId) {
        $rows = $this->resultSet(
            "SELECT s.name, s.icon, COUNT(*) AS events_count
             FROM Attendance a
             JOIN Event e ON a.event_id = e.event_id
             JOIN Skill s ON s.name = e.event_type
             WHERE a.user_id = ? AND e.organizer_club_id = ? AND a.status = 'Present'
             GROUP BY s.skill_id, s.name, s.icon
             ORDER BY events_count DESC, s.name",
            [(int) $userId, (int) $clubId]
        );
        $badges = [];
        foreach ($rows as $row) {
            $level = self::levelForCount($row->events_count ?? 0);
            if ($level === '') {
                continue;
            }
            $badges[] = [
                'name'   => (string) $row->name,
                'level'  => $level,
                'events' => (int) $row->events_count,
                'icon'   => (string) ($row->icon ?? 'check'),
            ];
        }
        return $badges;
    }
}
