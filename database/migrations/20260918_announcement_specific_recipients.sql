ALTER TABLE AnnouncementAudience
    ADD COLUMN selection_mode
        ENUM('All', 'Selected')
        NOT NULL
        DEFAULT 'All'
        AFTER target_role;


CREATE TABLE AnnouncementAudienceUser (
    audience_id INT NOT NULL,
    user_id INT NOT NULL,

    PRIMARY KEY (audience_id, user_id),

    FOREIGN KEY (audience_id)
        REFERENCES AnnouncementAudience(audience_id)
        ON DELETE CASCADE,

    FOREIGN KEY (user_id)
        REFERENCES User(user_id),

    INDEX idx_announcement_audience_user_user (user_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
