DROP USER 'root'@'localhost';
CREATE USER 'root'@'localhost' IDENTIFIED BY 'Earl1er2day';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
CREATE DATABASE gs;
DROP USER IF EXISTS 'gs'@'localhost';
CREATE USER 'gs_user'@'localhost' IDENTIFIED BY 'gs_passwd';
ALTER USER 'gs_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'gs_passwd'; # may not work on the pi
GRANT ALL PRIVILEGES ON gs.* TO 'gs_user'@'localhost';
FLUSH PRIVILEGES;
