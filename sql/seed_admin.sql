-- Default admin user.
-- Username: admin
-- Password: admin123
-- The hash below is bcrypt for "admin123" — change the password after first login.
USE student;

INSERT INTO users (username, password)
VALUES ('admin', '$2y$12$gCn0KueVUOZ9btflCCHxCe2qWU9gVKQ.dBY51Ddf7txSPOQa5Sq0i')
ON DUPLICATE KEY UPDATE username = username;
