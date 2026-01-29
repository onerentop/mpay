-- MPay Database Initialization Script
-- This script sets the correct character set

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Grant additional privileges if needed
GRANT ALL PRIVILEGES ON mpay.* TO 'mpay'@'%';
FLUSH PRIVILEGES;
