-- Create database
CREATE DATABASE IF NOT EXISTS mother_theresa_college DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;
USE mother_theresa_college;

-- Create students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    student_id VARCHAR(20) UNIQUE,
    mobile VARCHAR(15),
    course VARCHAR(100),
    department VARCHAR(100),
    year_semester VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    profile_pic VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified TINYINT DEFAULT 0,
    otp VARCHAR(6),
    otp_expires TIMESTAMP NULL
);

-- Create staff table
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    staff_id VARCHAR(20) UNIQUE,
    mobile VARCHAR(15),
    department VARCHAR(50),
    designation VARCHAR(50),
    qualification VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified TINYINT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'approved',
    is_admin TINYINT DEFAULT 0,
    otp VARCHAR(6),
    otp_expires TIMESTAMP NULL
);

-- Create documents table (status: pending until admin approves)
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
);

-- Create question_papers table (status: pending until admin approves)
CREATE TABLE IF NOT EXISTS question_papers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    course VARCHAR(50) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    year VARCHAR(10) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    file_path VARCHAR(500) NOT NULL,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES staff(id) ON DELETE SET NULL
);

-- Create contact_messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create results table (exam marks uploaded by admins, viewed by students)
CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    exam_type VARCHAR(50) NOT NULL DEFAULT 'Internal',
    marks_obtained DECIMAL(5,2) NOT NULL,
    max_marks DECIMAL(5,2) NOT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    course VARCHAR(50) DEFAULT NULL,
    remark VARCHAR(255) DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Default passwords: admin123 (admin), student123 (students)
-- Admin staff login: admin@mtc.edu / admin123 (is_admin = 1)
INSERT INTO staff (full_name, email, staff_id, department, designation, password, is_verified, status, is_admin)
VALUES ('Administrator', 'admin@mtc.edu', 'STF001', 'Administration', 'Admin', '$2y$10$YUOghq7V0J/pHiyqeK9UqeK1ZhduSKySfHgGhROmIpDXoNZZMtbLm', 1, 'approved', 1);

-- Insert sample students (password: student123)
INSERT INTO students (full_name, email, student_id, password)
VALUES ('John Doe', 'john@mtc.edu', 'STU001', '$2y$10$8zeF47CICodnwRqkwwopT.YUZGOvpXNPm6DMGmM2ooapKFyDl9dTq'),
       ('Jane Smith', 'jane@mtc.edu', 'STU002', '$2y$10$8zeF47CICodnwRqkwwopT.YUZGOvpXNPm6DMGmM2ooapKFyDl9dTq');