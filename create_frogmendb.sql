-- Script to create frogmendb database
-- Run this in MySQL Workbench or phpMyAdmin

-- Drop database if it exists (optional - be careful!)
-- DROP DATABASE IF EXISTS frogmendb;

-- Create the database
CREATE DATABASE frogmendb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE frogmendb;

-- Show confirmation
SELECT 'Database frogmendb created successfully!' AS Status;