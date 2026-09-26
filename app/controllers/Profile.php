<?php

class Profile extends Controller {

    /**
     * Roles allowed to write endorsements (owner-approved 2026-09-26):
     * president/secretary tiers only, always within their own scope.
     */
    const ENDORSABLE_ROLES = [
        'ClubPresident', 'ClubSecretary',
        'ZonalCoordinator', 'ZonalSecretary',
        'DivisionalCoordinator', 'DivisionalSecretary',
        'NYSCAdministrator',
    ];

    /**
     * Viewer-first role labels (DB role => CV label).
     */
    private function cvRoleLabel($role) {
        $map = [
            'ClubPresident'         => 'President',
            'ClubSecretary'         => 'Secretary',
            'ClubTreasurer'         => 'Treasurer',
            'ClubMember'            => 'Member',
            'Member'                => 'Member',
            'DivisionalCoordinator' => 'Divisional Coordinator',
            'DivisionalSecretary'   => 'Divisional Secretary',
            'DivisionalTreasurer'   => 'Divisional Treasurer',
            'ZonalCoordinator'      => 'Zonal Coordinator',
            'ZonalSecretary'        => 'Zonal Secretary',
            'ZonalTreasurer'        => 'Zonal Treasurer',
            'NYSCAdministrator'     => 'NYSC Administrator',
        ];
        return $map[$role] ?? (string) $role;
    }

    /**
     * Whole hours between two datetimes, or null when not computable.
     * Standing rule: volunteer hours = event duration x attendance
     * (callers only pass attended events, so attendance = 1).
     */
    private function cvEventHours($start, $end) {
        $from = $start ? strtotime((string) $start) : false;
        $to = $end ? strtotime((string) $end) : false;
        if (!$from || !$to || $to <= $from) {
            return null;
        }
        return round(($to - $from) / 3600, 1);
    }

    private function cvHoursLabel($hours) {
        if ($hours === null) {
            return '';
        }
        $text = rtrim(rtrim(number_format($hours, 1), '0'), '.');
        return $text . 'h';
    }

