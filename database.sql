CREATE DATABASE IF NOT EXISTS bloodbank;

USE bloodbank;

CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(50) NOT NULL
);

INSERT INTO
    admin (id, username, password)
VALUES (1, 'admin', 'admin123')
ON DUPLICATE KEY UPDATE
    username = username;

CREATE TABLE IF NOT EXISTS broadcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    target_name VARCHAR(100),
    sent_to_count INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'Sent',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS donors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    status ENUM(
        'Pending',
        'Approved',
        'Rejected',
        'Donated'
    ) DEFAULT 'Pending',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);