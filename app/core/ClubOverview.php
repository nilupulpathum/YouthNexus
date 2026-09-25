<?php

/**
 * Shared read-only aggregates for club overviews (D7). All methods take an
 * explicit club scope id; user/session handling stays in controllers.
 */
final class ClubOverview {

    public static function upcoming(Controller $controller, int $clubId): array {
        $statusMap = [
            'PendingApproval' => 'Pending Approval',
            'Approved'        => 'Approved',
            'Completed'       => 'Completed',
            'Rejected'        => 'Changes Requested',
        ];
        $upcoming = [];
        foreach ($controller->model('EventModel')->getClubEvents($clubId) as $ev) {
            $start = strtotime((string) $ev->start_datetime);
            $upcoming[] = [
                'title'      => $ev->title ?? '',
                'date'       => $start ? date('M d, Y', $start) : '—',
                'location'   => $ev->location ?? '—',
                'scope'      => 'Club',
                'status'     => $statusMap[$ev->status] ?? $ev->status,
                'status_key' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $ev->status ?? '')),
                'sort'       => $start ?: 0,
            ];
            if (count($upcoming) >= 6) {
                break;
            }
        }
        usort($upcoming, static fn($a, $b) => $b['sort'] <=> $a['sort']);
        return array_slice(array_map(static function ($row) {
            unset($row['sort']);
            return $row;
        }, $upcoming), 0, 3);
    }

    public static function announcements(Controller $controller, int $clubId, int $userId, string $role, $divisionId, $zonalId): array {
        try {
            $rows = $controller->model('AnnouncementModel')->findForUser($userId, $role, $clubId, $divisionId, $zonalId);
        } catch (Throwable $e) {
            return [];
        }
        $readModel = $controller->model('AnnouncementReadModel');
        $out = [];
        foreach (array_slice(is_array($rows) ? $rows : [], 0, 2) as $a) {
            $row = is_array($a) ? (object) $a : $a;
            $id = (int) ($row->announcement_id ?? $row->id ?? 0);
            $raw = $row->published_at ?? $row->created_at ?? null;
            $ts = $raw ? strtotime((string) $raw) : false;
            $out[] = [
                'title'   => $row->title ?? '',
                'summary' => mb_substr(trim(strip_tags((string) ($row->body ?? ''))), 0, 140),
                'scope'   => $row->level ?? $row->category ?? 'Club',
                'age'     => self::ageLabel($ts),
                'is_new'  => $id > 0 ? !$readModel->hasRead($id, $userId) : false,
            ];
        }
        return $out;
    }

    public static function completedCount(Controller $controller, int $clubId): int {
        $completed = 0;
        foreach ($controller->model('EventModel')->getClubEvents($clubId) as $ev) {
            if ($ev->status === 'Completed') {
                $completed++;
            }
        }
        return $completed;
    }

    public static function ageLabel($ts): string {
        if (!$ts) {
            return '';
        }
        $days = (int) floor((time() - $ts) / 86400);
        if ($days <= 0) {
            return 'Today';
        }
        if ($days === 1) {
            return 'Yesterday';
        }
        if ($days < 7) {
            return $days . ' days ago';
        }
        if ($days < 30) {
            $weeks = (int) floor($days / 7);
            return $weeks . ($weeks === 1 ? ' week ago' : ' weeks ago');
        }
        return date('M d, Y', $ts);
    }
}
