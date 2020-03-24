DROP USER 'root'@'localhost';
CREATE USER 'root'@'localhost' IDENTIFIED BY 'Earl1er2day';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
