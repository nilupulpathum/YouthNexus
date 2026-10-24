-- Migration: Add missing columns to Attendance table
-- Run this in phpMyAdmin or via MySQL CLI if columns don't exist

ALTER TABLE `Attendance`
    ADD COLUMN IF NOT EXISTS `location`  VARCHAR(255) NULL AFTER `remark`,
    ADD COLUMN IF NOT EXISTS `photo_url` VARCHAR(255) NULL AFTER `location`;
