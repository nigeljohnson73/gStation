CREATE DATABASE gs;
DROP USER IF EXISTS 'gs'@'localhost';
CREATE USER 'gs_user'@'localhost' IDENTIFIED BY 'gs_passwd';
GRANT ALL PRIVILEGES ON gs.* TO 'gs_user'@'localhost';
#ALTER USER 'gs_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'gs_passwd'; # Does not work on the pi - needed it for Mac development setup
FLUSH PRIVILEGES;
