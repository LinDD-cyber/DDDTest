CREATE DATABASE IF NOT EXISTS user_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS order_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS example_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create the user if it does not exist before granting permissions
CREATE USER IF NOT EXISTS 'ai4dt123'@'%' IDENTIFIED BY 'Aa0000';

GRANT ALL PRIVILEGES ON user_db.* TO 'ai4dt123'@'%';
GRANT ALL PRIVILEGES ON order_db.* TO 'ai4dt123'@'%';
GRANT ALL PRIVILEGES ON example_db.* TO 'ai4dt123'@'%';

FLUSH PRIVILEGES;

