<?php

/**
 * Shared read-only aggregates for zonal overviews. All methods take an
 * explicit zone scope id; user/session handling stays in controllers.
 * Mirrors ClubOverview (club tier) for the zonal tier.
 */
final class ZoneOverview {

    public static function zoneName(Controller $controller, int $zonalId): string {
        $zone = $controller->model('ZoneFundModel')->getZone($zonalId);
        return $zone->zonal_name ?? 'Zone';
    }

    public static function upcoming(Controller $controller, int $zonalId): array {
        $statusMap = [
            'PendingApproval' => 'Pending Approval',
            'Approved'        => 'Approved',
            'Completed'       => 'Completed',
            'Rejected'        => 'Changes Requested',
        ];
        $upcoming = [];
        foreach ($controller->model('EventModel')->getZonalEvents($zonalId) as $ev) {
            $start = strtotime((string) $ev->start_datetime);
            if (!$start || $start < time()) {
                continue;
            }
            $upcoming[] = [
                'title'      => $ev->title ?? '',
                'date'       => date('M d, Y', $start),
                'time'       => date('g:i A', $start),
                'location'   => $ev->location ?? '—',
                'status'     => $statusMap[$ev->status] ?? $ev->status,
                'status_key' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $ev->status ?? '')),
                'sort'       => $start,
            ];
        }
        usort($upcoming, static fn($a, $b) => $a['sort'] <=> $b['sort']);
        return array_slice(array_map(static function ($row) {
            unset($row['sort']);
            return $row;
        }, $upcoming), 0, 3);
    }

    public static function eventCount(Controller $controller, int $zonalId): int {
        return count($controller->model('EventModel')->getZonalEvents($zonalId));
    }

    public static function announcements(Controller $controller, int $userId, string $role, $divisionId, $zonalId): array {
        try {
            $rows = $controller->model('AnnouncementModel')->findForUser($userId, $role, null, $divisionId, $zonalId);
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
                'scope'   => $row->level ?? 'Zonal',
                'age'     => ClubOverview::ageLabel($ts),
                'is_new'  => $id > 0 ? !$readModel->hasRead($id, $userId) : false,
            ];
        }
        return $out;
    }

    public static function attendance(Controller $controller, int $zonalId): array {
        return $controller->model('ZoneMonitorModel')->getZoneAttendance($zonalId);
    }

    /**
     * Bell + badge data for zonal shells: unread published announcements for
     * the session user (count) plus the latest few (id/title/age) linking to
     * the unified detail view. Reads the session; controllers stay one-liners.
     *
     * @return array{count: int, items: list<array{id: int, title: string, age: string}>}
     */
    public static function headerNotifications(Controller $controller): array {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId < 1) {
            return ['count' => 0, 'items' => []];
        }
        $scope = self::effectiveScope($controller, $userId);
        try {
            $rows = $controller->model('AnnouncementModel')->findForUser(
                $userId,
                (string) ($_SESSION['user_role'] ?? ''),
                $scope['club_id'],
                $scope['division_id'],
                $scope['zonal_id']
            );
        } catch (Throwable $e) {
            return ['count' => 0, 'items' => []];
        }
        $readModel = $controller->model('AnnouncementReadModel');
        $count = 0;
        $items = [];
        foreach (is_array($rows) ? $rows : [] as $a) {
            $row = is_array($a) ? (object) $a : $a;
            if (($row->status ?? '') !== 'Published') {
                continue;
            }
            $id = (int) ($row->announcement_id ?? $row->id ?? 0);
            if ($id < 1 || $readModel->hasRead($id, $userId)) {
                continue;
            }
            $count++;
            if (count($items) < 3) {
                $raw = $row->published_at ?? $row->created_at ?? null;
                $items[] = [
                    'id' => $id,
                    'title' => (string) ($row->title ?? ''),
                    'age' => ClubOverview::ageLabel($raw ? strtotime((string) $raw) : false),
                ];
            }
        }
        return ['count' => $count, 'items' => $items];
    }

    /**
     * Announcement scope for one user, resolved through the DB hierarchy
     * (club -> division -> zone), not the raw session ids.
     *
     * Why: club members usually carry only club_id in the session, so the
     * raw-session query silently drops every zonal/divisional announcement
     * and the bell disagrees with the announcements list. Session values
     * survive only as a fallback when the user row cannot be loaded.
     *
     * @return array{club_id: ?int, division_id: ?int, zonal_id: ?int}
     */
    public static function effectiveScope(Controller $controller, int $userId): array {
        $fallback = [
            'club_id' => isset($_SESSION['club_id']) ? (int) $_SESSION['club_id'] : null,
            'division_id' => isset($_SESSION['division_id']) ? (int) $_SESSION['division_id'] : null,
            'zonal_id' => isset($_SESSION['zonal_id']) ? (int) $_SESSION['zonal_id'] : null,
        ];
        try {
            $viewer = $controller->model('UserModel')->findByUserIdWithHierarchy($userId);
        } catch (Throwable $e) {
            return $fallback;
        }
        if (!$viewer) {
            return $fallback;
        }
        $clubId = (int) ($viewer->club_id ?? 0);
        $divisionId = (int) ($viewer->effective_division_id ?? $viewer->division_id ?? 0);
        $zonalId = (int) ($viewer->effective_zonal_id ?? $viewer->zonal_id ?? 0);
        return [
            'club_id' => $clubId > 0 ? $clubId : null,
            'division_id' => $divisionId > 0 ? $divisionId : null,
            'zonal_id' => $zonalId > 0 ? $zonalId : null,
        ];
    }

    public static function funds(Controller $controller, int $zonalId): array {
        return $controller->model('ZoneFundModel')->getStats($zonalId);
    }
}
