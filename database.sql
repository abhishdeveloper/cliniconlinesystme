-- Advanced Clinic System Database

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL, -- For SMS notifications
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'patient') DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Doctor Availability (Weekly Schedule Templates)
-- Stores recurring availability, e.g., "Monday 09:00 - 17:00"
CREATE TABLE IF NOT EXISTS doctor_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 30, -- Duration in minutes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Specific Appointment Slots (Generated from schedule or added manually)
-- This is what patients actually book.
CREATE TABLE IF NOT EXISTS appointment_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    slot_datetime DATETIME NOT NULL,
    is_booked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slot (doctor_id, slot_datetime)
);

-- Appointments Table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    slot_id INT DEFAULT NULL, -- Link to the specific slot
    appointment_date DATETIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (slot_id) REFERENCES appointment_slots(id) ON DELETE SET NULL
);

-- Seed Initial Admin User (Password: admin123)
INSERT INTO users (name, email, phone, password, role) VALUES
('Super Admin', 'admin@clinic.com', '+15550000000', '$2y$10$Odv5dPTeqhFv9ZD2O.NH8.JLvJqwDOTzAmN.PLN3cM22N8IDeK1B2', 'admin');

-- Seed a Doctor (Password: doctor123)
INSERT INTO users (name, email, phone, password, role) VALUES
('Dr. Smith', 'doctor@clinic.com', '+15551234567', '$2y$10$EGbj.bj1g8rm44fX75V37uDz1kjXTTXZrQuzTec/hAResWwy8HWsm', 'doctor');

-- Seed a Patient (Password: patient123)
INSERT INTO users (name, email, phone, password, role) VALUES
('John Doe', 'patient@clinic.com', '+15559876543', '$2y$10$KOno9WKy/.t/Kn4kV6Tg.uDE.QxQg.rJ1Qkmhy8hmmaEnjTQiwLeS', 'patient');
