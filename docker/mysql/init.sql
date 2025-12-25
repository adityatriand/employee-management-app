CREATE DATABASE IF NOT EXISTS employee_management;
CREATE USER IF NOT EXISTS 'laravel_user'@'localhost' IDENTIFIED BY 'laravel_password';
CREATE USER IF NOT EXISTS 'laravel_user'@'%' IDENTIFIED BY 'laravel_password';
GRANT ALL PRIVILEGES ON employee_management.* TO 'laravel_user'@'localhost';
GRANT ALL PRIVILEGES ON employee_management.* TO 'laravel_user'@'%';
FLUSH PRIVILEGES;

