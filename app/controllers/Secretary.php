<?php

/**
 * Secretary — club secretary overview (C10).
 *
 * Presentation-only: mock data mirroring the future backend contract.
 * No database reads or writes. Full member-panel embedding across all
 * exec overviews lands in C15.
 *
 * Routes:
 *   secretary -> index()  (secretary only)
 */
class Secretary extends Controller {

    private function requireSecretary() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $allowedRoles = ['ClubSecretary', 'secretary'];
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
        $headerNotif = ZoneOverview::headerNotifications($this);
        return [
            'title'                   => $title,
            'pageTitle'               => $pageTitle,
            'pageDescription'         => $pageDescription,
            'currentRoute'            => $currentRoute,
            'userRole'                => $_SESSION['user_role'] ?? 'ClubSecretary',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => $headerNotif['count'],
            'headerNotifications'     => $headerNotif['items'],
        ];
    }

    /**
     * Secretary overview: member/event/attendance/announcement shortcuts,
     * plus the member-base panels (announcements preview, upcoming events,
     * Social CV summary) per the subclass rule.
     */
    public function index() {
        $this->requireSecretary();

        $clubId = (int) ($_SESSION['club_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $userModel = $this->model('UserModel');
        $eventModel = $this->model('EventModel');

        $shortcuts = [
            ['title' => 'Register Member', 'desc' => 'Add a member — president approves', 'href' => 'club/members', 'icon' => 'user'],
            ['title' => 'Create Event', 'desc' => 'Submit an event for approval', 'href' => 'club/events', 'icon' => 'calendar'],
            ['title' => 'Mark Attendance', 'desc' => 'Single entry or bulk CSV', 'href' => 'club/attendance', 'icon' => 'check'],
            ['title' => 'Publish Announcement', 'desc' => 'Normal or Urgent club update', 'href' => 'announcements', 'icon' => 'bell'],
        ];

        $memberCounts = $userModel->countClubRoster($clubId);
        $eventCounts = $eventModel->countClubEventsByStatus($clubId);
        $queue = [
            'pending_members' => $memberCounts['pending'],
            'pending_events'  => $eventCounts['PendingApproval'],
        ];

        $announcements = ClubOverview::announcements($this, $clubId, $userId, 'ClubSecretary', (int) ($_SESSION['division_id'] ?? 0) ?: null, (int) ($_SESSION['zonal_id'] ?? 0) ?: null);
        $upcomingEvents = ClubOverview::upcoming($this, $clubId);

        $socialCv = [
            'volunteer_hours' => '—',
            'events_count'    => ClubOverview::completedCount($this, $clubId),
            'leadership'      => 'Club Secretary',
        ];

        $data = $this->shell(
            'Secretary Overview — YouthNexus Pulse',
            'Secretary Overview',
            'Queues, shortcuts and personal summary of Amal Perera.',
            'secretary'
        );
        $data['shortcuts'] = $shortcuts;
        $data['queue'] = $queue;
        $data['announcements'] = $announcements;
        $data['upcomingEvents'] = $upcomingEvents;
        $data['socialCv'] = $socialCv;

        $this->view('secretary/index', $data);
    }
}
