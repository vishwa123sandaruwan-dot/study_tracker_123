CREATE DATABASE IF NOT EXISTS study_tracker;
USE study_tracker;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    exam_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) DEFAULT '#6366f1',
    icon VARCHAR(50) DEFAULT 'book'
);

-- Study Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT,
    duration_minutes INT NOT NULL,
    study_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT,
    title VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Pre-populate subjects for Sri Lankan O/L
INSERT INTO subjects (name, color, icon) VALUES 
('Mathematics', '#ef4444', 'pi'),
('Science', '#3b82f6', 'flask'),
('Sinhala / Tamil', '#f59e0b', 'languages'),
('English', '#8b5cf6', 'alphabet'),
('History', '#a855f7', 'history'),
('Religion', '#10b981', 'om'),
('Information Technology', '#06b6d4', 'cpu'),
('Business Studies', '#f97316', 'briefcase'),
('Health & P.E.', '#fb7185', 'heart');

-- Initial demo user (assuming the student has 8 months left from current time)
-- 8 months from April 2026 is roughly December 2026/January 2027
INSERT INTO users (name, exam_date) VALUES ('O/L Student', DATE_ADD(CURRENT_DATE, INTERVAL 8 MONTH));

-- Timetable table
CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    subject_id INT,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);
