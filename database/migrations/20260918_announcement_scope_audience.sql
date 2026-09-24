ALTER TABLE Announcement
    ADD COLUMN organizer_club_id INT NULL AFTER level,
    ADD COLUMN organizer_zonal_id INT NULL AFTER organizer_division_id,
    ADD COLUMN deleted_at DATETIME NULL AFTER content_edited_at;

ALTER TABLE Announcement
    ADD CONSTRAINT fk_announcement_club
        FOREIGN KEY (organizer_club_id)
        REFERENCES Club(club_id),
    ADD CONSTRAINT fk_announcement_zonal
        FOREIGN KEY (organizer_zonal_id)
        REFERENCES Zone(zonal_id);

CREATE INDEX idx_announcement_club
    ON Announcement(organizer_club_id);

CREATE INDEX idx_announcement_zonal
    ON Announcement(organizer_zonal_id);

CREATE INDEX idx_announcement_level_status
    ON Announcement(level, status);


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
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;