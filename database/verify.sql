-- ============================================================================
-- YouthNexus post-install verification (moved out of youthnexus.sql, B1).
-- Usage: mysql -u root youthnexus < database/verify.sql
-- Every query below must return rows without errors after a clean install.
-- ============================================================================

SELECT user_id, username, email, role, status FROM User;
SELECT * FROM Zone;
SELECT * FROM Division;

SELECT
    a.application_id,
    a.club_name,
    a.category,
    a.no_of_members,
    a.status AS app_status,
    a.submitted_at,
    u.first_name AS proposer_first,
    u.last_name AS proposer_last,
    d.division_name
FROM ClubApplication a
JOIN User u ON a.proposer_user_id = u.user_id
LEFT JOIN Division d ON a.proposed_division_id = d.division_id
ORDER BY a.application_id DESC LIMIT 1;

SELECT
    n.role_type,
    n.name,
    n.email,
    n.NIC,
    n.phone_number
FROM ExecutiveNominee n
ORDER BY n.nominee_id DESC LIMIT 3;

-- Attendance table presence + upsert-key check (required by AttendanceModel).
SELECT COUNT(*) AS attendance_rows FROM Attendance;
SHOW INDEX FROM Attendance WHERE Key_name = 'uq_attendance_event_user';
