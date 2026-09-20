<?php

/**
 * Attendance Controller
 *
 * Supports both NYSC Administrator (National scope with cascading Zone/Division/Club filters)
 * and Divisional Secretary (Divisional scope).
 */
class Attendance extends Controller {

    /**
     * Auth gate — allows NYSCAdministrator, DivisionalSecretary, and ZonalSecretary.
     */
    private function requireAuthorizedUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
        if (empty($_SESSION['user_id']) || !in_array($role, ['NYSCAdministrator', 'DivisionalSecretary', 'ZonalSecretary'])) {
            $this->redirect('auth/signin');
        }
        return $role;
    }

    private function isNYSCAdmin() {
        return ($_SESSION['user_role'] ?? '') === 'NYSCAdministrator';
    }

    private function jsonError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit();
    }

    // ------------------------------------------------------------------
    // INDEX — session list (approved events with attendance summary)
    // ------------------------------------------------------------------
    public function index() {
        $role = $this->requireAuthorizedUser();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $attendanceModel = $this->model('AttendanceModel');
        $isNYSCAdmin     = ($role === 'NYSCAdministrator');

        if ($isNYSCAdmin) {
            // National Administrator Scope with Cascading Filters
            $filters = [
                'zone_id'     => !empty($_GET['zone_id'])     ? (int)$_GET['zone_id']     : null,
                'division_id' => !empty($_GET['division_id']) ? (int)$_GET['division_id'] : null,
                'club_id'     => !empty($_GET['club_id'])     ? (int)$_GET['club_id']     : null,
                'level'       => trim($_GET['level'] ?? 'all'),
                'event_type'  => trim($_GET['event_type'] ?? ''),
                'search'      => trim($_GET['search'] ?? ''),
            ];

            $zones     = $attendanceModel->getAllZones();
            $divisions = $attendanceModel->getDivisionsByZone($filters['zone_id']);
            $clubs     = $attendanceModel->getClubsByScope($filters['division_id'], $filters['zone_id']);
            $events    = $attendanceModel->getApprovedEventsNational($filters);
            $stats     = $attendanceModel->getNationalAttendanceStats();

            $this->view('attendance/session-list', [
                'title'       => 'View Attendance — YouthNexus',
                'pageTitle'   => 'National Attendance Governance',
                'pageDescription' => 'Review, filter and monitor youth attendance across all zones, divisions, and clubs',
                'isNYSCAdmin' => true,
                'zones'       => $zones,
                'divisions'   => $divisions,
                'clubs'       => $clubs,
                'filters'     => $filters,
                'events'      => $events,
                'stats'       => $stats,
                'csrf_token'  => $_SESSION['csrf_token'],
                'userName'    => $_SESSION['user_name'] ?? 'National Admin',
                'userRole'    => $role,
            ]);

        } else {
            // Divisional Secretary Scope
            $divisionId = (int)($_SESSION['division_id'] ?? 0);
            $events     = $attendanceModel->getApprovedEventsByDivision($divisionId);
            $stats      = $attendanceModel->getDivisionAttendanceStats($divisionId);

            $this->view('attendance/session-list', [
                'title'       => 'Manage Attendance — YouthNexus',
                'pageTitle'   => 'Manage Attendance',
                'pageDescription' => 'Log and review attendance for approved events in your division',
                'isNYSCAdmin' => false,
                'events'      => $events,
                'stats'       => $stats,
                'csrf_token'  => $_SESSION['csrf_token'],
                'userName'    => $_SESSION['user_name'] ?? 'N. Fernando',
                'userRole'    => $role,
            ]);
        }
    }

    // ------------------------------------------------------------------
    // DETAIL — single event member roster
    // Supports both HTML render and JSON (called by JS for member dropdown)
    // ------------------------------------------------------------------
    public function detail($eventId = null) {
        $role    = $this->requireAuthorizedUser();
        $eventId = (int)$eventId;

        if (!$eventId) {
            $this->redirect('attendance');
        }

        $attendanceModel = $this->model('AttendanceModel');
        $isNYSCAdmin     = ($role === 'NYSCAdministrator');
        $divisionId      = (int)($_SESSION['division_id'] ?? 0);

        // Fetch event based on scope
        if ($isNYSCAdmin) {
            $event = $attendanceModel->getApprovedEventNational($eventId);
        } else {
            $event = $attendanceModel->getApprovedEventInScope($eventId, $divisionId);
        }

        if (!$event) {
            http_response_code(404);
            $this->redirect('attendance');
        }

        // JSON mode — called by JS to populate member dropdown
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        if (strpos($accept, 'application/json') !== false || strtolower($xReq) === 'xmlhttprequest') {
            $roster = $isNYSCAdmin
                ? $attendanceModel->getMemberRosterForEventNational($eventId)
                : $attendanceModel->getMemberRosterForEvent($eventId, $divisionId, $event->target_scope);

            header('Content-Type: application/json');
            echo json_encode(['members' => $roster, 'event' => $event]);
            exit();
        }

        // HTML render
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $roster = $isNYSCAdmin
            ? $attendanceModel->getMemberRosterForEventNational($eventId)
            : $attendanceModel->getMemberRosterForEvent($eventId, $divisionId, $event->target_scope);

        $aStats = $attendanceModel->getEventAttendanceStats($eventId);

        $present   = (int)($aStats->present_count ?? 0);
        $absent    = (int)($aStats->absent_count  ?? 0);
        $recorded  = $present + $absent;
        $total     = count($roster);
        $rate      = $total > 0 ? round(($present / $total) * 100) : 0;

        $this->view('attendance/session-detail', [
            'title'       => 'Event Attendance — YouthNexus',
            'isNYSCAdmin' => $isNYSCAdmin,
            'event'       => $event,
            'roster'      => $roster,
            'present'     => $present,
            'absent'      => $absent,
            'recorded'    => $recorded,
            'total'       => $total,
            'rate'        => $rate,
            'csrf_token'  => $_SESSION['csrf_token'],
            'userName'    => $_SESSION['user_name'] ?? 'Administrator',
            'userRole'    => $role,
        ]);
    }

    // ------------------------------------------------------------------
    // AJAX CASCADING FILTERS
    // ------------------------------------------------------------------

    /** Get divisions by zone. */
    public function getdivisions() {
        $this->requireAuthorizedUser();
        $zoneId = !empty($_GET['zone_id']) ? (int)$_GET['zone_id'] : null;
        $model  = $this->model('AttendanceModel');
        $divs   = $model->getDivisionsByZone($zoneId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'divisions' => $divs]);
        exit();
    }

    /** Get clubs by division or zone. */
    public function getclubs() {
        $this->requireAuthorizedUser();
        $divId  = !empty($_GET['division_id']) ? (int)$_GET['division_id'] : null;
        $zoneId = !empty($_GET['zone_id'])     ? (int)$_GET['zone_id']     : null;
        $model  = $this->model('AttendanceModel');
        $clubs  = $model->getClubsByScope($divId, $zoneId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'clubs' => $clubs]);
        exit();
    }

    /** Get members for an event (used by Add Attendance modal). */
    public function getmembers() {
        $role    = $this->requireAuthorizedUser();
        $eventId = (int)($_GET['event_id'] ?? 0);
        if (!$eventId) {
            $this->jsonError('Missing event_id.');
        }

        $model       = $this->model('AttendanceModel');
        $isNYSCAdmin = ($role === 'NYSCAdministrator');
        $divisionId  = (int)($_SESSION['division_id'] ?? 0);

        $roster = $isNYSCAdmin
            ? $model->getMemberRosterForEventNational($eventId)
            : $model->getMemberRosterForEvent($eventId, $divisionId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'members' => $roster]);
        exit();
    }

    // ------------------------------------------------------------------
    // SAVE — POST handler for both single-entry and bulk CSV
    // ------------------------------------------------------------------
    public function save() {
        $role = $this->requireAuthorizedUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('attendance');
        }

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $this->jsonError('Invalid security token. Please refresh the page.');
        }

        $mode        = trim($_POST['mode'] ?? 'single');
        $isNYSCAdmin = ($role === 'NYSCAdministrator');
        $divisionId  = $isNYSCAdmin ? null : (int)($_SESSION['division_id'] ?? 0);
        $recordedBy  = (int)($_SESSION['user_id'] ?? 0);

        $attendanceModel = $this->model('AttendanceModel');

        if ($mode === 'single') {
            $eventId     = (int)($_POST['event_id']     ?? 0);
            $memberId    = (int)($_POST['member_id']    ?? 0);
            $status      = trim($_POST['status']        ?? '');
            $checkInTime = trim($_POST['check_in_time'] ?? '');
            $remark      = trim($_POST['remark']        ?? '');

            if (!$eventId || !$memberId || !in_array($status, ['Present', 'Absent'])) {
                $this->jsonError('Missing required fields: event, member, and status.');
            }

            // Verify event exists and is approved
            $event = $isNYSCAdmin
                ? $attendanceModel->getApprovedEventNational($eventId)
                : $attendanceModel->getApprovedEventInScope($eventId, $divisionId);

            if (!$event) {
                $this->jsonError('Event not found or not approved for your scope.', 403);
            }

            if (!$attendanceModel->memberIsInScope($memberId, $eventId, $divisionId, $event->target_scope ?? 'AllInScope')) {
                $this->jsonError('Member is not active or not targeted by this event.', 422);
            }

            // Normalise check-in time
            if ($status === 'Present') {
                $checkInTime = !empty($checkInTime) ? date('Y-m-d H:i:s', strtotime($checkInTime)) : date('Y-m-d H:i:s');
            } else {
                $checkInTime = null;
            }

            $attendanceModel->saveAttendance(
                $eventId,
                $memberId,
                $status,
                $checkInTime,
                null,
                $remark ?: null,
                $recordedBy
            );

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Attendance logged successfully.']);
            exit();

        } elseif ($mode === 'bulk') {
            $eventId = (int)($_POST['event_id'] ?? 0);

            if (!$eventId || empty($_FILES['csv_file']['tmp_name'])) {
                $this->jsonError('Please provide both an event and a valid CSV file.');
            }

            $event = $isNYSCAdmin
                ? $attendanceModel->getApprovedEventNational($eventId)
                : $attendanceModel->getApprovedEventInScope($eventId, $divisionId);

            if (!$event) {
                $this->jsonError('Event not found or not approved.', 403);
            }

            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if (!$file) {
                $this->jsonError('Failed to read uploaded CSV file.');
            }

            $savedCount   = 0;
            $skippedRows  = [];
            $rowNum       = 0;

            while (($row = fgetcsv($file, 1000, ',')) !== false) {
                $rowNum++;
                // Skip header row if present
                if ($rowNum === 1 && (strtolower(trim($row[0])) === 'member_id' || strtolower(trim($row[0])) === 'user_id')) {
                    continue;
                }

                $memberId = (int)trim($row[0] ?? '');
                $status   = ucfirst(strtolower(trim($row[1] ?? 'Present')));
                $checkIn  = trim($row[2] ?? '');
                $remark   = trim($row[3] ?? '');

                if (!$memberId || !in_array($status, ['Present', 'Absent'])) {
                    $skippedRows[] = "Row $rowNum: invalid member ID or status";
                    continue;
                }

                if (!$attendanceModel->memberIsInScope($memberId, $eventId, $divisionId, $event->target_scope ?? 'AllInScope')) {
                    $skippedRows[] = "Row $rowNum: Member #$memberId not active or out of scope";
                    continue;
                }

                $checkInTime = ($status === 'Present' && !empty($checkIn)) ? date('Y-m-d H:i:s', strtotime($checkIn)) : null;

                $attendanceModel->saveAttendance(
                    $eventId,
                    $memberId,
                    $status,
                    $checkInTime,
                    null,
                    $remark ?: null,
                    $recordedBy
                );
                $savedCount++;
            }

            fclose($file);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'saved'   => $savedCount,
                'skipped' => $skippedRows,
                'message' => "Successfully imported $savedCount attendance records." . (!empty($skippedRows) ? " (" . count($skippedRows) . " rows skipped)" : ""),
            ]);
            exit();

        } else {
            $this->jsonError('Unsupported submission mode.');
        }
    }

    // ------------------------------------------------------------------
    // DOWNLOAD — export attendance roster as CSV
    // ------------------------------------------------------------------
    public function download($eventId = null) {
        $role    = $this->requireAuthorizedUser();
        $eventId = (int)$eventId;

        if (!$eventId) {
            $this->redirect('attendance');
        }

        $attendanceModel = $this->model('AttendanceModel');
        $isNYSCAdmin     = ($role === 'NYSCAdministrator');
        $divisionId      = (int)($_SESSION['division_id'] ?? 0);

        $event = $isNYSCAdmin
            ? $attendanceModel->getApprovedEventNational($eventId)
            : $attendanceModel->getApprovedEventInScope($eventId, $divisionId);

        if (!$event) {
            $this->redirect('attendance');
        }

        $rows = $attendanceModel->getAttendanceForEvent($eventId);

        $filename = 'Attendance_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $event->title) . '_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Attendance ID', 'Member ID', 'Member Name', 'Club', 'Status', 'Check-in Time', 'Remark', 'Recorded By', 'Recorder Role', 'Recorded At']);

        foreach ($rows as $r) {
            fputcsv($output, [
                $r->attendance_id,
                $r->member_id,
                $r->member_name,
                $r->club_name ?? '—',
                $r->status,
                $r->check_in_time ?? '—',
                $r->remark ?? '—',
                $r->recorded_by_name ?? '—',
                $r->recorded_by_role ?? '—',
                $r->recorded_at,
            ]);
        }

        fclose($output);
        exit();
    }
}
