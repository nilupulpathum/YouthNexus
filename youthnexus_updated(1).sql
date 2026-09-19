-- ============================================
-- youthnexus_updated.sql
-- Complete database with all tables + seed data
-- ============================================

CREATE DATABASE IF NOT EXISTS youthnexus;
USE youthnexus;

-- ============================================
-- 1. BASE TABLES (no FK dependencies)
-- ============================================

CREATE TABLE Zone (
    zonal_id    INT AUTO_INCREMENT PRIMARY KEY,
    zonal_name  VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE Division (
    division_id    INT AUTO_INCREMENT PRIMARY KEY,
    division_name  VARCHAR(100) NOT NULL,
    zonal_id       INT NOT NULL,
    FOREIGN KEY (zonal_id) REFERENCES Zone(zonal_id),
    UNIQUE (division_name, zonal_id)
);

-- ============================================
-- 2. CLUB APPLICATION (needed before Club)
-- ============================================

CREATE TABLE ClubApplication (
    application_id        INT AUTO_INCREMENT PRIMARY KEY,
    proposer_user_id      INT NOT NULL,
    club_name             VARCHAR(150) NOT NULL,
    description           VARCHAR(500) DEFAULT NULL,
    club_logo_path        VARCHAR(500) DEFAULT NULL,
    category              VARCHAR(50) DEFAULT NULL,
    date_establishment    DATE DEFAULT NULL,
    no_of_members         INT DEFAULT 0,
    proposed_division_id  INT DEFAULT NULL,
    location_type         VARCHAR(50) DEFAULT NULL,
    street_address        VARCHAR(500) DEFAULT NULL,
    city                  VARCHAR(100) DEFAULT NULL,
    state_province        VARCHAR(100) DEFAULT NULL,
    postal_code           VARCHAR(20) DEFAULT NULL,
    country               VARCHAR(80) DEFAULT 'Sri Lanka',
    bank_name             VARCHAR(100) DEFAULT NULL,
    bank_branch           VARCHAR(100) DEFAULT NULL,
    account_holder        VARCHAR(200) DEFAULT NULL,
    account_number        VARCHAR(50) DEFAULT NULL,
    bank_confirmed        BOOLEAN DEFAULT FALSE,
    constitution_path     VARCHAR(500) DEFAULT NULL,
    venue_proof_path      VARCHAR(500) DEFAULT NULL,
    nic_president_path    VARCHAR(500) DEFAULT NULL,
    nic_secretary_path    VARCHAR(500) DEFAULT NULL,
    nic_treasurer_path    VARCHAR(500) DEFAULT NULL,
    info_accuracy         BOOLEAN DEFAULT FALSE,
    terms_accepted        BOOLEAN DEFAULT FALSE,
    digital_signature     VARCHAR(200) DEFAULT NULL,
    status                ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    submitted_at          TIMESTAMP NULL DEFAULT NULL,
    reviewed_by           INT DEFAULT NULL,
    reviewed_at           TIMESTAMP NULL DEFAULT NULL,
    rejection_remarks     VARCHAR(500) DEFAULT NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proposed_division_id) REFERENCES Division(division_id),
    INDEX idx_app_status (status),
    INDEX idx_proposer (proposer_user_id),
    INDEX idx_division (proposed_division_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. CLUB
-- ============================================

CREATE TABLE Club (
    club_id              INT AUTO_INCREMENT PRIMARY KEY,
    club_name            VARCHAR(150) NOT NULL,
    description          VARCHAR(500),
    division_id          INT NOT NULL,
    registration_date    DATE NOT NULL,
    status               ENUM('Pending', 'Active', 'Flagged', 'Disbanded') NOT NULL DEFAULT 'Pending',
    no_of_members        INT NOT NULL DEFAULT 0,
    club_code            VARCHAR(20) NOT NULL UNIQUE,
    overall_health_score  DECIMAL(5,2) DEFAULT 0,
    health_status        ENUM('Green', 'Yellow', 'Red') DEFAULT 'Green',
    flagged              BOOLEAN NOT NULL DEFAULT FALSE,
    source_application_id INT NULL,
    FOREIGN KEY (division_id) REFERENCES Division(division_id),
    UNIQUE (club_name, division_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add FK to ClubApplication after both tables exist
ALTER TABLE Club ADD FOREIGN KEY (source_application_id) REFERENCES ClubApplication(application_id);

-- ============================================
-- 4. USER
-- ============================================

CREATE TABLE User (
    user_id                     INT AUTO_INCREMENT PRIMARY KEY,
    username                    VARCHAR(50)  NOT NULL UNIQUE,
    email                       VARCHAR(100) NOT NULL UNIQUE,
    password_hash               VARCHAR(255) NOT NULL,
    status                      ENUM('Active', 'Suspended', 'Disabled') NOT NULL DEFAULT 'Active',
    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at               TIMESTAMP NULL,
    first_name                  VARCHAR(50)  NOT NULL,
    last_name                   VARCHAR(50)  NOT NULL,
    phone_number                VARCHAR(20),
    NIC                         VARCHAR(20)  NULL UNIQUE,
    address                     VARCHAR(255),
    date_of_birth               DATE         NULL,
    guardian_consent            BOOLEAN,
    profile_picture_url         VARCHAR(255),
    email_notifications_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    role                        ENUM('ClubMember','ClubPresident','ClubSecretary','ClubTreasurer','DivisionalCoordinator','DivisionalSecretary','DivisionalTreasurer','ZonalCoordinator','ZonalSecretary','ZonalTreasurer','NYSCAdministrator','UnassignedUser') NOT NULL DEFAULT 'UnassignedUser',
    club_id                     INT NULL,
    division_id                 INT NULL,
    zonal_id                    INT NULL,
    membership_date             DATE,
    membership_status           ENUM('Active', 'Inactive'),
    term_start_date             DATE,
    term_end_date               DATE,
    assigned_date               DATE,
    eligibility_checked        BOOLEAN,
    FOREIGN KEY (club_id) REFERENCES Club(club_id),
    FOREIGN KEY (division_id) REFERENCES Division(division_id),
    FOREIGN KEY (zonal_id) REFERENCES Zone(zonal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Now add FKs to ClubApplication that reference User
ALTER TABLE ClubApplication ADD FOREIGN KEY (proposer_user_id) REFERENCES User(user_id);
ALTER TABLE ClubApplication ADD FOREIGN KEY (reviewed_by) REFERENCES User(user_id);

-- ============================================
-- 5. PASSWORD RESET
-- ============================================

CREATE TABLE PasswordReset (
    reset_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    otp_code    VARCHAR(255) NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    is_used     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. EXECUTIVE NOMINEE
-- ============================================

CREATE TABLE ExecutiveNominee (
    nominee_id      INT AUTO_INCREMENT PRIMARY KEY,
    application_id  INT NOT NULL,
    role_type       ENUM('President', 'Secretary', 'Treasurer') NOT NULL,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(100) DEFAULT NULL,
    NIC             VARCHAR(20) DEFAULT NULL,
    phone_number    VARCHAR(20) DEFAULT NULL,
    date_of_birth   DATE DEFAULT NULL,
    photo_path      VARCHAR(500) DEFAULT NULL,
    index_number    VARCHAR(30) DEFAULT NULL,
    FOREIGN KEY (application_id) REFERENCES ClubApplication(application_id) ON DELETE CASCADE,
    INDEX idx_app_nominee (application_id),
    INDEX idx_role_type (role_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. CLUB ASSET
-- ============================================

CREATE TABLE ClubAsset (
    asset_id        INT AUTO_INCREMENT PRIMARY KEY,
    application_id  INT NOT NULL,
    asset_name      VARCHAR(200) NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    `condition`     ENUM('Excellent', 'Good', 'Fair', 'Poor') DEFAULT 'Good',
    photo_path      VARCHAR(500) DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES ClubApplication(application_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. CLUB APPLICATION PHOTO
-- ============================================

CREATE TABLE ClubApplicationPhoto (
    photo_id        INT AUTO_INCREMENT PRIMARY KEY,
    application_id  INT NOT NULL,
    photo_path      VARCHAR(500) NOT NULL,
    uploaded_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES ClubApplication(application_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. CERTIFICATE
-- ============================================

CREATE TABLE Certificate (
    certificate_id   INT AUTO_INCREMENT PRIMARY KEY,
    owner_type       ENUM('Club', 'Member') NOT NULL,
    owner_id         INT NOT NULL,
    certificate_type ENUM('ClubRegistration', 'Volunteer') NOT NULL DEFAULT 'ClubRegistration',
    qr_code          VARCHAR(500) DEFAULT NULL,
    issued_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pdf_url          VARCHAR(500) DEFAULT NULL,
    INDEX idx_owner (owner_type, owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. AUDIT LOG
-- ============================================

CREATE TABLE AuditLog (
    log_id          INT AUTO_INCREMENT PRIMARY KEY,
    actor_user_id   INT NOT NULL,
    action_type     VARCHAR(50) NOT NULL,
    target_entity   VARCHAR(50) NOT NULL,
    target_id       INT NOT NULL,
    `timestamp`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    details         VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (actor_user_id) REFERENCES User(user_id),
    INDEX idx_action (action_type),
    INDEX idx_actor (actor_user_id),
    INDEX idx_target (target_entity, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. EVENT
-- ============================================

CREATE TABLE Event (
    event_id              INT AUTO_INCREMENT PRIMARY KEY,
    title                 VARCHAR(150) NOT NULL,
    description           VARCHAR(1000),
    event_type            VARCHAR(50),
    max_attendance        INT,
    start_datetime        DATETIME NOT NULL,
    end_datetime          DATETIME NOT NULL,
    location              VARCHAR(255),
    organizer_club_id     INT NULL,
    organizer_division_id INT NULL,
    organizer_zonal_id    INT NULL,
    target_scope          ENUM('AllInScope','SelectedClubs') NOT NULL,
    status                ENUM('Draft','PendingApproval','Approved','Rejected','Completed') NOT NULL DEFAULT 'PendingApproval',
    created_by            INT NOT NULL,
    approved_by           INT NULL,
    rejection_remarks     VARCHAR(500) NULL,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_club_id) REFERENCES Club(club_id),
    FOREIGN KEY (organizer_division_id) REFERENCES Division(division_id),
    FOREIGN KEY (organizer_zonal_id) REFERENCES Zone(zonal_id),
    FOREIGN KEY (created_by) REFERENCES User(user_id),
    FOREIGN KEY (approved_by) REFERENCES User(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. EVENT TARGET
-- ============================================

CREATE TABLE EventTarget (
    target_id          INT AUTO_INCREMENT PRIMARY KEY,
    event_id           INT NOT NULL,
    target_club_id     INT NULL,
    max_attendance     INT NULL,
    target_division_id INT NULL,
    target_zonal_id    INT NULL,
    FOREIGN KEY (event_id) REFERENCES Event(event_id),
    FOREIGN KEY (target_club_id) REFERENCES Club(club_id),
    FOREIGN KEY (target_division_id) REFERENCES Division(division_id),
    FOREIGN KEY (target_zonal_id) REFERENCES Zone(zonal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. LEDGER (new)
-- ============================================

CREATE TABLE Ledger (
    ledger_id       INT AUTO_INCREMENT PRIMARY KEY,
    owner_type      ENUM('Club','Division','Zone') NOT NULL,
    owner_id        INT NOT NULL,
    current_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    status          ENUM('Active','Closed') NOT NULL DEFAULT 'Active',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. LEDGER ENTRY (new)
-- ============================================

CREATE TABLE LedgerEntry (
    entry_id    INT AUTO_INCREMENT PRIMARY KEY,
    ledger_id   INT NOT NULL,
    amount      DECIMAL(15,2) NOT NULL,
    type        ENUM('Income','Expense') NOT NULL,
    status      ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    date        DATE NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (ledger_id) REFERENCES Ledger(ledger_id),
    INDEX idx_ledger (ledger_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. CLUB HEALTH SCORE (new)
-- ============================================

CREATE TABLE ClubHealthScore (
    health_score_id   INT AUTO_INCREMENT PRIMARY KEY,
    club_id           INT NOT NULL,
    event_score       DECIMAL(5,2) DEFAULT 0,
    attendance_score  DECIMAL(5,2) DEFAULT 0,
    financial_score   DECIMAL(5,2) DEFAULT 0,
    total_score       DECIMAL(5,2) DEFAULT 0,
    status            ENUM('Green','Yellow','Red') DEFAULT 'Green',
    calculated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES Club(club_id),
    INDEX idx_club (club_id),
    INDEX idx_calc (calculated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 16. VOLUNTEER HISTORY (new)
-- ============================================

CREATE TABLE VolunteerHistory (
    log_id      INT AUTO_INCREMENT PRIMARY KEY,
    member_id   INT NOT NULL,
    date        DATE NOT NULL,
    hours       INT NOT NULL DEFAULT 0,
    status      ENUM('Pending','Verified','Fail') NOT NULL DEFAULT 'Pending',
    description VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (member_id) REFERENCES User(user_id),
    INDEX idx_member (member_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 17. RED FLAG (new)
-- ============================================

CREATE TABLE RedFlag (
    red_flag_id   INT AUTO_INCREMENT PRIMARY KEY,
    entry_id      INT NULL,
    audit_id      INT NULL,
    reason        VARCHAR(255) NOT NULL,
    status        ENUM('Unresolved','Resolved') NOT NULL DEFAULT 'Unresolved',
    flagged_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at   TIMESTAMP NULL,
    FOREIGN KEY (entry_id) REFERENCES LedgerEntry(entry_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 18. AUDIT (new)
-- ============================================

CREATE TABLE Audit (
    audit_id       INT AUTO_INCREMENT PRIMARY KEY,
    club_id        INT NOT NULL,
    financial_year INT NOT NULL,
    audit_status   ENUM('InProgress','Completed','Overdue') NOT NULL DEFAULT 'InProgress',
    sign_off_date  DATE NULL,
    locked         BOOLEAN NOT NULL DEFAULT FALSE,
    due_date       DATE NOT NULL,
    FOREIGN KEY (club_id) REFERENCES Club(club_id),
    INDEX idx_status (audit_status),
    INDEX idx_due (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 19. REPORT (new)
-- ============================================

CREATE TABLE Report (
    report_id     INT AUTO_INCREMENT PRIMARY KEY,
    type          VARCHAR(50) NOT NULL,
    scope_level   ENUM('Division','Zone','National') NOT NULL,
    scope_id      INT NOT NULL,
    generated_by  INT NULL,
    generated_at  TIMESTAMP NULL,
    status        ENUM('Submitted','Missing','Late') NOT NULL DEFAULT 'Missing',
    period        VARCHAR(20) NOT NULL,
    FOREIGN KEY (generated_by) REFERENCES User(user_id),
    INDEX idx_scope (scope_level, scope_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 20. ASSET TRANSFER (new)
-- ============================================

CREATE TABLE AssetTransfer (
    transfer_id     INT AUTO_INCREMENT PRIMARY KEY,
    asset_id        INT NOT NULL,
    from_owner_level VARCHAR(50) NOT NULL,
    from_owner_id   INT NOT NULL,
    to_owner_level  VARCHAR(50) NOT NULL,
    to_owner_id     INT NOT NULL,
    transfer_date   DATE NOT NULL,
    status          ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    approved_by     INT NULL,
    approved_at     TIMESTAMP NULL,
    FOREIGN KEY (asset_id) REFERENCES ClubAsset(asset_id),
    FOREIGN KEY (approved_by) REFERENCES User(user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA
-- ============================================

-- Zones
INSERT INTO Zone (zonal_id, zonal_name) VALUES
(1, 'Western Zone'),
(2, 'Central Zone'),
(3, 'Southern Zone'),
(4, 'Eastern Zone'),
(5, 'Northern Zone');

-- Divisions
INSERT INTO Division (division_id, division_name, zonal_id) VALUES
(1, 'Colombo Division', 1),
(2, 'Gampaha Division', 1),
(3, 'Kalutara Division', 1),
(4, 'Kandy Division', 2),
(5, 'Galle Division', 3),
(6, 'Matara Division', 3),
(7, 'Hambantota Division', 3),
(8, 'Trincomalee Division', 4),
(9, 'Batticaloa Division', 4),
(10, 'Ampara Division', 4),
(11, 'Jaffna Division', 5),
(12, 'Mannar Division', 5),
(13, 'Vavuniya Division', 5),
(14, 'Matale Division', 2),
(15, 'Nuwara Eliya Division', 2);

-- Admin user (must be first since ClubApplication references User)
INSERT INTO User (user_id, username, email, password_hash, first_name, last_name, role, status) 
VALUES (1, 'admin', 'admin@youthnexus.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'National', 'Admin', 'NYSCAdministrator', 'Active');

-- Test user
INSERT INTO User (user_id, username, email, password_hash, first_name, last_name, role, status) 
VALUES (2, 'testuser', 'test@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1n5S3J2h1xV8Vz.N9gXyM0Y6Kz2Kq6S', 'Test', 'User', 'UnassignedUser', 'Active');

-- Divisional Coordinators
INSERT INTO User (user_id, username, email, password_hash, first_name, last_name, phone_number, NIC, role, status, division_id) VALUES
(3, 'damikrajithuru', 'damikarajithuru@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Damik', 'Rajithuru', '0771234567', '200012345678', 'DivisionalCoordinator', 'Active', 2),
(4, 'coord_colombo', 'colombo@youthnexus.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Sunil', 'Perera', '0771111111', '198512345678', 'DivisionalCoordinator', 'Active', 1),
(5, 'coord_kandy', 'kandy@youthnexus.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Kamal', 'Fernando', '0772222222', '198623456789', 'DivisionalCoordinator', 'Active', 4),
(6, 'coord_batti', 'batti@youthnexus.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Ravi', 'Sivakumar', '0773333333', '198734567890', 'ZonalCoordinator', 'Active', 9);

-- Divisional Secretaries
INSERT INTO User (user_id, username, email, password_hash, first_name, last_name, phone_number, role, status, division_id) VALUES
(7, 'sec_gampaha', 'damikarajithuru2@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Damik', 'Rajithuru', '0771122334', 'DivisionalSecretary', 'Active', 2),
(8, 'sec_batticaloa', 'sec.batti@youthnexus.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Nimal', 'Ranasinghe', '0774444444', 'DivisionalSecretary', 'Active', 9);

-- Clubs (30 clubs across divisions)
INSERT INTO Club (club_id, club_name, description, division_id, registration_date, status, no_of_members, club_code, health_status) VALUES
(1, 'Gampaha Youth Development Club', 'Active youth empowerment club', 2, '2026-01-15', 'Active', 45, 'CLB-GAM-001', 'Green'),
(2, 'Colombo Central Youth', 'Youth leadership club', 1, '2026-02-10', 'Active', 78, 'CLB-COL-001', 'Green'),
(3, 'Kalmunai Youth Forward', 'Community service club', 9, '2026-03-05', 'Active', 32, 'CLB-BAT-001', 'Red'),
(4, 'Kandy Hills Youth Club', 'Sports and culture', 4, '2026-01-20', 'Active', 56, 'CLB-KAN-001', 'Green'),
(5, 'Galle Coastal Youth', 'Environmental club', 5, '2026-04-12', 'Active', 41, 'CLB-GAL-001', 'Green'),
(6, 'Matara Young Leaders', 'Leadership training', 6, '2026-02-28', 'Active', 38, 'CLB-MAT-001', 'Yellow'),
(7, 'Trinco Coastal Youth', 'Marine conservation', 8, '2026-08-20', 'Pending', 25, 'CLB-TRI-001', 'Green'),
(8, 'Jaffna Northern Stars', 'Arts and culture', 11, '2026-05-15', 'Active', 62, 'CLB-JAF-001', 'Green'),
(9, 'Kalutara Youth Force', 'Social welfare', 3, '2026-03-22', 'Active', 29, 'CLB-KAL-001', 'Yellow'),
(10, 'Hambantota Youth Power', 'Agriculture club', 7, '2026-06-10', 'Active', 33, 'CLB-HAM-001', 'Green'),
(11, 'Ampara Rising Youth', 'Education support', 10, '2026-07-01', 'Active', 27, 'CLB-AMP-001', 'Red'),
(12, 'Mannar Youth Bridge', 'Peace building', 12, '2026-04-05', 'Active', 19, 'CLB-MAN-001', 'Yellow'),
(13, 'Vavuniya Youth Vision', 'Technology club', 13, '2026-05-20', 'Active', 22, 'CLB-VAV-001', 'Green'),
(14, 'Matale Mountain Youth', 'Hiking and nature', 14, '2026-02-14', 'Active', 35, 'CLB-MAT-002', 'Green'),
(15, 'Nuwara Eliya Youth', 'Tea community club', 15, '2026-03-30', 'Active', 28, 'CLB-NUW-001', 'Yellow'),
(16, 'Colombo South Youth', 'Urban development', 1, '2026-06-01', 'Active', 51, 'CLB-COL-002', 'Green'),
(17, 'Gampaha North Youth', 'Rural development', 2, '2026-07-15', 'Active', 42, 'CLB-GAM-002', 'Green'),
(18, 'Kandy Central Youth', 'Music and dance', 4, '2026-01-25', 'Active', 47, 'CLB-KAN-002', 'Green'),
(19, 'Galle Fort Youth', 'Heritage club', 5, '2026-08-01', 'Active', 30, 'CLB-GAL-002', 'Yellow'),
(20, 'Batticaloa East Youth', 'Fishermen welfare', 9, '2026-04-18', 'Flagged', 24, 'CLB-BAT-002', 'Red'),
(21, 'Trincomalee Harbour Youth', 'Port community', 8, '2026-05-25', 'Active', 36, 'CLB-TRI-002', 'Green'),
(22, 'Jaffna University Youth', 'Academic club', 11, '2026-06-20', 'Active', 55, 'CLB-JAF-002', 'Green'),
(23, 'Colombo West Youth', 'Sports academy', 1, '2026-07-10', 'Active', 63, 'CLB-COL-003', 'Green'),
(24, 'Kandy South Youth', 'Cricket club', 4, '2026-03-12', 'Active', 48, 'CLB-KAN-003', 'Yellow'),
(25, 'Gampaha East Youth', 'Cycling club', 2, '2026-08-05', 'Active', 31, 'CLB-GAM-003', 'Green'),
(26, 'Matara South Youth', 'Surf club', 6, '2026-02-20', 'Active', 26, 'CLB-MAT-002', 'Red'),
(27, 'Kalutara North Youth', 'River conservation', 3, '2026-05-01', 'Active', 34, 'CLB-KAL-002', 'Green'),
(28, 'Hambantota South Youth', 'Wildlife club', 7, '2026-06-15', 'Active', 21, 'CLB-HAM-002', 'Yellow'),
(29, 'Ampara Central Youth', 'Farmers club', 10, '2026-07-20', 'Active', 18, 'CLB-AMP-002', 'Red'),
(30, 'Vavuniya South Youth', 'Women empowerment', 13, '2026-04-25', 'Active', 23, 'CLB-VAV-002', 'Green');

-- Club Presidents and Members (users linked to clubs)
INSERT INTO User (user_id, username, email, password_hash, first_name, last_name, phone_number, role, status, club_id, division_id) VALUES
(9, 'clubpresident_gampaha', 'club.gampaha@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Nuwan', 'Bandara', '0775566778', 'ClubPresident', 'Active', 1, 2),
(10, 'president_colombo', 'pres.colombo@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Saman', 'Silva', '0776666666', 'ClubPresident', 'Active', 2, 1),
(11, 'president_kandy', 'pres.kandy@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Nadeesha', 'Perera', '0777777777', 'ClubPresident', 'Active', 4, 4),
(12, 'treasurer_gampaha', 'treas.gampaha@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Kasun', 'Wijesinghe', '0778888888', 'ClubTreasurer', 'Active', 1, 2),
(13, 'member1', 'member1@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Amal', 'Jayasinghe', '0779999999', 'ClubMember', 'Active', 1, 2),
(14, 'member2', 'member2@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Sajith', 'Kumar', '0770000000', 'ClubMember', 'Active', 2, 1),
(15, 'member3', 'member3@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Fathima', 'Rizwan', '0781111111', 'ClubMember', 'Active', 3, 9),
(16, 'member4', 'member4@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Dinesh', 'Rajapaksa', '0782222222', 'ClubMember', 'Active', 4, 4),
(17, 'member5', 'member5@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Chamari', 'Athapattu', '0783333333', 'ClubMember', 'Active', 5, 5),
(18, 'member6', 'member6@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Lahiru', 'Kumara', '0784444444', 'ClubMember', 'Active', 6, 6),
(19, 'member7', 'member7@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Tharindu', 'Silva', '0785555555', 'ClubMember', 'Active', 8, 11),
(20, 'member8', 'member8@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Ishara', 'Fernando', '0786666666', 'ClubMember', 'Active', 10, 7),
(21, 'member9', 'member9@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Roshan', 'Mendis', '0787777777', 'ClubMember', 'Active', 14, 14),
(22, 'member10', 'member10@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Anjali', 'Weerasinghe', '0788888888', 'ClubMember', 'Active', 16, 1),
(23, 'member11', 'member11@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Suresh', 'Gunaratne', '0789999999', 'ClubMember', 'Active', 18, 4),
(24, 'member12', 'member12@youthnexus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O', 'Dilani', 'Samaraweera', '0790000000', 'ClubMember', 'Active', 22, 11);

-- More members to reach higher youth count
INSERT INTO User (username, email, password_hash, first_name, last_name, role, status, club_id, division_id)
SELECT 
    CONCAT('member', seq),
    CONCAT('member', seq, '@youthnexus.com'),
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O',
    CONCAT('First', seq),
    CONCAT('Last', seq),
    'ClubMember',
    'Active',
    (seq % 30) + 1,
    ((seq % 30) % 15) + 1
FROM (
    SELECT @row := @row + 1 as seq
    FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t1,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t2,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t3,
         (SELECT @row := 24) t4
) numbers
WHERE seq > 24 AND seq <= 200;

-- Club Applications (some pending, some approved)
INSERT INTO ClubApplication (application_id, proposer_user_id, club_name, description, no_of_members, proposed_division_id, status, submitted_at) VALUES
(1, 2, 'Negombo Youth Wave', 'Beach cleanup and marine life', 20, 2, 'Pending', '2026-08-25 10:00:00'),
(2, 2, 'Panadura Youth Club', 'Sports and recreation', 15, 3, 'Pending', '2026-08-26 14:30:00'),
(3, 2, 'Badulla Youth Force', 'Agriculture and farming', 18, 14, 'Pending', '2026-08-27 09:15:00'),
(4, 2, 'Ratnapura Gem Youth', 'Gem industry training', 12, 14, 'Pending', '2026-08-20 11:00:00'),
(5, 2, 'Polonnaruwa Heritage Youth', 'Archaeology and history', 22, 4, 'Pending', '2026-08-22 16:00:00'),
(6, 2, 'Anuradhapura Ancient Youth', 'Cultural preservation', 25, 4, 'Pending', '2026-08-23 08:30:00'),
(7, 2, 'Kegalle Youth Club', 'Rubber industry training', 14, 2, 'Pending', '2026-08-24 13:00:00'),
(8, 2, 'Kurunegala Youth Power', 'Coconut industry', 19, 2, 'Pending', '2026-08-21 10:30:00'),
(9, 2, 'Puttalam Coastal Youth', 'Fishing community', 16, 2, 'Pending', '2026-08-19 15:00:00'),
(10, 2, 'Monaragala Youth Vision', 'Forest conservation', 11, 4, 'Pending', '2026-08-18 09:00:00'),
(11, 2, 'Gampaha Youth Rising', 'Education support', 30, 2, 'Approved', '2026-08-01 10:00:00'),
(12, 2, 'Colombo Youth Stars', 'Technology training', 28, 1, 'Approved', '2026-08-02 14:00:00');

-- Update some clubs with source_application_id
UPDATE Club SET source_application_id = 11 WHERE club_id = 25;
UPDATE Club SET source_application_id = 12 WHERE club_id = 26;

-- Ledger for clubs and divisions
INSERT INTO Ledger (ledger_id, owner_type, owner_id, current_balance, status) VALUES
(1, 'Club', 1, 850000.00, 'Active'),
(2, 'Club', 2, 1200000.00, 'Active'),
(3, 'Club', 3, 320000.00, 'Active'),
(4, 'Club', 4, 950000.00, 'Active'),
(5, 'Club', 5, 680000.00, 'Active'),
(6, 'Club', 6, 450000.00, 'Active'),
(7, 'Club', 8, 780000.00, 'Active'),
(8, 'Club', 10, 520000.00, 'Active'),
(9, 'Club', 14, 890000.00, 'Active'),
(10, 'Club', 16, 1100000.00, 'Active'),
(11, 'Club', 18, 670000.00, 'Active'),
(12, 'Club', 22, 920000.00, 'Active'),
(13, 'Club', 23, 1350000.00, 'Active'),
(14, 'Division', 1, 2500000.00, 'Active'),
(15, 'Division', 2, 3200000.00, 'Active'),
(16, 'Division', 4, 1800000.00, 'Active'),
(17, 'Division', 5, 1500000.00, 'Active'),
(18, 'Division', 9, 900000.00, 'Active'),
(19, 'Zone', 1, 5000000.00, 'Active'),
(20, 'Zone', 2, 3500000.00, 'Active');

-- Ledger Entries
INSERT INTO LedgerEntry (ledger_id, amount, type, status, date, description) VALUES
(1, 50000.00, 'Income', 'Approved', '2026-08-01', 'Membership fees'),
(1, 25000.00, 'Expense', 'Approved', '2026-08-05', 'Event supplies'),
(2, 75000.00, 'Income', 'Approved', '2026-08-02', 'Donation received'),
(14, 100000.00, 'Income', 'Approved', '2026-08-10', 'Government grant'),
(15, 150000.00, 'Income', 'Approved', '2026-08-12', 'Zonal allocation');

-- Club Health Scores
INSERT INTO ClubHealthScore (club_id, event_score, attendance_score, financial_score, total_score, status, calculated_at) VALUES
(1, 85.50, 90.00, 88.00, 87.83, 'Green', '2026-08-01'),
(2, 92.00, 88.50, 95.00, 91.83, 'Green', '2026-08-01'),
(3, 45.00, 50.00, 40.00, 45.00, 'Red', '2026-08-01'),
(4, 78.00, 82.00, 80.00, 80.00, 'Green', '2026-08-01'),
(5, 88.00, 85.00, 90.00, 87.67, 'Green', '2026-08-01'),
(6, 65.00, 70.00, 68.00, 67.67, 'Yellow', '2026-08-01'),
(8, 90.00, 92.00, 88.00, 90.00, 'Green', '2026-08-01'),
(10, 75.00, 78.00, 72.00, 75.00, 'Green', '2026-08-01'),
(11, 35.00, 40.00, 30.00, 35.00, 'Red', '2026-08-01'),
(12, 55.00, 60.00, 58.00, 57.67, 'Yellow', '2026-08-01'),
(14, 82.00, 85.00, 80.00, 82.33, 'Green', '2026-08-01'),
(15, 60.00, 65.00, 62.00, 62.33, 'Yellow', '2026-08-01'),
(16, 95.00, 93.00, 96.00, 94.67, 'Green', '2026-08-01'),
(18, 87.00, 89.00, 85.00, 87.00, 'Green', '2026-08-01'),
(20, 30.00, 35.00, 25.00, 30.00, 'Red', '2026-08-01'),
(22, 91.00, 90.00, 92.00, 91.00, 'Green', '2026-08-01'),
(23, 93.00, 94.00, 95.00, 94.00, 'Green', '2026-08-01'),
(24, 68.00, 72.00, 70.00, 70.00, 'Yellow', '2026-08-01'),
(26, 42.00, 48.00, 45.00, 45.00, 'Red', '2026-08-01'),
(27, 80.00, 82.00, 78.00, 80.00, 'Green', '2026-08-01'),
(28, 58.00, 62.00, 60.00, 60.00, 'Yellow', '2026-08-01'),
(29, 38.00, 42.00, 35.00, 38.33, 'Red', '2026-08-01'),
(30, 86.00, 88.00, 84.00, 86.00, 'Green', '2026-08-01');

-- Volunteer History
INSERT INTO VolunteerHistory (member_id, date, hours, status, description) VALUES
(13, '2026-01-15', 8, 'Verified', 'Beach cleanup'),
(13, '2026-02-20', 6, 'Verified', 'Tree planting'),
(14, '2026-03-10', 10, 'Verified', 'Blood donation camp'),
(15, '2026-04-05', 5, 'Verified', 'Community kitchen'),
(16, '2026-05-12', 7, 'Verified', 'School renovation'),
(17, '2026-06-18', 9, 'Verified', 'Elder care visit'),
(18, '2026-07-22', 4, 'Verified', 'Library setup'),
(19, '2026-08-01', 6, 'Verified', 'Health camp'),
(20, '2026-01-20', 8, 'Verified', 'Road cleaning'),
(21, '2026-02-15', 5, 'Verified', 'Park maintenance'),
(22, '2026-03-25', 7, 'Verified', 'Youth workshop'),
(23, '2026-04-30', 10, 'Verified', 'Flood relief'),
(24, '2026-05-15', 6, 'Verified', 'Teaching program'),
(13, '2026-06-01', 5, 'Pending', 'Charity run'),
(14, '2026-07-10', 8, 'Pending', 'Food distribution');

-- Add more volunteer hours
INSERT INTO VolunteerHistory (member_id, date, hours, status, description)
SELECT 
    (seq % 16) + 9,
    DATE_ADD('2026-01-01', INTERVAL (seq % 240) DAY),
    (seq % 8) + 2,
    IF(seq % 5 = 0, 'Pending', 'Verified'),
    CONCAT('Activity ', seq)
FROM (
    SELECT @row := @row + 1 as seq
    FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t1,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t2,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t3,
         (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t4,
         (SELECT @row := 0) t5
) numbers
WHERE seq <= 500;

-- Red Flags
INSERT INTO RedFlag (entry_id, audit_id, reason, status, flagged_at) VALUES
(3, NULL, 'Unusual expense amount flagged by system', 'Unresolved', '2026-08-20 10:00:00'),
(NULL, 3, 'Audit overdue by 30 days', 'Unresolved', '2026-08-15 14:00:00'),
(4, NULL, 'Missing receipt documentation', 'Unresolved', '2026-08-18 09:30:00'),
(NULL, 5, 'Financial records incomplete', 'Unresolved', '2026-08-22 11:00:00'),
(2, NULL, 'Duplicate payment detected', 'Unresolved', '2026-08-25 16:00:00'),
(NULL, 8, 'Asset register mismatch', 'Unresolved', '2026-08-26 08:00:00'),
(5, NULL, 'Unauthorized transfer flagged', 'Resolved', '2026-08-10 10:00:00'),
(NULL, 10, 'Late submission of documents', 'Unresolved', '2026-08-27 14:00:00');

-- Audits
INSERT INTO Audit (club_id, financial_year, audit_status, sign_off_date, locked, due_date) VALUES
(1, 2026, 'Completed', '2026-07-15', TRUE, '2026-07-31'),
(2, 2026, 'Completed', '2026-07-20', TRUE, '2026-07-31'),
(3, 2026, 'Overdue', NULL, FALSE, '2026-07-31'),
(4, 2026, 'Completed', '2026-07-25', TRUE, '2026-07-31'),
(5, 2026, 'Completed', '2026-07-28', TRUE, '2026-07-31'),
(6, 2026, 'InProgress', NULL, FALSE, '2026-08-31'),
(8, 2026, 'Completed', '2026-08-01', TRUE, '2026-08-15'),
(10, 2026, 'Overdue', NULL, FALSE, '2026-08-15'),
(11, 2026, 'Overdue', NULL, FALSE, '2026-08-01'),
(14, 2026, 'Completed', '2026-08-05', TRUE, '2026-08-15'),
(15, 2026, 'InProgress', NULL, FALSE, '2026-08-31'),
(20, 2026, 'Overdue', NULL, FALSE, '2026-07-31'),
(22, 2026, 'Completed', '2026-08-10', TRUE, '2026-08-15'),
(26, 2026, 'InProgress', NULL, FALSE, '2026-08-31'),
(29, 2026, 'Overdue', NULL, FALSE, '2026-08-01');

-- Reports
INSERT INTO Report (type, scope_level, scope_id, generated_by, generated_at, status, period) VALUES
('Monthly', 'Division', 1, 4, '2026-08-01 10:00:00', 'Submitted', '2026-08'),
('Monthly', 'Division', 2, 3, '2026-08-02 14:00:00', 'Submitted', '2026-08'),
('Monthly', 'Division', 3, NULL, NULL, 'Missing', '2026-08'),
('Monthly', 'Division', 4, 5, '2026-08-03 09:00:00', 'Submitted', '2026-08'),
('Monthly', 'Division', 5, NULL, NULL, 'Missing', '2026-08'),
('Monthly', 'Division', 6, NULL, NULL, 'Missing', '2026-08'),
('Monthly', 'Division', 9, 6, '2026-08-05 11:00:00', 'Submitted', '2026-08'),
('Quarterly', 'Zone', 1, 1, '2026-08-10 10:00:00', 'Submitted', 'Q2-2026'),
('Quarterly', 'Zone', 2, 1, '2026-08-12 14:00:00', 'Submitted', 'Q2-2026'),
('Quarterly', 'Zone', 3, 1, '2026-08-15 09:00:00', 'Submitted', 'Q2-2026');

-- Events
INSERT INTO Event (event_id, title, description, event_type, max_attendance, start_datetime, end_datetime, location, organizer_club_id, organizer_division_id, target_scope, status, created_by, created_at) VALUES 
(1, 'Gampaha Youth Leadership Workshop 2026', 'Annual leadership development workshop', 'Workshop', 120, '2026-09-15 09:00:00', '2026-09-15 16:00:00', 'Gampaha Town Hall', 1, NULL, 'AllInScope', 'PendingApproval', 9, '2026-08-20 10:00:00'),
(2, 'Gampaha Youth Sports Championship', 'Division-wide sports meet', 'Sports', 500, '2026-09-22 08:30:00', '2026-09-23 18:00:00', 'Gampaha District Stadium', NULL, 2, 'AllInScope', 'PendingApproval', 7, '2026-08-21 14:00:00'),
(3, 'Community Green Cleanup', 'Environmental cleanup', 'Community Service', 60, '2026-09-28 07:30:00', '2026-09-28 12:00:00', 'Gampaha Central Park', 1, NULL, 'AllInScope', 'PendingApproval', 9, '2026-08-22 09:00:00'),
(4, 'National Youth Day Celebration', 'Annual national event', 'Cultural', 1000, '2026-10-01 08:00:00', '2026-10-01 20:00:00', 'Colombo Independence Square', NULL, NULL, 'AllInScope', 'PendingApproval', 1, '2026-08-25 10:00:00');

-- Audit Log (Recent Activity)
INSERT INTO AuditLog (actor_user_id, action_type, target_entity, target_id, `timestamp`, details) VALUES
(6, 'APPROVE', 'AssetTransfer', 1, DATE_SUB(NOW(), INTERVAL 12 MINUTE), 'Zonal Coordinator (Eastern) approved 3 asset transfer requests'),
(1, 'FLAG', 'Audit', 3, DATE_SUB(NOW(), INTERVAL 1 HOUR), 'Club 'Kalmunai Youth Forward' flagged for overdue audit'),
(8, 'SUBMIT', 'Report', 7, DATE_SUB(NOW(), INTERVAL 3 HOUR), 'Divisional Treasurer (Batticaloa) submitted Q2 fund ledger'),
(2, 'SUBMIT', 'ClubApplication', 7, DATE_SUB(NOW(), INTERVAL 1 DAY), 'New club registration submitted: 'Trinco Coastal Youth''),
(3, 'APPROVE', 'Event', 2, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Divisional Coordinator (Gampaha) approved youth sports championship'),
(5, 'CREATE', 'Event', 4, DATE_SUB(NOW(), INTERVAL 3 DAY), 'National Youth Day event created by Administrator'),
(4, 'UPDATE', 'Club', 2, DATE_SUB(NOW(), INTERVAL 4 DAY), 'Colombo Central Youth updated member count to 78'),
(1, 'ASSIGN', 'User', 25, DATE_SUB(NOW(), INTERVAL 5 DAY), 'New member assigned to Gampaha East Youth cycling club');

-- ============================================
-- VERIFICATION QUERIES (run these to check data)
-- ============================================
-- SELECT * FROM Zone;
-- SELECT * FROM Division;
-- SELECT * FROM Club;
-- SELECT * FROM User WHERE role = 'ClubMember';
-- SELECT * FROM ClubApplication WHERE status = 'Pending';
-- SELECT * FROM Ledger;
-- SELECT * FROM VolunteerHistory WHERE status = 'Verified';
-- SELECT * FROM Audit WHERE audit_status = 'Overdue';
-- SELECT * FROM RedFlag WHERE status = 'Unresolved';
-- SELECT * FROM Report WHERE status = 'Missing';
-- SELECT * FROM AuditLog ORDER BY timestamp DESC;
