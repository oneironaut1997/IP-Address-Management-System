-- =============================================================================
-- MySQL Initialization Script for IP Database
-- =============================================================================
-- This script runs when the ip-db container starts for the first time.
-- It creates the application user and grants necessary permissions.
-- =============================================================================

-- Create application user with specific permissions
CREATE USER IF NOT EXISTS 'ip_user'@'%' IDENTIFIED BY 'ip_pass';

-- Grant all privileges on ip_db to ip_user
GRANT ALL PRIVILEGES ON ip_db.* TO 'ip_user'@'%';

-- Apply changes
FLUSH PRIVILEGES;
