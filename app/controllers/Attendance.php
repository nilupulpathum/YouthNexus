<?php

class Attendance extends Controller {

    // ------------------------------------------------------------------
    // Auth gate — identical pattern to ManageEvents::requireSecretary()
    // ------------------------------------------------------------------
    private function requireSecretary() {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'DivisionalSecretary') {
            $this->redirect('auth/signin');
        }
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
        $this->requireSecretary();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $divisionId      = (int)($_SESSION['division_id'] ?? 0);
        $attendanceModel = $this->model('AttendanceModel');

        $events = $attendanceModel->getApprovedEventsByDivision($divisionId);
        $stats  = $attendanceModel->getDivisionAttendanceStats($divisionId);

        $this->view('attendance/session-list', [
            'title'       => 'Manage Attendance — YouthNexus',
            'events'      => $events,
            'stats'       => $stats,
            'csrf_token'  => $_SESSION['csrf_token'],
            'userName'    => $_SESSION['user_name']  ?? 'N. Fernando',
            'userRole'    => 'DivisionalSecretary',
        ]);
    }

    // ------------------------------------------------------------------
    // DETAIL — single event member roster
    // Supports both HTML render and JSON (called by JS for member dropdown)
    // ------------------------------------------------------------------
    public function detail($eventId = null) {
        $this->requireSecretary();

        $eventId    = (int)$eventId;
        $divisionId = (int)($_SESSION['division_id'] ?? 0);

        if (!$eventId) {
            $this->redirect('attendance');
        }

        $attendanceModel = $this->model('AttendanceModel');

        // Server-side scope + status re-check
        $event = $attendanceModel->getApprovedEventInScope($eventId, $divisionId);
        if (!$event) {
            http_response_code(404);
            $this->redirect('attendance');
        }

        // JSON mode — called by JS to populate member dropdown
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        if (strpos($accept, 'application/json') !== false || strtolower($xReq) === 'xmlhttprequest') {
            $roster = $attendanceModel->getMemberRosterForEvent($eventId, $divisionId, $event->target_scope);
            header('Content-Type: application/json');
            echo json_encode(['members' => $roster, 'event' => $event]);
            exit();
        }

        // HTML render
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $roster = $attendanceModel->getMemberRosterForEvent($eventId, $divisionId, $event->target_scope);
        $aStats = $attendanceModel->getEventAttendanceStats($eventId);

        $present   = (int)($aStats->present_count ?? 0);
        $absent    = (int)($aStats->absent_count  ?? 0);
        $recorded  = $present + $absent;
        $total     = count($roster);
        $rate      = $total > 0 ? round(($present / $total) * 100) : 0;

        $this->view('attendance/session-detail', [
            'title'      => 'Event Attendance — YouthNexus',
            'event'      => $event,
            'roster'     => $roster,
            'present'    => $present,
            'absent'     => $absent,
            'recorded'   => $recorded,
            'total'      => $total,
            'rate'       => $rate,
            'csrf_token' => $_SESSION['csrf_token'],
            'userName'   => $_SESSION['user_name'] ?? 'N. Fernando',
            'userRole'   => 'DivisionalSecretary',
        ]);
    }

    // ------------------------------------------------------------------
    // SAVE — POST handler for both single-entry and bulk CSV
    // ------------------------------------------------------------------
    public function save() {
        $this->requireSecretary();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('attendance');
        }

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->jsonError('Invalid request. Please refresh the page.');
        }

        $divisionId      = (int)($_SESSION['division_id'] ?? 0);
        $recordedBy      = (int)$_SESSION['user_id'];
        $attendanceModel = $this->model('AttendanceModel');

        $eventId = (int)($_POST['event_id'] ?? 0);
        if (!$eventId) {
            $this->jsonError('No event selected.');
        }

        // Server-side scope + status re-check — never trust the form alone
        $event = $attendanceModel->getApprovedEventInScope($eventId, $divisionId);
        if (!$event) {
            $this->jsonError('Event not found or not in scope.', 403);
        }

        $mode = $_POST['mode'] ?? 'single';

        // ------ SINGLE ENTRY ----------------------------------------
        if ($mode === 'single') {
            $memberId    = (int)($_POST['member_id'] ?? 0);
            $status      = $_POST['status']        ?? '';
            $checkIn     = trim($_POST['check_in_time']  ?? '');
            $checkOut    = trim($_POST['check_out_time'] ?? '');
            $remark      = trim($_POST['remark']          ?? '');

            if (!$memberId || !in_array($status, ['Present', 'Absent'])) {
                $this->jsonError('Missing or invalid member or status.');
            }

            if (!$attendanceModel->memberIsInScope($memberId, $eventId, $divisionId, $event->target_scope)) {
                $this->jsonError('Member is not in scope for this division.', 403);
            }

            $attendanceModel->saveAttendance(
                $eventId, $memberId, $status,
                $checkIn  ?: null,
                $checkOut ?: null,
                $remark   ?: null,
                $recordedBy
            );

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();
        }

        // ------ BULK CSV -------------------------------------------
        if ($mode === 'bulk') {
            if (empty($_FILES['csv_file']['tmp_name'])) {
                $this->jsonError('No CSV file uploaded.');
            }

            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if (!$handle) {
                $this->jsonError('Could not read uploaded file.');
            }

            // Skip header row
            $header = fgetcsv($handle);

            $saved   = 0;
            $skipped = [];
            $rowNum  = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;

                // Assumed column order: member_id, status, check_in_time, remark
                // NOTE: This column format is UNCONFIRMED — flagged for Nimesh to verify.
                $memberId   = isset($row[0]) ? (int)trim($row[0]) : 0;
                $status     = isset($row[1]) ? trim($row[1]) : '';
                $checkIn    = isset($row[2]) ? trim($row[2]) : null;
                $remark     = isset($row[3]) ? trim($row[3]) : null;

                if (!$memberId) {
                    $skipped[] = ['row' => $rowNum, 'member_id' => $row[0] ?? '', 'reason' => 'Missing or non-numeric member_id'];
                    continue;
                }

                if (!in_array($status, ['Present', 'Absent'])) {
                    $skipped[] = ['row' => $rowNum, 'member_id' => $memberId, 'reason' => "Invalid status '{$status}' — must be Present or Absent"];
                    continue;
                }

                if (!$attendanceModel->memberIsInScope($memberId, $eventId, $divisionId, $event->target_scope)) {
                    $skipped[] = ['row' => $rowNum, 'member_id' => $memberId, 'reason' => 'Member not in scope for this division/event'];
                    continue;
                }

                $attendanceModel->saveAttendance(
                    $eventId, $memberId, $status,
                    $checkIn  ?: null,
                    null,           // check_out_time not in CSV spec
                    $remark   ?: null,
                    $recordedBy
                );
                $saved++;
            }
            fclose($handle);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'saved' => $saved, 'skipped' => $skipped]);
            exit();
        }

        $this->jsonError('Unknown save mode.');
    }

    // ------------------------------------------------------------------
    // UPDATESTATUS — Quick Update panel (single-member status change)
    // ------------------------------------------------------------------
    public function updatestatus() {
        $this->requireSecretary();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('POST required.');
        }

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->jsonError('Invalid request. Please refresh the page.');
        }

        $divisionId      = (int)($_SESSION['division_id'] ?? 0);
        $recordedBy      = (int)$_SESSION['user_id'];
        $attendanceModel = $this->model('AttendanceModel');

        $eventId  = (int)($_POST['event_id']  ?? 0);
        $memberId = (int)($_POST['member_id'] ?? 0);
        $status   = $_POST['status'] ?? '';

        if (!$eventId || !$memberId || !in_array($status, ['Present', 'Absent'])) {
            $this->jsonError('Missing or invalid fields.');
        }

        // Scope re-check
        $event = $attendanceModel->getApprovedEventInScope($eventId, $divisionId);
        if (!$event) {
            $this->jsonError('Event not found or not in scope.', 403);
        }

        if (!$attendanceModel->memberIsInScope($memberId, $eventId, $divisionId, $event->target_scope)) {
            $this->jsonError('Member is not in scope for this division.', 403);
        }

        $checkIn  = trim($_POST['check_in_time']  ?? '');
        $checkOut = trim($_POST['check_out_time'] ?? '');
        $remark   = trim($_POST['remark']          ?? '') ?: null;

        $checkInVal = null;
        if (!empty($checkIn)) {
            $ts = strtotime($checkIn);
            if ($ts !== false) {
                $checkInVal = date('Y-m-d H:i:s', $ts);
            }
        }

        $checkOutVal = null;
        if (!empty($checkOut)) {
            $ts = strtotime($checkOut);
            if ($ts !== false) {
                $checkOutVal = date('Y-m-d H:i:s', $ts);
            }
        }

        try {
            $attendanceModel->saveAttendance(
                $eventId, $memberId, $status,
                $checkInVal, $checkOutVal, $remark,
                $recordedBy
            );

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();
        } catch (Throwable $e) {
            $this->jsonError('Failed to save attendance: ' . $e->getMessage(), 500);
        }
    }
}
