-- Run ONCE against your existing YouthNexus database before using the updated app.
-- New installations already include this column in database/youthnexus.sql.
-- Do not backfill from last_edited_at: that field also changed on page views.
-- Existing creation/publication dates and all announcement content are preserved.
ALTER TABLE Announcement
    ADD COLUMN content_edited_at DATETIME NULL DEFAULT NULL AFTER published_at;