    private function verifyCsrf() {
        $token = (string) ($_POST['csrf_token'] ?? '');
        return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function setFlash($type, $message) {
        $_SESSION['profile_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash() {
        $flash = $_SESSION['profile_flash'] ?? null;
        unset($_SESSION['profile_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function effectiveDivision($row) {
        return (int) ($row->effective_division_id ?? $row->division_id ?? 0);
    }

    private function effectiveZonal($row) {
        return (int) ($row->effective_zonal_id ?? $row->zonal_id ?? 0);
    }

    /**
     * Scope visibility: NYSC sees anyone; club viewers see own-club members;
     * zonal/divisional viewers see their own scope.
     */
    private function inSameScope($viewer, $target) {
        $viewerRole = (string) ($viewer->role ?? '');
        if ($viewerRole === 'NYSCAdministrator') {
            return true;
        }
        $viewerClub = (int) ($viewer->club_id ?? 0);
        if ($viewerClub > 0) {
            return (int) ($target->club_id ?? 0) === $viewerClub;
        }
        if (str_starts_with($viewerRole, 'Zonal')) {
            $zonalId = $this->effectiveZonal($viewer);
            return $zonalId > 0 && $this->effectiveZonal($target) === $zonalId;
        }
        if (str_starts_with($viewerRole, 'Divisional')) {
            $divisionId = $this->effectiveDivision($viewer);
            return $divisionId > 0 && $this->effectiveDivision($target) === $divisionId;
        }
        return false;
    }

    /**
     * Endorse eligibility: privileged role, in scope, never yourself.
     */
    private function canEndorseTarget($viewer, $target) {
        if ((int) $viewer->user_id === (int) $target->user_id) {
            return false;
        }
        if (!in_array((string) ($viewer->role ?? ''), self::ENDORSABLE_ROLES, true)) {
            return false;
        }
        return $this->inSameScope($viewer, $target);
    }

    /**
     * Full CV dataset for any visible user. Returns null when the target
     * row does not exist.
     */
    private function buildCvData($targetUserId) {
        $target = $this->model('UserModel')->findByUserIdWithHierarchy((int) $targetUserId);
        if (!$target) {
            return null;
        }
        $targetId = (int) $target->user_id;
        $role = (string) ($target->role ?? 'ClubMember');
        $roleLabel = $this->cvRoleLabel($role);
        $name = trim(trim((string) ($target->first_name ?? '')) . ' ' . trim((string) ($target->last_name ?? '')));
        if ($name === '') {
            $name = 'YouthNexus User';
        }

        // Scope names, only the ones that resolve (no invented geography).
        $clubId = (int) ($target->club_id ?? 0);
        $scopeParts = [];
        if ($clubId > 0) {
            $club = $this->model('ClubModel')->findById($clubId);
            if ($club && trim((string) ($club->club_name ?? '')) !== '') {
                $scopeParts[] = trim((string) $club->club_name);
            }
        }
        $divisionId = $this->effectiveDivision($target);
        if ($divisionId > 0) {
            $division = $this->model('EventModel')->getDivisionById($divisionId);
            if ($division && trim((string) ($division->division_name ?? '')) !== '') {
                $scopeParts[] = trim((string) $division->division_name);
            }
        }
        $zonalId = $this->effectiveZonal($target);
        if ($zonalId > 0) {
            try {
                $zoneName = $this->model('ZoneFundModel')->getZone($zonalId)->zonal_name ?? '';
            } catch (Throwable $e) {
                $zoneName = '';
            }
            if (trim((string) $zoneName) !== '') {
                $scopeParts[] = trim((string) $zoneName);
            }
        }
        $location = $scopeParts ? implode(' · ', $scopeParts) : '—';

        $sinceRaw = $target->membership_date ?? $target->created_at ?? null;
        $sinceTs = $sinceRaw ? strtotime((string) $sinceRaw) : false;
        $memberSince = $sinceTs ? date('M Y', $sinceTs) : '—';
        if ($sinceTs) {
            $years = (int) floor((time() - $sinceTs) / 31556952);
            $yearsActive = $years > 0 ? $years . ' yr' . ($years === 1 ? '' : 's') : '< 1 yr';
        } else {
            $yearsActive = '—';
        }

        // Participation source: attended club events only (club tier, UC24).
        $cvEvents = [];
        $attendedCount = 0;
        $attendanceRate = '—';
        if ($clubId > 0) {
            $cvEvents = $this->model('EventModel')->getMemberCvEvents($clubId, $targetId);
            $attendedCount = count($cvEvents);
            try {
                $summary = $this->model('AttendanceModel')->getClubMemberSummary($clubId, $targetId);
                $attendanceRate = (string) ($summary['rate'] ?? '—');
            } catch (Throwable $e) {
                $attendanceRate = '—';
            }
        }

        $totalHours = 0.0;
        $hoursKnown = false;
        $timeline = [];
        foreach ($cvEvents as $ev) {
            $hours = $this->cvEventHours($ev->start_datetime ?? null, $ev->end_datetime ?? null);
            if ($hours !== null) {
                $hoursKnown = true;
                $totalHours += $hours;
            }
            $ts = strtotime((string) ($ev->start_datetime ?? ''));
            $timeline[] = [
                'title'    => (string) ($ev->title ?? ''),
                'date'     => $ts ? date('M d, Y', $ts) : '—',
                'location' => trim((string) ($ev->location ?? '')) !== '' ? (string) $ev->location : '—',
                'scope'    => 'Club',
                'role'     => '',
                'hours'    => $this->cvHoursLabel($hours),
            ];
        }

        $skills = [];
        if ($clubId > 0) {
            try {
                $skills = $this->model('SkillModel')->forMember($clubId, $targetId);
            } catch (Throwable $e) {
                $skills = [];
            }
        }

        try {
            $endorsements = $this->model('EndorsementModel')->forMember($targetId);
        } catch (Throwable $e) {
            $endorsements = [];
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $headerNotif = ZoneOverview::headerNotifications($this);

        return [
            'title' => $name . ' — Social CV — YouthNexus Pulse',
            'pageTitle' => 'Social CV',
            'pageDescription' => 'Verified youth development profile.',
            'currentRoute' => 'profile',
            'userRole' => (string) ($_SESSION['user_role'] ?? 'ClubMember'),
            'userName' => trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User',
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userInitials' => $_SESSION['user_initials'] ?? 'YN',
            'unreadNotificationCount' => $headerNotif['count'],
            'headerNotifications' => $headerNotif['items'],
            'csrf_token' => $_SESSION['csrf_token'],
            'flash' => $this->pullFlash(),
            'targetUserId' => $targetId,
            'profile' => [
                // No NIC: a viewer may open another member's CV, and the NIC
                // is never rendered (OUTSIDE item 1 privacy note).
                'name' => $name,
                'member_id' => (string) ($target->username ?? ('user-' . $targetId)),
                'location' => $location,
                'member_since' => $memberSince,
                'bio' => $roleLabel . ($scopeParts ? ' · ' . $scopeParts[0] : ''),
            ],
            'stats' => [
                ['label' => 'Volunteer Hours', 'value' => $hoursKnown ? $this->cvHoursLabel(round($totalHours, 1)) : '—', 'icon' => 'clock'],
                ['label' => 'Events Attended', 'value' => (string) $attendedCount, 'icon' => 'calendar'],
                ['label' => 'Attendance Rate', 'value' => $attendanceRate, 'icon' => 'check'],
                ['label' => 'Years Active', 'value' => $yearsActive, 'icon' => 'award'],
            ],
            'skills' => $skills,
            'positions' => [
                [
                    'role' => $roleLabel,
                    'date' => 'Current',
                    'club' => $location,
                    'description' => '',
                ],
            ],
            'timeline' => $timeline,
            'endorsements' => $endorsements,
            'publicUrl' => 'pulse.nysc.lk/cv/' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) ($target->username ?? ('user-' . $targetId)))),
            'lastUpdated' => date('M j, Y'),
        ];
    }

    /**
     * Own CV.
     */
    public function index() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $data = $this->buildCvData((int) $_SESSION['user_id']);
        if (!$data) {
            $this->redirect('auth/signin');
        }
        $data['isSelf'] = true;
        $data['canEndorse'] = false;
        $data['deletableIds'] = [];
        $this->view('profile/index', $data);
    }

    /**
     * Another member's CV (scope-guarded) with the endorse form.
     */
    public function member($id = 0) {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $viewer = $this->model('UserModel')->findByUserIdWithHierarchy((int) $_SESSION['user_id']);
        $target = $this->model('UserModel')->findByUserIdWithHierarchy((int) $id);
        if (!$viewer || !$target) {
            $this->redirect('home');
        }
        if ((int) $target->user_id === (int) $viewer->user_id) {
            $this->redirect('profile');
        }
        if (!$this->inSameScope($viewer, $target)) {
            $this->redirect('home');
        }
        $data = $this->buildCvData((int) $target->user_id);
        if (!$data) {
            $this->redirect('home');
        }
        $eligible = $this->canEndorseTarget($viewer, $target);
        $data['isSelf'] = false;
        $data['canEndorse'] = $eligible;
        // Delete is author-only (owner decision 2026-09-26): scope privilege
        // lets you write and revise your own endorsement, never remove
        // someone else's. Stale rows (author gone) are NYSC cleanup via DB.
        $data['deletableIds'] = [];
        foreach ($data['endorsements'] as $endorsement) {
            if ((int) $endorsement['endorser_user_id'] === (int) $viewer->user_id) {
                $data['deletableIds'][] = (int) $endorsement['id'];
            }
        }
        $this->view('profile/index', $data);
    }

    /**
     * Write (or revise) an endorsement. POST only.
     */
    public function endorse() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$this->verifyCsrf()) {
            $this->redirect('home');
        }
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $text = trim((string) ($_POST['text'] ?? ''));
        $viewer = $this->model('UserModel')->findByUserIdWithHierarchy((int) $_SESSION['user_id']);
        $target = $memberId > 0 ? $this->model('UserModel')->findByUserIdWithHierarchy($memberId) : false;
        if (!$viewer || !$target || !$this->canEndorseTarget($viewer, $target)) {
            $this->redirect('home');
        }
        if (mb_strlen($text) < 10 || mb_strlen($text) > 1000) {
            $this->setFlash('error', 'Endorsement must be between 10 and 1000 characters.');
            $this->redirect('profile/member/' . $memberId);
        }
        $this->model('EndorsementModel')->upsert(
            $memberId,
            (int) $viewer->user_id,
            $this->cvRoleLabel((string) ($viewer->role ?? '')),
            $text
        );
        $this->model('AuditLogModel')->log(
            (int) $viewer->user_id, 'ENDORSE_MEMBER', 'User', $memberId, 'Wrote a CV endorsement'
        );
        $this->setFlash('success', 'Endorsement saved.');
        $this->redirect('profile/member/' . $memberId);
    }

    /**
     * Delete your own endorsement. POST only. Authors can only ever remove
     * rows they wrote (owner decision 2026-09-26) — scope privilege grants
     * no moderation power.
     */
    public function deleteEndorsement() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$this->verifyCsrf()) {
            $this->redirect('home');
        }
        $endorsementId = (int) ($_POST['endorsement_id'] ?? 0);
        $row = $endorsementId > 0 ? $this->model('EndorsementModel')->findById($endorsementId) : false;
        if (!$row) {
            $this->redirect('home');
        }
        if ((int) $row->endorser_user_id !== (int) $_SESSION['user_id']) {
            $this->redirect('home');
        }
        $this->model('EndorsementModel')->delete($endorsementId);
        $this->model('AuditLogModel')->log(
            (int) $_SESSION['user_id'], 'DELETE_ENDORSEMENT', 'User', (int) $row->member_user_id, 'Removed own CV endorsement'
        );
        $this->setFlash('success', 'Endorsement removed.');
        $this->redirect('profile/member/' . (int) $row->member_user_id);
    }
}
