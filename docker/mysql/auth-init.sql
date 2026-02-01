-- =============================================================================
-- MySQL Initialization Script for Auth Database
-- =============================================================================
-- This script runs when the auth-db container starts for the first time.
-- It creates the application user and grants necessary permissions.
-- =============================================================================

-- Create application user with specific permissions
CREATE USER IF NOT EXISTS 'auth_user'@'%' IDENTIFIED BY 'auth_pass';

-- Grant all privileges on auth_db to auth_user
GRANT ALL PRIVILEGES ON auth_db.* TO 'auth_user'@'%';

-- Also grant permissions for gateway_db (Gateway service uses auth-db)
CREATE USER IF NOT EXISTS 'gateway_user'@'%' IDENTIFIED BY 'gateway_pass';
GRANT ALL PRIVILEGES ON gateway_db.* TO 'gateway_user'@'%';

-- Apply changes
FLUSH PRIVILEGES;

-- Create gateway database (Auth DB will be created by environment variable)
CREATE DATABASE IF NOT EXISTS gateway_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON gateway_db.* TO 'gateway_user'@'%';
FLUSH PRIVILEGES;
