-- Seed Data for Event Approval Workflow Testing

-- Insert 3 active clubs in Division 1 (Colombo Division)
INSERT IGNORE INTO Club (club_name, description, division_id, registration_date, status, club_code) VALUES
('Battaramulla Debate Society', 'Fostering debate and public speaking', 1, '2023-01-10', 'Active', 'C-BDS-001'),
('Malabe Robotics Circle', 'Building the future through robotics', 1, '2023-02-15', 'Active', 'C-MRC-002'),
('Athurugiriya Chess Club', 'Developing strategic minds', 1, '2023-03-20', 'Active', 'C-ACC-003');

-- Insert Club Presidents for these clubs to act as event creators
INSERT IGNORE INTO User (username, email, password_hash, first_name, last_name, role, status, club_id, division_id) VALUES
('bds_pres', 'pres@bds.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Ruwan', 'Silva', 'ClubPresident', 'Active', (SELECT club_id FROM Club WHERE club_code = 'C-BDS-001'), 1),
('mrc_pres', 'pres@mrc.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Kamal', 'Perera', 'ClubPresident', 'Active', (SELECT club_id FROM Club WHERE club_code = 'C-MRC-002'), 1),
('acc_pres', 'pres@acc.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Saman', 'Kumara', 'ClubPresident', 'Active', (SELECT club_id FROM Club WHERE club_code = 'C-ACC-003'), 1);

-- Insert 3 Pending Events
INSERT INTO Event (title, description, event_type, max_attendance, start_datetime, end_datetime, location, organizer_club_id, organizer_division_id, organizer_zonal_id, target_scope, status, created_by) VALUES
('Colombo Youth Leadership Workshop', 'A workshop to develop leadership skills among youth.', 'Workshop', 50, '2024-05-10 09:00:00', '2024-05-10 16:00:00', 'BMICH, Colombo', (SELECT club_id FROM Club WHERE club_code = 'C-BDS-001'), 1, 1, 'AllInScope', 'PendingApproval', (SELECT user_id FROM User WHERE username = 'bds_pres')),
('Community Clean-Up Campaign', 'Cleaning up the beach in Colombo.', 'Community Service', 100, '2024-06-05 07:00:00', '2024-06-05 12:00:00', 'Galle Face Green', (SELECT club_id FROM Club WHERE club_code = 'C-MRC-002'), 1, 1, 'AllInScope', 'PendingApproval', (SELECT user_id FROM User WHERE username = 'mrc_pres')),
('Inter-Club Chess Tournament', 'Annual chess tournament for all clubs in the division.', 'Sports', 80, '2024-07-15 08:30:00', '2024-07-15 17:00:00', 'Royal College Main Hall', (SELECT club_id FROM Club WHERE club_code = 'C-ACC-003'), 1, 1, 'SelectedClubs', 'PendingApproval', (SELECT user_id FROM User WHERE username = 'acc_pres'));

-- Add target audience for the third event
INSERT INTO EventTarget (event_id, target_club_id) VALUES
((SELECT event_id FROM Event WHERE title = 'Inter-Club Chess Tournament'), (SELECT club_id FROM Club WHERE club_code = 'C-BDS-001')),
((SELECT event_id FROM Event WHERE title = 'Inter-Club Chess Tournament'), (SELECT club_id FROM Club WHERE club_code = 'C-MRC-002'));

-- Insert 2 Approved Events
INSERT INTO Event (title, description, event_type, max_attendance, start_datetime, end_datetime, location, organizer_club_id, organizer_division_id, organizer_zonal_id, target_scope, status, created_by, approved_by) VALUES
('Youth Entrepreneurship Seminar', 'Learn how to start your own business.', 'Seminar', 150, '2024-03-20 10:00:00', '2024-03-20 15:00:00', 'NSBM Green University', (SELECT club_id FROM Club WHERE club_code = 'C-BDS-001'), 1, 1, 'AllInScope', 'Approved', (SELECT user_id FROM User WHERE username = 'bds_pres'), (SELECT user_id FROM User WHERE username = 'coord_colombo')),
('Digital Literacy Awareness Programme', 'Teaching basic computer skills.', 'Workshop', 60, '2024-04-12 13:00:00', '2024-04-12 17:00:00', 'Colombo Public Library', (SELECT club_id FROM Club WHERE club_code = 'C-MRC-002'), 1, 1, 'AllInScope', 'Approved', (SELECT user_id FROM User WHERE username = 'mrc_pres'), (SELECT user_id FROM User WHERE username = 'coord_colombo'));

-- Insert 1 Rejected Event
INSERT INTO Event (title, description, event_type, max_attendance, start_datetime, end_datetime, location, organizer_club_id, organizer_division_id, organizer_zonal_id, target_scope, status, created_by, approved_by, rejection_remarks) VALUES
('Beach Party', 'Fun time at the beach.', 'Social', 200, '2024-08-01 18:00:00', '2024-08-01 23:00:00', 'Mt Lavinia Beach', (SELECT club_id FROM Club WHERE club_code = 'C-ACC-003'), 1, 1, 'AllInScope', 'Rejected', (SELECT user_id FROM User WHERE username = 'acc_pres'), (SELECT user_id FROM User WHERE username = 'coord_colombo'), 'Does not align with YouthNexus core objectives.');
