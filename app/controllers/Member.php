<?php

class Member extends Controller {

    /**
     * Restrict this dashboard to authenticated club-level accounts.
     * President / Secretary / Treasurer are subclasses of Club Member
     * (subclass rule, C14) so they share the member dashboard.
     */
    private function requireMember() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }

        $allowedRoles = ['ClubMember', 'Member', 'ClubPresident', 'ClubSecretary', 'ClubTreasurer', 'president', 'secretary', 'treasurer'];
        if (!in_array($_SESSION['user_role'] ?? '', $allowedRoles, true)) {
            $this->redirect('home');
        }
        if ((int) ($_SESSION['club_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a club.');
        }
    }

    /**
     * Render the Club Member dashboard with presentation-only data.
     *
     * This task intentionally does not query the database or perform writes.
     * The array keys mirror the future backend response contract so the view
     * can later be connected to real aggregates without a markup rewrite.
     */
    public function index() {
        $this->requireMember();

        $memberName = trim((string) ($_SESSION['user_name'] ?? 'Nuwan Bandara')) ?: 'Nuwan Bandara';
        $memberInitials = strtoupper(
            substr($memberName, 0, 1) . substr(strrchr(' ' . $memberName, ' '), 1, 1)
        );

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $roleLabels = ['ClubPresident' => 'President', 'ClubSecretary' => 'Secretary', 'ClubTreasurer' => 'Treasurer', 'ClubMember' => 'Member', 'Member' => 'Member'];

        $attSummary = $this->model('AttendanceModel')->getClubMemberSummary($clubId, $userId);
        $upcoming = ClubOverview::upcoming($this, $clubId);
        // Hierarchy-resolved scope (raw session ids drop zonal/divisional rows).
        $annScope = ZoneOverview::effectiveScope($this, $userId);
        $annRows = ClubOverview::announcements($this, $clubId, $userId, $_SESSION['user_role'] ?? 'ClubMember', $annScope['division_id'], $annScope['zonal_id']);

        $unread = 0;
        try {
            $all = $this->model('AnnouncementModel')->findForUser($userId, $_SESSION['user_role'] ?? 'ClubMember', $clubId, $annScope['division_id'], $annScope['zonal_id']);
            $readModel = $this->model('AnnouncementReadModel');
            foreach (is_array($all) ? $all : [] as $a) {
                $row = is_array($a) ? (object) $a : $a;
                $id = (int) ($row->announcement_id ?? $row->id ?? 0);
                if ($id > 0 && !$readModel->hasRead($id, $userId)) {
                    $unread++;
                }
            }
        } catch (Throwable $e) {
            $unread = 0;
        }

        $certs = $this->model('CertificateModel')->findByOwner('Member', $userId);
        $latestCert = '—';
        if (is_array($certs) && count($certs) > 0) {
            $first = is_array($certs[0]) ? (object) $certs[0] : $certs[0];
            $latestCert = ($first->certificate_type ?? 'Certificate') . ' — Issued';
        }

        $actionLabels = [
            'SAVE_ATTENDANCE' => 'Attendance recorded',
            'APPROVE_MEMBER'  => 'Approved a member application',
            'REJECT_MEMBER'   => 'Reviewed a member application',
            'ASSIGN_ROLE'     => 'Assigned an executive role',
            'CREATE_EVENT'    => 'Created an event',
            'APPROVE_EVENT'   => 'Approved an event',
            'REJECT_EVENT'    => 'Reviewed an event',
            'COMPLETE_EVENT'  => 'Completed an event',
            'LOG_TRANSACTION' => 'Logged a transaction',
            'REQUEST_VOID'    => 'Requested a void',
            'REGISTER_MEMBER' => 'Registered a member',
            'HANDOVER'        => 'Handed over the presidency',
        ];
        $recentActivity = [];
        foreach ($this->model('AuditLogModel')->getByActor($userId, 4) as $log) {
            $ts = strtotime((string) $log->timestamp);
            $recentActivity[] = [
                'label' => $actionLabels[$log->action_type] ?? ucwords(strtolower(str_replace('_', ' ', $log->action_type))),
                'meta'  => $ts ? date('M d, g:i A', $ts) : '',
                'icon'  => 'check',
            ];
        }

        $memberDashboard = [
            'member' => [
                'name'      => $memberName,
                'role'      => $roleLabels[$_SESSION['user_role'] ?? ''] ?? 'Member',
                'club_name' => $_SESSION['club_name'] ?? 'Gampaha Youth Development Club',
                'initials'   => $memberInitials ?: 'JD',
            ],
            'tiles' => [
                'volunteer_hours'      => '—',
                'upcoming_events'      => count($upcoming),
                'unread_announcements'=> $unread,
                'latest_certificate'  => $latestCert,
            ],
            'announcements' => $annRows,
            'upcoming_events_list' => $upcoming,
            'recent_activity' => $recentActivity,
        ];

        $headerNotif = ZoneOverview::headerNotifications($this);

        $this->view('member/index', [
            'title'                   => 'Member Dashboard — YouthNexus Pulse',
            'pageTitle'               => 'Welcome back, ' . $memberName,
            'pageDescription'         => "Here’s what’s happening with your activities at {$memberDashboard['member']['club_name']}.",
            'currentRoute'            => 'member',
            'userRole'                => $_SESSION['user_role'] ?? 'ClubMember',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? $memberInitials,
            'unreadNotificationCount' => $headerNotif['count'],
            'headerNotifications'     => $headerNotif['items'],
            'memberDashboard'         => $memberDashboard,
            // Exec summary strips (same sources as the full overviews).
            'presidentSummary'        => in_array($_SESSION['user_role'] ?? '', ['ClubPresident', 'president'], true)
                ? $this->presidentStrip($clubId)
                : null,
            'treasurerSummary'        => in_array($_SESSION['user_role'] ?? '', ['ClubTreasurer', 'treasurer'], true)
                ? $this->treasurerStrip($clubId)
                : null,
            'secretarySummary'        => in_array($_SESSION['user_role'] ?? '', ['ClubSecretary', 'secretary'], true)
                ? $this->secretaryStrip($clubId)
                : null,
        ]);
    }

    private function presidentStrip($clubId) {
        $score = $this->model('DivisionalClubHealthModel')->scoreClub($clubId);
        $memberCounts = $this->model('UserModel')->countClubRoster($clubId);
        $eventCounts = $this->model('EventModel')->countClubEventsByStatus($clubId);
        return [
            'health_score' => $score['overall_score'],
            'health_label' => $score['health_status'],
            'pending_events' => $eventCounts['PendingApproval'],
            'pending_members' => $memberCounts['pending'],
        ];
    }

    private function treasurerStrip($clubId) {
        $ledgerModel = $this->model('ClubLedgerModel');
        $ledger = $ledgerModel->ensureClubLedger($clubId);
        $summary = $ledgerModel->getSummary((int) $ledger->ledger_id);
        $money = static fn($v) => 'Rs. ' . number_format((float) $v, 2);
        return [
            'balance' => $money($summary['balance']),
            'income' => $money($summary['income']),
            'expenses' => $money($summary['expenses']),
            'pending_voids' => $ledgerModel->getPendingVoidCount((int) $ledger->ledger_id),
        ];
    }

    private function secretaryStrip($clubId) {
        $memberCounts = $this->model('UserModel')->countClubRoster($clubId);
        $eventCounts = $this->model('EventModel')->countClubEventsByStatus($clubId);
        return [
            'pending_members' => $memberCounts['pending'],
            'pending_events' => $eventCounts['PendingApproval'],
        ];
    }
}
