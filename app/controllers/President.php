<?php

/**
 * President — club president overview (C2).
 *
 * Presentation-only: mock data mirroring the future backend contract.
 * No database reads or writes. Member-base panels land in C15;
 * leadership handover lands in C6.
 *
 * Routes:
 *   president -> index()  (president only)
 *   president/review  -> review()  [POST] approve/reject a pending member
 *   president/assign  -> assign()  [POST] assign an executive role
 */
class President extends Controller {

    private function requirePresident() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $allowedRoles = ['ClubPresident', 'president'];
        if (!in_array($_SESSION['user_role'] ?? '', $allowedRoles, true)) {
            $this->redirect('home');
        }
        if ((int) ($_SESSION['club_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a club.');
        }
    }

    /**
     * Base view data for the shared shell.
     */
    private function shell($title, $pageTitle, $pageDescription, $currentRoute) {
        $memberName = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'YouthNexus User';
        return [
            'title'                   => $title,
            'pageTitle'               => $pageTitle,
            'pageDescription'         => $pageDescription,
            'currentRoute'            => $currentRoute,
            'userRole'                => $_SESSION['user_role'] ?? 'ClubPresident',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
        ];
    }

    /**
     * President overview: health score + 40/30/30 breakdown, pending club
     * events, exec-roster summary.
     */
    public function index() {
        $this->requirePresident();

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $userModel = $this->model('UserModel');
        $eventModel = $this->model('EventModel');

        $score = $this->model('DivisionalClubHealthModel')->scoreClub($clubId);
        $health = [
            'score'  => $score['overall_score'],
            'label'  => $score['health_status'],
            'state'  => 'Active',
            'events' => ['points' => (int) round($score['event_score'] / 100 * 40), 'max' => 40],
            'finances' => ['points' => (int) round($score['finance_score'] / 100 * 30), 'max' => 30],
            'attendance' => ['points' => (int) round($score['attendance_score'] / 100 * 30), 'max' => 30],
        ];

        $roleLabels = ['ClubPresident' => 'President', 'ClubSecretary' => 'Secretary', 'ClubTreasurer' => 'Treasurer'];
        $pendingEvents = [];
        foreach ($eventModel->getClubEvents($clubId) as $ev) {
            if ($ev->status !== 'PendingApproval') {
                continue;
            }
            $start = strtotime((string) $ev->start_datetime);
            $pendingEvents[] = [
                'id' => (int) $ev->event_id,
                'title' => $ev->title ?? '',
                'date' => $start ? date('M d, Y g:i A', $start) : '—',
                'location' => $ev->location ?? '—',
                'type' => $ev->event_type ?? '—',
                'submitted_by' => trim(($ev->creator_name ?? '') . ' (' . ($roleLabels[$ev->creator_role] ?? $ev->creator_role ?? '') . ')'),
            ];
            if (count($pendingEvents) >= 3) {
                break;
            }
        }

        $pendingMembers = [];
        foreach ($userModel->getClubPending($clubId) as $u) {
            $pendingMembers[] = [
                'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'email' => $u->email ?? '',
                'phone' => $u->phone_number ?? '—',
                'address' => $u->address ?? '—',
                'nic' => $u->NIC ?? '—',
                'joined' => ($u->membership_date && $u->membership_date !== '0000-00-00') ? date('M Y', strtotime($u->membership_date)) : '—',
                'registered_by' => 'Secretary',
            ];
            if (count($pendingMembers) >= 3) {
                break;
            }
        }

        $execRoster = [];
        foreach ($userModel->getClubRoster($clubId) as $u) {
            if (!isset($roleLabels[$u->role])) {
                continue;
            }
            $execRoster[] = [
                'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'role' => $roleLabels[$u->role],
                'status' => 'Active',
                'status_key' => 'active',
            ];
        }

        $announcements = ClubOverview::announcements($this, $clubId, $userId, 'ClubPresident', (int) ($_SESSION['division_id'] ?? 0) ?: null, (int) ($_SESSION['zonal_id'] ?? 0) ?: null);
        $upcomingEvents = ClubOverview::upcoming($this, $clubId);

        $completed = ClubOverview::completedCount($this, $clubId);
        $socialCv = [
            'volunteer_hours' => '—',
            'events_count'    => $completed,
            'leadership'      => 'Club President',
        ];

        $data = $this->shell(
            'President Overview — YouthNexus Pulse',
            'President Overview',
            'Health, pending approvals and executives of Gampaha Youth Development Club.',
            'president'
        );
        $data['health'] = $health;
        $data['pendingEvents'] = $pendingEvents;
        $data['pendingMembers'] = $pendingMembers;
        $data['execRoster'] = $execRoster;
        $data['announcements'] = $announcements;
        $data['upcomingEvents'] = $upcomingEvents;
        $data['socialCv'] = $socialCv;

        $this->view('president/index', $data);
    }

