<?php

/**
 * Treasurer — club treasurer overview (C9).
 *
 * Presentation-only: mock data mirroring the future backend contract.
 * No database reads or writes. Full member-panel embedding across all
 * exec overviews lands in C15.
 *
 * Routes:
 *   treasurer -> index()  (treasurer only)
 */
class Treasurer extends Controller {

    private function requireTreasurer() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $allowedRoles = ['ClubTreasurer', 'treasurer'];
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
            'userRole'                => $_SESSION['user_role'] ?? 'ClubTreasurer',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => 2,
        ];
    }

    /**
     * Treasurer overview: fund balance summary + ledger/asset shortcuts,
     * plus the member-base panels (announcements preview, upcoming events,
     * Social CV summary) per the subclass rule.
     */
    public function index() {
        $this->requireTreasurer();

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $ledgerModel = $this->model('ClubLedgerModel');
        $ledger = $ledgerModel->ensureClubLedger($clubId);
        $summary = $ledgerModel->getSummary((int) $ledger->ledger_id);
        $pendingVoids = $ledgerModel->getPendingVoidCount((int) $ledger->ledger_id);

        $money = static fn($v) => 'Rs. ' . number_format((float) $v, 2);
        $funds = [
            'balance'  => $money($summary['balance']),
            'income'   => $money($summary['income']),
            'expenses' => $money($summary['expenses']),
            'pending_voids' => $pendingVoids,
        ];

        $shortcuts = [
            ['title' => 'Log Transaction', 'desc' => 'Income or expense with mandatory receipt', 'href' => 'club/ledger', 'icon' => 'file'],
            ['title' => 'Transfer Custody', 'desc' => 'Hand an Available asset to a custodian', 'href' => 'club/assets', 'icon' => 'user'],
            ['title' => 'Review Pending Voids', 'desc' => $pendingVoids . ' void request(s) awaiting the Divisional Treasurer', 'href' => 'club/ledger', 'icon' => 'eye'],
        ];

        $announcements = ClubOverview::announcements($this, $clubId, $userId, 'ClubTreasurer', (int) ($_SESSION['division_id'] ?? 0) ?: null, (int) ($_SESSION['zonal_id'] ?? 0) ?: null);
        $upcomingEvents = ClubOverview::upcoming($this, $clubId);

        $socialCv = [
            'volunteer_hours' => '—',
            'events_count'    => ClubOverview::completedCount($this, $clubId),
            'leadership'      => 'Club Treasurer',
        ];

        $data = $this->shell(
            'Treasurer Overview — YouthNexus Pulse',
            'Treasurer Overview',
            'Funds, shortcuts and personal summary of Kasun Fernando.',
            'treasurer'
        );
        $data['funds'] = $funds;
        $data['shortcuts'] = $shortcuts;
        $data['announcements'] = $announcements;
        $data['upcomingEvents'] = $upcomingEvents;
        $data['socialCv'] = $socialCv;

        $this->view('treasurer/index', $data);
    }
}
