-- Migration: Add club disbandment tracking columns to Club table
-- Required for Monitor Club Health feature (disbanding dormant or flagged clubs)

ALTER TABLE `Club`
    ADD COLUMN IF NOT EXISTS `disband_reason` TEXT NULL AFTER `flagged`,
    ADD COLUMN IF NOT EXISTS `disbanded_at` DATETIME NULL AFTER `disband_reason`,
    ADD COLUMN IF NOT EXISTS `disbanded_by` INT NULL AFTER `disbanded_at`;