    /**
     * Decide a pending member (D1: real DB write scoped to the club).
     */
    public function review() {
        $this->requirePresident();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('club/members');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('club/members');
        }

        $memberId = (int) ($_POST['member_id'] ?? 0);
        $result = $_POST['result'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');
        if ($memberId < 1 || !in_array($result, ['approve', 'reject'], true)) {
            $this->setFlash('error', 'Invalid review request.');
            $this->redirect('club/members');
        }
        if ($result === 'reject' && $remarks === '') {
            $this->setFlash('error', 'Please add a note explaining the rejection.');
            $this->redirect('club/members');
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $userModel = $this->model('UserModel');
        if ($result === 'approve') {
            $rows = $userModel->approveClubMember($clubId, $memberId);
            if ($rows < 1) {
                $this->setFlash('error', 'Member not found in your club.');
                $this->redirect('club/members');
            }
            $this->model('AuditLogModel')->log($_SESSION['user_id'], 'APPROVE_MEMBER', 'User', $memberId, 'Approved club member');
            $this->setFlash('success', 'Member approved as General Member.');
        } else {
            $rows = $userModel->rejectClubMember($clubId, $memberId);
            if ($rows < 1) {
                $this->setFlash('error', 'Member not found in your club.');
                $this->redirect('club/members');
            }
            $this->model('AuditLogModel')->log($_SESSION['user_id'], 'REJECT_MEMBER', 'User', $memberId, $remarks);
            $this->setFlash('success', 'Application rejected with note.');
        }
        $this->redirect('club/members');
    }

    /**
     * Assign an executive role to a roster member.
     */
    public function assign() {
        $this->requirePresident();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('club/members');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('club/members');
        }

        $memberId = (int) ($_POST['member_id'] ?? 0);
        $role = $_POST['role'] ?? '';
        if ($memberId < 1 || !in_array($role, ['ClubSecretary', 'ClubTreasurer', 'ClubMember'], true)) {
            $this->setFlash('error', 'Invalid assignment request.');
            $this->redirect('club/members');
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $rows = $this->model('UserModel')->assignClubRole($clubId, $memberId, $role);
        if ($rows < 1) {
            $this->setFlash('error', 'Member not found in your club.');
            $this->redirect('club/members');
        }
        $this->model('AuditLogModel')->log($_SESSION['user_id'], 'ASSIGN_ROLE', 'User', $memberId, "Assigned {$role}");
        $this->setFlash('success', 'Role assignment recorded.');
        $this->redirect('club/members');
    }

    /**
     * Decide a pending club event (D2: real DB write scoped to the club).
     */
    public function eventDecision() {
        $this->requirePresident();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('club/events');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('club/events');
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');
        if ($eventId < 1 || !in_array($decision, ['approve', 'request-changes'], true)) {
            $this->setFlash('error', 'Invalid decision request.');
            $this->redirect('club/events');
        }
        if ($decision === 'request-changes' && $remarks === '') {
            $this->setFlash('error', 'Provide the reason for this decision.');
            $this->redirect('club/events');
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $rows = $this->model('EventModel')->decideClubEvent($clubId, $eventId, (int) $_SESSION['user_id'], $decision, $remarks);
        if ($rows < 1) {
            $this->setFlash('error', 'Event not found in your club.');
            $this->redirect('club/events');
        }
        $audit = $this->model('AuditLogModel');
        if ($decision === 'approve') {
            $audit->log($_SESSION['user_id'], 'APPROVE_EVENT', 'Event', $eventId, 'Approved club event');
            $this->setFlash('success', 'Event approved and published to the club calendar.');
        } else {
            $audit->log($_SESSION['user_id'], 'REJECT_EVENT', 'Event', $eventId, $remarks);
            $this->setFlash('success', 'Changes requested - the secretary has been notified.');
        }
        $this->redirect('club/events');
    }

    private function verifyCsrf(): bool {
        $token = (string) ($_POST['csrf_token'] ?? '');
        return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['club_flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * Leadership handover (C6): successor member-ID verify (invalid →
     * error + abort) + asset-freeze inventory checklist + president
     * confirm → atomic demote/promote + handover log + member notify.
     * Presentation-only: no DB writes.
     */
    /**
     * Leadership handover (D6: real atomic demote/promote + log).
     */
    public function handover() {
        $this->requirePresident();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $roleLabels = [
            'ClubPresident' => 'President',
            'ClubSecretary' => 'Secretary',
            'ClubTreasurer' => 'Treasurer',
            'ClubMember'    => 'Member',
            'Member'        => 'Member',
        ];
        $members = [];
        foreach ($this->model('UserModel')->getClubRoster($clubId) as $u) {
            $members[] = [
                'id'      => (int) $u->user_id,
                'name'    => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'role'    => $roleLabels[$u->role] ?? $u->role,
                'status'  => 'Active',
                'current' => (int) $u->user_id === (int) ($_SESSION['user_id'] ?? 0),
            ];
        }

        $freezeAssets = [];
        foreach ($this->model('ClubAssetModel')->getInventory($clubId) as $s) {
            $freezeAssets[] = [
                'id'       => (int) $s->catalog_item_id,
                'name'     => $s->item_name ?? '',
                'sku'      => $s->sku ?? '',
                'quantity' => (int) $s->quantity,
            ];
        }

        $data = $this->shell(
            'Leadership Handover - YouthNexus Pulse',
            'Leadership Handover',
            'Transfer the presidency of Gampaha Youth Development Club.',
            'president/handover'
        );
        $data['members'] = $members;
        $data['freezeAssets'] = $freezeAssets;
        $data['handoverLog'] = $this->model('ClubHandoverModel')->getLog($clubId);
        $data['csrf_token'] = $_SESSION['csrf_token'];
        $data['flash'] = $this->pullFlash();

        $this->view('president/handover', $data);
    }

    /**
     * Confirm the handover (atomic demote + promote + log).
     */
    public function confirmHandover() {
        $this->requirePresident();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('president/handover');
        }
        if (!$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect('president/handover');
        }

        $successorId = (int) ($_POST['successor_id'] ?? 0);
        $checked = json_decode($_POST['checklist'] ?? '[]', true);
        if (!is_array($checked)) {
            $checked = [];
        }

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        try {
            $handoverId = $this->model('ClubHandoverModel')->confirm($clubId, (int) $_SESSION['user_id'], $successorId, $checked);
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('president/handover');
        }

        $_SESSION['user_role'] = 'ClubMember';
        $this->setFlash('success', "Handover complete (log #{$handoverId}). You are now a General Member.");
        $this->redirect('member');
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['club_flash'] ?? null;
        unset($_SESSION['club_flash']);
        return is_array($flash) ? $flash : null;
    }
}
