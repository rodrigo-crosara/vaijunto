-- VaiJunto Database Schema

CREATE DATABASE IF NOT EXISTS vaijunto_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vaijunto_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100),
    photo_url VARCHAR(255),
    bio TEXT,
    pix_key VARCHAR(255),
    pin_hash VARCHAR(255) NULL,
    is_driver TINYINT(1) DEFAULT 0,
    is_admin TINYINT(1) DEFAULT 0,
    reputation DECIMAL(3,2) DEFAULT 5.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone)
) ENGINE=InnoDB;

-- Cars Table
CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    model VARCHAR(50) NOT NULL,
    color VARCHAR(30),
    plate VARCHAR(10) NOT NULL,
    photo_url VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- Rides Table
CREATE TABLE IF NOT EXISTS rides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    origin_text VARCHAR(255) NOT NULL,
    destination_text VARCHAR(255) NOT NULL,
    waypoints JSON,
    departure_time DATETIME NOT NULL,
    seats_total INT NOT NULL,
    seats_available INT NOT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    tags JSON,
    status ENUM('scheduled', 'active', 'finished', 'canceled') DEFAULT 'scheduled',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_driver_id (driver_id),
    INDEX idx_departure_time (departure_time),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL,
    passenger_id INT NOT NULL,
    meeting_point VARCHAR(255) NULL COMMENT 'Ponto de encontro do passageiro',
    note VARCHAR(100) NULL COMMENT 'Ponto de encontro ou obs do passageiro',
    status ENUM('pending', 'confirmed', 'rejected', 'canceled', 'no_show', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ride_id (ride_id),
    INDEX idx_passenger_id (passenger_id)
) ENGINE=InnoDB;

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'ex: system, booking_request, confirmed, cancel, payment',
    message TEXT NOT NULL,
    link_url VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
