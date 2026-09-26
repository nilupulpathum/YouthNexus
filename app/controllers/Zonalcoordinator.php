<?php

/**
 * ZonalCoordinator — zonal coordinator overview (D8, real zone aggregates)
 * plus the zonal event approval queue (D9/Plan 02). Club health monitoring
 * and reports live in Zonalclubhealth and Zonalreports (divisional workflow
 * at zone scope).
 *
 * Routes:
 *   zonalcoordinator -> index()  (zonal coordinator only)
 *   zonalcoordinator/events -> events()
 *   zonalcoordinator/approveevent, zonalcoordinator/returnevent
 */
class Zonalcoordinator extends Controller {

    private function requireZonalCoordinator() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/signin');
        }
        $allowedRoles = ['ZonalCoordinator', 'zonalcoordinator', 'zonal'];
        if (!in_array($_SESSION['user_role'] ?? '', $allowedRoles, true)) {
            $this->redirect('home');
        }
        if ((int) ($_SESSION['zonal_id'] ?? 0) < 1) {
            http_response_code(403);
            exit('Your user account is not assigned to a zone.');
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
            'userRole'                => $_SESSION['user_role'] ?? 'ZonalCoordinator',
            'userName'                => $memberName,
            'userEmail'               => $_SESSION['user_email'] ?? '',
            'userInitials'            => $_SESSION['user_initials'] ?? '',
            'unreadNotificationCount' => $headerNotif['count'],
            'headerNotifications'     => $headerNotif['items'],
        ];
    }

    /**
     * Coordinator overview (Z1): zone-health tiles, division averages and
     * club monitoring. Club cards live on clubs().
     */
    public function index() {
        $this->requireZonalCoordinator();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $role = (string) ($_SESSION['user_role'] ?? 'ZonalCoordinator');
        $built = $this->buildMonitor($zonalId);

        $data = $this->shell(
            'Zonal Coordinator Overview — YouthNexus Pulse',
            'Zonal Coordinator Overview',
            'Zone health of ' . $built['zone_name'] . '.',
            'zonalcoordinator'
        );
        $data['zoneHealth'] = $built['zoneHealth'];
        $data['divisions'] = $built['divisions'];
        $data['zoneName'] = $built['zone_name'];
        $data['announcements'] = ZoneOverview::announcements(
            $this, $userId, $role, $_SESSION['division_id'] ?? null, $zonalId
        );
        $data['upcomingEvents'] = ZoneOverview::upcoming($this, $zonalId);
        $data['flash'] = $this->pullFlash();

        $this->view('zonalcoordinator/index', $data);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['zonal_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array {
        $flash = $_SESSION['zonal_flash'] ?? null;
        unset($_SESSION['zonal_flash']);
        return is_array($flash) ? $flash : null;
    }

    /**
     * Assemble bands, division averages and club cards for a zone.
     */
    private function buildMonitor(int $zonalId): array {
        $monitor = $this->model('ZoneMonitorModel');
        $healthModel = $this->model('DivisionalClubHealthModel');
        $zone = $monitor->getZone($zonalId);
        $zoneName = $zone->zonal_name ?? 'Zone';

        $bandOf = static function ($score) {
            if ($score >= 85) {
                return 'healthy';
            }
            if ($score >= 50) {
                return 'atrisk';
            }
            return 'dormant';
        };

        $clubs = [];
        $divStats = [];
        foreach ($monitor->getClubs($zonalId) as $c) {
            $clubId = (int) $c->club_id;
            $score = $healthModel->scoreClub($clubId);
            $overall = (float) $score['overall_score'];
            $band = $bandOf($overall);
            $execs = [];
            foreach ($monitor->getExecutives($clubId) as $x) {
                $execs[] = [
                    'name' => trim(($x->first_name ?? '') . ' ' . ($x->last_name ?? '')),
                    'role' => ['ClubPresident' => 'President', 'ClubSecretary' => 'Secretary', 'ClubTreasurer' => 'Treasurer'][$x->role] ?? $x->role,
                ];
            }
            $recent = [];
            foreach ($monitor->getRecentEvents($clubId) as $ev) {
                $ts = strtotime((string) $ev->start_datetime);
                $recent[] = [
                    'title' => $ev->title ?? '',
                    'date' => $ts ? date('M d, Y', $ts) : '—',
                    'status' => $ev->status ?? '',
                ];
            }
            $rate = $monitor->getAttendanceRate($clubId);
            $openFlags = (int) ($c->open_flags ?? 0);
            $trigger = $band === 'dormant' ? 'Intervention required' : ($band === 'atrisk' ? 'Watch list' : 'Healthy');
            if ($openFlags > 0) {
                $trigger .= " · {$openFlags} open flag(s)";
            }
            $clubs[] = [
                'id' => $clubId,
                'name' => $c->club_name ?? '',
                'division' => $c->division_name ?? '',
                'score' => $overall,
                'band' => $band,
                'status' => $score['health_status'],
                'status_key' => strtolower($score['health_status']),
                'members' => (int) ($c->active_members ?? 0),
                'members_note' => (int) ($c->active_members ?? 0) . ' active members',
                'about' => $c->description ?? '',
                'category' => '—',
                'location' => '—',
                'established' => ($c->registration_date && $c->registration_date !== '0000-00-00') ? date('M Y', strtotime($c->registration_date)) : '—',
                'avg_attendance' => $rate === null ? '—' : $rate . '%',
                'attendance_trend' => '—',
                'trigger' => $trigger,
                'events' => ['points' => (int) round($score['event_score'] / 100 * 40), 'max' => 40],
                'finances' => ['points' => (int) round($score['finance_score'] / 100 * 30), 'max' => 30],
                'attendance' => ['points' => (int) round($score['attendance_score'] / 100 * 30), 'max' => 30],
                'recent_events' => $recent,
                'execs' => $execs,
            ];
            $divId = (int) $c->division_id;
            if (!isset($divStats[$divId])) {
                $divStats[$divId] = ['division' => $c->division_name ?? '', 'scores' => [], 'clubs' => 0];
            }
            $divStats[$divId]['scores'][] = $overall;
            $divStats[$divId]['clubs']++;
        }
        usort($clubs, static fn($a, $b) => $b['score'] <=> $a['score']);

        $divisions = [];
        foreach ($monitor->getDivisions($zonalId) as $d) {
            $stats = $divStats[(int) $d->division_id] ?? ['scores' => [], 'clubs' => 0];
            $avg = count($stats['scores']) > 0 ? round(array_sum($stats['scores']) / count($stats['scores']), 1) : 0;
            $divisions[] = [
                'division' => $d->division_name ?? '',
                'average' => $avg,
                'clubs' => $stats['clubs'],
                'status' => $avg >= 85 ? 'Healthy' : ($avg >= 50 ? 'At Risk' : 'Dormant'),
                'status_key' => $bandOf($avg),
            ];
        }

        $bands = ['healthy' => 0, 'atrisk' => 0, 'dormant' => 0];
        foreach ($clubs as $c) {
            $bands[$c['band']]++;
        }

        $n = count($clubs);
        $avgOf = static function ($key) use ($clubs, $n) {
            if ($n === 0) {
                return 0;
            }
            $sum = 0;
            foreach ($clubs as $c) {
                $sum += $c[$key]['points'] / max(1, $c[$key]['max']) * 100;
            }
            return round($sum / $n, 1);
        };
        $eventScore = $avgOf('events');
        $financeScore = $avgOf('finances');
        $attendanceScore = $avgOf('attendance');
        $overall = $n > 0 ? round(($eventScore * 0.4) + ($financeScore * 0.3) + ($attendanceScore * 0.3), 1) : 0;
        $zoneHealth = [
            'score' => $overall,
            'label' => $overall >= 85 ? 'Healthy' : ($overall >= 50 ? 'At Risk' : 'Dormant'),
            'state' => $n . ($n === 1 ? ' club' : ' clubs'),
            'events' => ['points' => (int) round($eventScore / 100 * 40), 'max' => 40],
            'finances' => ['points' => (int) round($financeScore / 100 * 30), 'max' => 30],
            'attendance' => ['points' => (int) round($attendanceScore / 100 * 30), 'max' => 30],
        ];

        return ['zone_name' => $zoneName, 'bands' => $bands, 'zoneHealth' => $zoneHealth, 'divisions' => $divisions, 'clubs' => $clubs];
    }

    private function coordinatorCsrf() {
        $_SESSION['zonal_coordinator_csrf'] = $_SESSION['zonal_coordinator_csrf'] ?? bin2hex(random_bytes(32));
        return $_SESSION['zonal_coordinator_csrf'];
    }

    public function events() {
        $this->requireZonalCoordinator();
        $zonalId = (int) ($_SESSION['zonal_id'] ?? 0);
        $statusMap = [
            'PendingApproval' => 'Pending approval',
            'Approved' => 'Approved',
            'Completed' => 'Completed',
            'Rejected' => 'Changes requested',
        ];
        $events = [];
        $pending = 0;
        $approved = 0;
        foreach ($this->model('EventModel')->getZonalEvents($zonalId) as $ev) {
            if ($ev->status === 'PendingApproval') {
                $pending++;
            } elseif ($ev->status === 'Approved') {
                $approved++;
            }
            $start = strtotime((string) $ev->start_datetime);
            $events[] = [
                'id' => (int) $ev->event_id,
                'title' => $ev->title ?? '',
                'type' => $ev->event_type ?? '',
                'date' => $start ? date('M d, Y', $start) : '—',
                'time' => $start ? date('g:i A', $start) : '',
                'location' => $ev->location ?? '',
                'audience' => $ev->target_divisions ?: 'All divisions',
                'status' => $statusMap[$ev->status] ?? $ev->status,
                'status_key' => strtolower($ev->status ?? ''),
                'coordinator_remark' => $ev->rejection_remarks ?? '',
            ];
        }
        $data = $this->shell('Approve Zonal Events — YouthNexus Pulse', 'Approve Zonal Events', 'Review events submitted by the Zonal Secretary.', 'zonalcoordinator/events');
        $data += ['events' => $events, 'eventStats' => ['scheduled' => $pending, 'approved' => $approved, 'total' => count($events)], 'csrf_token' => $this->coordinatorCsrf(), 'flash' => $this->pullFlash(), 'zoneName' => $this->zoneDisplayName($zonalId)];
        $this->view('zonalcoordinator/events', $data);
    }

    private function decideEvent($decision) {
        $this->requireZonalCoordinator();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_string($_POST['csrf_token'] ?? null) || !hash_equals($this->coordinatorCsrf(), $_POST['csrf_token'])) {
            $this->setFlash('error', 'Refresh the event queue before submitting a decision.'); $this->redirect('zonalcoordinator/events');
        }
        $id = (int) ($_POST['event_id'] ?? 0); $remark = trim((string)($_POST['remark'] ?? ''));
        if ($id < 1 || $remark === '' || mb_strlen($remark) > 1000) {
            $this->setFlash('error', 'A pending event and decision remark are required.');
            $this->redirect('zonalcoordinator/events');
        }
        $rows = $this->model('EventModel')->decideZonalEvent((int) ($_SESSION['zonal_id'] ?? 0), $id, (int) $_SESSION['user_id'], $decision, $remark);
        if ($rows < 1) {
            $this->setFlash('error', 'Event not found in your zone.');
            $this->redirect('zonalcoordinator/events');
        }
        $this->model('AuditLogModel')->log($_SESSION['user_id'], $decision === 'approve' ? 'APPROVE_EVENT' : 'REJECT_EVENT', 'Event', $id, $remark);
        $this->setFlash('success', $decision === 'approve' ? 'Event approved. The decision is recorded in the event queue.' : 'Event returned to the Zonal Secretary with the requested changes.');
        $this->redirect('zonalcoordinator/events');
    }

    public function approveevent() { $this->decideEvent('approve'); }
    public function returnevent() { $this->decideEvent('request-changes'); }

    private function zoneDisplayName(int $zonalId): string {
        $zone = $this->model('ZoneFundModel')->getZone($zonalId);
        return $zone->zonal_name ?? 'Zone';
    }

}
