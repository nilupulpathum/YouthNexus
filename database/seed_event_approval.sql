-- Seed Data for Event Approval Workflow Testing (Updated)

-- Clean up previously seeded events for a fresh start (optional, but good for testing)
DELETE FROM EventTarget WHERE event_id IN (SELECT event_id FROM Event WHERE title LIKE '%Workshop%' OR title LIKE '%Campaign%' OR title LIKE '%Tournament%' OR title LIKE '%Seminar%' OR title LIKE '%Programme%' OR title LIKE '%Beach Party%');
DELETE FROM Event WHERE title LIKE '%Workshop%' OR title LIKE '%Campaign%' OR title LIKE '%Tournament%' OR title LIKE '%Seminar%' OR title LIKE '%Programme%' OR title LIKE '%Beach Party%';

-- Insert 3 Pending Events (organizer_division_id MUST be NULL for club events)
INSERT INTO Event (title, description, event_type, max_attendance, start_datetime, end_datetime, location, organizer_club_id, organizer_division_id, organizer_zonal_id, target_scope, status, created_by) VALUES
('Colombo Youth Leadership Workshop', 'A workshop to develop leadership skills among youth.', 'Workshop', 50, '2025-05-10 09:00:00', '2025-05-10 16:00:00', 'BMICH, Colombo', (SELECT club_id FROM Club WHERE club_code = 'C-BDS-001'), NULL, NULL, 'AllInScope', 'PendingApproval', (SELECT user_id FROM User WHERE username = 'bds_pres')),
('Community Clean-Up Campaign', 'Cleaning up the beach in Colombo.', 'Community Service', 100, '2025-06-05 07:00:00', '2025-06-05 12:00:00', 'Galle Face Green', (SELECT club_id FROM Club WHERE club_code = 'C-MRC-002'), NULL, NULL, 'AllInScope', 'PendingApproval', (SELECT user_id FROM User WHERE username = 'mrc_pres')),
('Inter-Club Chess Tournament', 'Annual chess tournament for all clubs in the division.', 'Sports', 80, '2025-07-15 08:30:00', '2025-07-15 17:00:00', 'Royal College Main Hall', (SELECT club_id FROM Club WHERE club_code = 'C-ACC-003'), NULL, NULL, 'SelectedClubs', 'PendingApproval', (SELECT user_id FROM User WHERE username = 'acc_pres'));

-- Add target audience for the third event
INSERT INTO EventTarget (event_id, target_club_id) VALUES
((SELECT event_id FROM Event WHERE title = 'Inter-Club Chess Tournament'), (SELECT club_id FROM Club WHERE club_code = 'C-BDS-001')),
((SELECT event_id FROM Event WHERE title = 'Inter-Club Chess Tournament'), (SELECT club_id FROM Club WHERE club_code = 'C-MRC-002'));

-- Insert 2 Approved Events
INSERT INTO Event (title, description, event_type, max_attendance, start_datetime, end_datetime, location, organizer_club_id, organizer_division_id, organizer_zonal_id, target_scope, status, created_by, approved_by) VALUES
('Youth Entrepreneurship Seminar', 'Learn how to start your own business.', 'Seminar', 150, '2025-03-20 10:00:00', '2025-03-20 15:00:00', 'NSBM Green University', (SELECT club_id FROM Club WHERE club_code = 'C-BDS-001'), NULL, NULL, 'AllInScope', 'Approved', (SELECT user_id FROM User WHERE username = 'bds_pres'), (SELECT user_id FROM User WHERE username = 'coord_colombo')),
('Digital Literacy Awareness Programme', 'Teaching basic computer skills.', 'Workshop', 60, '2025-04-12 13:00:00', '2025-04-12 17:00:00', 'Colombo Public Library', (SELECT club_id FROM Club WHERE club_code = 'C-MRC-002'), NULL, NULL, 'AllInScope', 'Approved', (SELECT user_id FROM User WHERE username = 'mrc_pres'), (SELECT user_id FROM User WHERE username = 'coord_colombo'));

-- Insert 1 Rejected Event
INSERT INTO Event (title, description, event_type, max_attendance, start_datetime, end_datetime, location, organizer_club_id, organizer_division_id, organizer_zonal_id, target_scope, status, created_by, approved_by, rejection_remarks) VALUES
('Beach Party', 'Fun time at the beach.', 'Social', 200, '2025-08-01 18:00:00', '2025-08-01 23:00:00', 'Mt Lavinia Beach', (SELECT club_id FROM Club WHERE club_code = 'C-ACC-003'), NULL, NULL, 'AllInScope', 'Rejected', (SELECT user_id FROM User WHERE username = 'acc_pres'), (SELECT user_id FROM User WHERE username = 'coord_colombo'), 'Does not align with YouthNexus core objectives.');
