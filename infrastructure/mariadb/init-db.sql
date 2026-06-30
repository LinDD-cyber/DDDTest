CREATE DATABASE IF NOT EXISTS user_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS order_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create the user if it does not exist before granting permissions
CREATE USER IF NOT EXISTS 'ai4dt'@'%' IDENTIFIED BY 'ai4dt@example';

GRANT ALL PRIVILEGES ON user_db.* TO 'ai4dt'@'%';
GRANT ALL PRIVILEGES ON order_db.* TO 'ai4dt'@'%';

FLUSH PRIVILEGES;

