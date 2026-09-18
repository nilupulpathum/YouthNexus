-- ============================================================================
-- YouthNexus schema — clean-install safe (B1 fix, 2026-09-08)
--
-- Install:  mysql -u root < database/youthnexus.sql
--           mysql -u root youthnexus < database/seed_club_application.sql
-- Verify:   mysql -u root youthnexus < database/verify.sql
--
-- Import-order rules (do NOT reorder without re-testing a fresh import):
--  1. Tables are created before any table that references them, EXCEPT the
--     two circular references below, which are added as trailing ALTERs.
--  2. Circular refs deferred to Section 4: User.club_id -> Club,
--     Club.source_application_id -> ClubApplication.
--  3. All seeds live in Section 5, after every CREATE/ALTER.
--  4. No SELECTs in this file (see verify.sql).
-- ============================================================================

CREATE DATABASE IF NOT EXISTS youthnexus;
USE youthnexus;

-- ----------------------------------------------------------------------------
-- Section 1: geography (no dependencies)
-- ----------------------------------------------------------------------------

CREATE TABLE Zone (
    zonal_id    INT AUTO_INCREMENT PRIMARY KEY,
    zonal_name  VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Division (
    division_id    INT AUTO_INCREMENT PRIMARY KEY,
    division_name  VARCHAR(100) NOT NULL,
    zonal_id       INT NOT NULL,

    FOREIGN KEY (zonal_id) REFERENCES Zone(zonal_id),
    UNIQUE (division_name, zonal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Section 2: people and applications
-- NOTE: User.club_id FK is deferred to Section 4 (Club is created later).
-- ----------------------------------------------------------------------------

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
    FOREIGN KEY (division_id) REFERENCES Division(division_id),
    FOREIGN KEY (zonal_id) REFERENCES Zone(zonal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ClubApplication (
    application_id        INT AUTO_INCREMENT PRIMARY KEY,

    -- Proposer (the UnassignedUser who submits)
    proposer_user_id      INT NOT NULL,

    -- Step 1: Basic Information
    club_name             VARCHAR(150) NOT NULL,
    description           VARCHAR(500) DEFAULT NULL,
    club_logo_path        VARCHAR(500) DEFAULT NULL,
    category              VARCHAR(50) DEFAULT NULL,
    date_establishment    DATE DEFAULT NULL,
    no_of_members         INT DEFAULT 0,

    -- Step 2: Location Details
    proposed_division_id  INT DEFAULT NULL,
    location_type         VARCHAR(50) DEFAULT NULL,
    street_address        VARCHAR(500) DEFAULT NULL,
    city                  VARCHAR(100) DEFAULT NULL,
    state_province        VARCHAR(100) DEFAULT NULL,
    postal_code           VARCHAR(20) DEFAULT NULL,
    country               VARCHAR(80) DEFAULT 'Sri Lanka',

    -- Step 5: Bank / Disbursement Details
    bank_name             VARCHAR(100) DEFAULT NULL,
    bank_branch           VARCHAR(100) DEFAULT NULL,
    account_holder        VARCHAR(200) DEFAULT NULL,
    account_number        VARCHAR(50) DEFAULT NULL,
    bank_confirmed        BOOLEAN DEFAULT FALSE,

    -- Step 6: Document Paths
    constitution_path     VARCHAR(500) DEFAULT NULL,
    venue_proof_path      VARCHAR(500) DEFAULT NULL,
    nic_president_path    VARCHAR(500) DEFAULT NULL,
    nic_secretary_path    VARCHAR(500) DEFAULT NULL,
    nic_treasurer_path    VARCHAR(500) DEFAULT NULL,

    -- Step 7: Declaration
    info_accuracy         BOOLEAN DEFAULT FALSE,
    terms_accepted        BOOLEAN DEFAULT FALSE,
    digital_signature     VARCHAR(200) DEFAULT NULL,

    -- Application Status
    status                ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    submitted_at          TIMESTAMP NULL DEFAULT NULL,
    reviewed_by           INT DEFAULT NULL,
    reviewed_at           TIMESTAMP NULL DEFAULT NULL,
    rejection_remarks     VARCHAR(500) DEFAULT NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (proposer_user_id) REFERENCES User(user_id),
    FOREIGN KEY (proposed_division_id) REFERENCES Division(division_id),
    FOREIGN KEY (reviewed_by) REFERENCES User(user_id),

    INDEX idx_app_status (status),
    INDEX idx_proposer (proposer_user_id),
    INDEX idx_division (proposed_division_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Section 3: clubs, credentials, evidence, events
-- NOTE: Club.source_application_id FK is deferred to Section 4.
-- ----------------------------------------------------------------------------

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
    index_number    VARCHAR(30) DEFAULT NULL,  -- system-generated after approval

    FOREIGN KEY (application_id) REFERENCES ClubApplication(application_id) ON DELETE CASCADE,

    INDEX idx_app_nominee (application_id),
    INDEX idx_role_type (role_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ClubAsset (
    asset_id        INT AUTO_INCREMENT PRIMARY KEY,
    application_id  INT NOT NULL,
    asset_name      VARCHAR(200) NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    `condition`       ENUM('Excellent', 'Good', 'Fair', 'Poor') DEFAULT 'Good',
    photo_path      VARCHAR(500) DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (application_id) REFERENCES ClubApplication(application_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ClubApplicationPhoto (
    photo_id        INT AUTO_INCREMENT PRIMARY KEY,
    application_id  INT NOT NULL,
    photo_path      VARCHAR(500) NOT NULL,
    uploaded_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (application_id) REFERENCES ClubApplication(application_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE PasswordReset (
    reset_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    otp_code    VARCHAR(255) NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    is_used     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Certificate (
    certificate_id  INT AUTO_INCREMENT PRIMARY KEY,
    owner_type      ENUM('Club', 'Member') NOT NULL,
    owner_id        INT NOT NULL,
    certificate_type ENUM('ClubRegistration', 'Volunteer') NOT NULL DEFAULT 'ClubRegistration',
    qr_code         VARCHAR(500) DEFAULT NULL,
    issued_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pdf_url         VARCHAR(500) DEFAULT NULL,

    INDEX idx_owner (owner_type, owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE Event (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description VARCHAR(1000),
    event_type VARCHAR(50),
    max_attendance INT,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    location VARCHAR(255),
    organizer_club_id INT NULL,
    organizer_division_id INT NULL,
    organizer_zonal_id INT NULL,
    target_scope ENUM('AllInScope','SelectedClubs') NOT NULL,
    status ENUM('Draft','PendingApproval','Approved','Rejected','Completed') NOT NULL DEFAULT 'PendingApproval',
    created_by INT NOT NULL,
    approved_by INT NULL,
    rejection_remarks VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_club_id) REFERENCES Club(club_id),
    FOREIGN KEY (organizer_division_id) REFERENCES Division(division_id),
    FOREIGN KEY (organizer_zonal_id) REFERENCES Zone(zonal_id),
    FOREIGN KEY (created_by) REFERENCES User(user_id),
    FOREIGN KEY (approved_by) REFERENCES User(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE EventTarget (
    target_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    target_club_id INT NULL,
    max_attendance INT NULL,
    target_division_id INT NULL,
    target_zonal_id INT NULL,
    FOREIGN KEY (event_id) REFERENCES Event(event_id),
    FOREIGN KEY (target_club_id) REFERENCES Club(club_id),
    FOREIGN KEY (target_division_id) REFERENCES Division(division_id),
    FOREIGN KEY (target_zonal_id) REFERENCES Zone(zonal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance (was missing entirely — required by AttendanceModel).
-- The UNIQUE(event_id, user_id) key powers the INSERT ... ON DUPLICATE KEY
-- UPDATE upsert in AttendanceModel::saveAttendance().
CREATE TABLE Attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('Present', 'Absent') NOT NULL,
    check_in_time DATETIME NULL,
    check_out_time DATETIME NULL,
    remark VARCHAR(500) NULL,
    recorded_by INT NOT NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES Event(event_id),
    FOREIGN KEY (user_id) REFERENCES User(user_id),
    FOREIGN KEY (recorded_by) REFERENCES User(user_id),
    UNIQUE KEY uq_attendance_event_user (event_id, user_id),
    INDEX idx_attendance_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Announcement / Broadcast Communication
-- ----------------------------------------------------------------------------

CREATE TABLE Announcement (
    announcement_id       INT AUTO_INCREMENT PRIMARY KEY,
    title                 VARCHAR(150) NOT NULL,
    body                  TEXT NOT NULL,

    level                 ENUM('Club','Divisional','Zonal','NYSC')
                          NOT NULL DEFAULT 'Divisional',

    organizer_club_id INT NULL,
    organizer_division_id INT NULL,
    organizer_zonal_id INT NULL,

    target_audience       ENUM(
                            'AllDivisionalClubs',
                            'ClubPresidentsSecretaries',
                            'AllMembers'
                          ) NULL,

    category              VARCHAR(100) NULL,
    priority              ENUM('Normal','Urgent')
                          NOT NULL DEFAULT 'Normal',

    status                ENUM('Draft','Published')
                          NOT NULL DEFAULT 'Draft',

    view_count            INT NOT NULL DEFAULT 0,

    created_by            INT NOT NULL,

    published_at          DATETIME NULL,
    content_edited_at     DATETIME NULL DEFAULT NULL,

    last_edited_at        TIMESTAMP
                          DEFAULT CURRENT_TIMESTAMP
                          ON UPDATE CURRENT_TIMESTAMP,

    created_at            TIMESTAMP
                          DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

FOREIGN KEY (organizer_club_id)
    REFERENCES Club(club_id),

FOREIGN KEY (organizer_division_id)
    REFERENCES Division(division_id),

FOREIGN KEY (organizer_zonal_id)
    REFERENCES Zone(zonal_id),


    FOREIGN KEY (created_by)
        REFERENCES User(user_id),

    INDEX idx_announcement_division (organizer_division_id),
    INDEX idx_announcement_status (status),
    INDEX idx_announcement_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE AnnouncementAudience (
    audience_id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    target_role VARCHAR(50) NOT NULL,

    UNIQUE KEY uq_announcement_target_role (
        announcement_id,
        target_role
    ),

    FOREIGN KEY (announcement_id)
        REFERENCES Announcement(announcement_id)
        ON DELETE CASCADE,

    INDEX idx_announcement_audience_role (target_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE AnnouncementAttachment (
    attachment_id   INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    file_name       VARCHAR(255) NOT NULL,
    file_path       VARCHAR(255) NOT NULL,
    file_size       INT NOT NULL,
    uploaded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (announcement_id)
        REFERENCES Announcement(announcement_id)
        ON DELETE CASCADE,

    INDEX idx_announcement_attachment (announcement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE AnnouncementRead (
    read_id         INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id         INT NOT NULL,
    read_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_announcement_read (
        announcement_id,
        user_id
    ),

    FOREIGN KEY (announcement_id)
        REFERENCES Announcement(announcement_id)
        ON DELETE CASCADE,

    FOREIGN KEY (user_id)
        REFERENCES User(user_id),

    INDEX idx_announcement_read_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;



-- ----------------------------------------------------------------------------
-- Section 4: deferred circular foreign keys
-- ----------------------------------------------------------------------------

ALTER TABLE User ADD CONSTRAINT fk_user_club
    FOREIGN KEY (club_id) REFERENCES Club(club_id);

ALTER TABLE Club ADD CONSTRAINT fk_club_source_application
    FOREIGN KEY (source_application_id) REFERENCES ClubApplication(application_id);

-- ----------------------------------------------------------------------------
-- Section 5: seed data (all DDL/ALTERs above must succeed first)
-- ----------------------------------------------------------------------------

INSERT INTO Zone (zonal_id, zonal_name) VALUES
(1, 'Western Zone'),
(2, 'Central Zone'),
(3, 'Southern Zone');

INSERT INTO Division (division_id, division_name, zonal_id) VALUES
(1, 'Colombo Division', 1),
(2, 'Gampaha Division', 1),
(3, 'Kalutara Division', 1),
(4, 'Kandy Division', 2),
(5, 'Galle Division', 3);

INSERT INTO User (username, email, password_hash, first_name, last_name, role, status)
VALUES (
  'testuser',
  'test@example.com',
  '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1n5S3J2h1xV8Vz.N9gXyM0Y6Kz2Kq6S',
  'Test',
  'User',
  'UnassignedUser',
  'Active'
);

INSERT INTO User (username, email, password_hash, first_name, last_name, phone_number, NIC, role, status, division_id)
VALUES (
  'damikrajithuru',
  'damikarajithuru@gmail.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O',
  'Damik',
  'Rajithuru',
  '0771234567',
  '200012345678',
  'DivisionalCoordinator',
  'Active',
  2
);

INSERT INTO User (username, email, password_hash, first_name, last_name, phone_number, role, status, division_id)
VALUES (
  'sec_gampaha',
  'damikarajithuru2@gmail.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O',
  'Damik',
  'Rajithuru',
  '0771122334',
  'DivisionalSecretary',
  'Active',
  2
);

INSERT INTO Club (club_id, club_name, description, division_id, registration_date, status, no_of_members, club_code)
VALUES (1, 'Gampaha Youth Development Club', 'Active youth empowerment and sports club in Gampaha.', 2, '2026-01-15', 'Active', 45, 'CLB-GAM-2026-001')
ON DUPLICATE KEY UPDATE division_id = 2, status = 'Active';

INSERT INTO User (username, email, password_hash, first_name, last_name, phone_number, role, status, club_id, division_id)
VALUES (
  'clubpresident_gampaha',
  'club.gampaha@youthnexus.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaRGRo6Jd9a4l9bU0Y3fF1R3K6O',
  'Nuwan',
  'Bandara',
  '0775566778',
  'ClubPresident',
  'Active',
  1,
  2
);

INSERT INTO Event (event_id, title, description, event_type, max_attendance, start_datetime, end_datetime, location, organizer_club_id, organizer_division_id, target_scope, status, created_by, created_at)
VALUES
(1, 'Gampaha Youth Leadership Workshop 2026', 'Annual leadership development and skills workshop for youth club members in Gampaha.', 'Workshop', 120, '2026-09-15 09:00:00', '2026-09-15 16:00:00', 'Gampaha Town Hall', 1, NULL, 'AllInScope', 'PendingApproval', 4, NOW()),
(2, 'Gampaha Youth Sports & Cultural Championship', 'Division-wide sports and cultural meet organized by Divisional Secretariat Gampaha.', 'Sports', 500, '2026-09-22 08:30:00', '2026-09-23 18:00:00', 'Gampaha District Stadium', NULL, 2, 'AllInScope', 'PendingApproval', 3, NOW()),
(3, 'Community Green Environment Cleanup', 'Voluntary environmental cleanup along the canal and central park in Gampaha.', 'Community Service', 60, '2026-09-28 07:30:00', '2026-09-28 12:00:00', 'Gampaha Central Park', 1, NULL, 'AllInScope', 'PendingApproval', 4, NOW());
