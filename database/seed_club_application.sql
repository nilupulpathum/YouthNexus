-- =====================================================================
-- Mock "already submitted" applications for the Club Registration &
-- Approval process. Run AFTER youthnexus.sql (uses its seeded
-- Colombo Division id=1 and the existing coord_colombo Coordinator).
-- =====================================================================

-- Five more proposer accounts (role UnassignedUser), alongside the
-- existing 'damikrajithuru' row already in youthnexus.sql.
INSERT INTO User (username, email, password_hash, first_name, last_name, phone_number, NIC, role, status) VALUES
('ishara.j',   'ishara.jayasinghe.demo@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Ishara',   'Jayasinghe',  '0772234561', '199612345672', 'UnassignedUser', 'Active'),
('tharindu.b', 'tharindu.bandara.demo@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Tharindu', 'Bandara',     '0773234561', '199712345673', 'UnassignedUser', 'Active'),
('chamodi.r',  'chamodi.rajapaksha.demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Chamodi',  'Rajapaksha',  '0774234561', '199812345674', 'UnassignedUser', 'Active'),
('naveen.e',   'naveen.ekanayake.demo@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Naveen',   'Ekanayake',   '0775234561', '199912345675', 'UnassignedUser', 'Active'),
('sachini.w',  'sachini.weerasooriya.demo@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Sachini',  'Weerasooriya','0776234561', '199712345676', 'UnassignedUser', 'Active');

-- ---------------------------------------------------------------------
-- Application 1: Athurugiriya Chess Club — proposed by the seeded Damik user
-- ---------------------------------------------------------------------
SET @damik_id = (SELECT user_id FROM User WHERE username = 'damikrajithuru');
INSERT INTO ClubApplication (proposer_user_id, club_name, description, category, date_establishment, no_of_members,
                              proposed_division_id, location_type, street_address, city, state_province,
                              bank_name, bank_branch, account_holder, account_number, bank_confirmed,
                              constitution_path, venue_proof_path, nic_president_path,
                              info_accuracy, terms_accepted, digital_signature, status, submitted_at)
VALUES (@damik_id, 'Athurugiriya Chess Club', 'To cultivate strategic thinking and community engagement through chess.',
        'Sports & Recreation', '2026-01-15', 15,
        1, 'Community Hall', 'Athurugiriya Community Hall', 'Athurugiriya', 'Western',
        'Bank of Ceylon', 'Athurugiriya', 'Athurugiriya Chess Club', '****4821', TRUE,
        '/uploads/app1/constitution.pdf', '/uploads/app1/venue_proof.pdf', '/uploads/app1/nic_president.jpg',
        TRUE, TRUE, 'Kasun Perera', 'Pending', NOW());
SET @app1 = LAST_INSERT_ID();
INSERT INTO ExecutiveNominee (application_id, role_type, name, email, NIC, phone_number) VALUES
(@app1, 'President', 'Kasun Perera',   'kasun.perera.demo@example.com',   '199512345671', '0771234561'),
(@app1, 'Secretary', 'Nimali Fernando','nimali.fernando.demo@example.com','199823456782', '0771234562'),
(@app1, 'Treasurer', 'Ruwan Silva',    'ruwan.silva.demo@example.com',    '199734567893', '0771234563');
INSERT INTO ClubAsset (application_id, asset_name, quantity, `condition`) VALUES
(@app1, 'Chess boards & sets', 12, 'Excellent'),
(@app1, 'Chess clocks', 6, 'Good');

-- ---------------------------------------------------------------------
-- Application 2: Battaramulla Debate Society — missing NIC docs on purpose
-- ---------------------------------------------------------------------
SET @ishara_id = (SELECT user_id FROM User WHERE username = 'ishara.j');
INSERT INTO ClubApplication (proposer_user_id, club_name, description, category, date_establishment, no_of_members,
                              proposed_division_id, location_type, street_address, city, state_province,
                              bank_name, bank_branch, account_holder, account_number, bank_confirmed,
                              constitution_path,
                              info_accuracy, terms_accepted, digital_signature, status, submitted_at)
VALUES (@ishara_id, 'Battaramulla Debate Society', 'To build confident public speakers among youth.',
        'Education & Public Speaking', '2026-01-20', 20,
        1, 'Youth Center', 'Battaramulla Youth Center', 'Battaramulla', 'Western',
        'Commercial Bank', 'Battaramulla', 'Battaramulla Debate Society', '****7734', TRUE,
        '/uploads/app2/constitution.pdf',
        TRUE, TRUE, 'Ishara Jayasinghe', 'Pending', NOW());
SET @app2 = LAST_INSERT_ID();
INSERT INTO ExecutiveNominee (application_id, role_type, name, email, NIC, phone_number) VALUES
(@app2, 'President', 'Ishara Jayasinghe', 'ishara.jayasinghe.demo@example.com', '199612345672', '0772234561'),
(@app2, 'Secretary', 'Dilshan Rathnayake','dilshan.rathnayake.demo@example.com','199523456783', '0772234562'),
(@app2, 'Treasurer', 'Sanduni Wickrama',  'sanduni.wickrama.demo@example.com',  '199934567894', '0772234563');

-- ---------------------------------------------------------------------
-- Application 3: Malabe Robotics Circle — President NIC (199712345673)
-- deliberately matches proposer Tharindu's own NIC, to test the
-- "link existing account instead of creating a new one" path.
-- ---------------------------------------------------------------------
SET @tharindu_id = (SELECT user_id FROM User WHERE username = 'tharindu.b');
INSERT INTO ClubApplication (proposer_user_id, club_name, description, category, date_establishment, no_of_members,
                              proposed_division_id, location_type, street_address, city, state_province,
                              bank_name, bank_branch, account_holder, account_number, bank_confirmed,
                              constitution_path, venue_proof_path, nic_president_path,
                              info_accuracy, terms_accepted, digital_signature, status, submitted_at)
VALUES (@tharindu_id, 'Malabe Robotics Circle', 'To introduce robotics and STEM skills to Malabe youth.',
        'Science & Technology', '2026-02-01', 18,
        1, 'Community Wing', 'SLIIT Community Wing', 'Malabe', 'Western',
        'Sampath Bank', 'Malabe', 'Malabe Robotics Circle', '****9012', TRUE,
        '/uploads/app3/constitution.pdf', '/uploads/app3/venue_proof.pdf', '/uploads/app3/nic_president.jpg',
        TRUE, TRUE, 'Tharindu Bandara', 'Pending', NOW());
SET @app3 = LAST_INSERT_ID();
INSERT INTO ExecutiveNominee (application_id, role_type, name, email, NIC, phone_number) VALUES
(@app3, 'President', 'Tharindu Bandara', 'tharindu.bandara.demo@example.com', '199712345673', '0773234561'),
(@app3, 'Secretary', 'Achini Gunasekara','achini.gunasekara.demo@example.com','199823456784', '0773234562'),
(@app3, 'Treasurer', 'Lakmal De Silva',  'lakmal.desilva.demo@example.com',   '199634567895', '0773234563');
INSERT INTO ClubAsset (application_id, asset_name, quantity, `condition`) VALUES
(@app3, 'Arduino kits', 10, 'Excellent'),
(@app3, 'Laptops (shared)', 3, 'Good');
