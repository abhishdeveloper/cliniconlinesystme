-- Advanced Clinic System Database (Ayurveda Enhanced)

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL, -- For SMS notifications
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'admin', 'doctor', 'patient') DEFAULT 'patient',
    prakruti VARCHAR(100) DEFAULT NULL, -- Ayurvedic Constitution (e.g., Vata-Pitta)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Doctor Availability (Weekly Schedule Templates)
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

-- Specific Appointment Slots
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
    meeting_link VARCHAR(255) DEFAULT NULL, -- For Jitsi Video Call

    -- Payment Fields
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT NULL, -- razorpay, cash, other
    transaction_id VARCHAR(255) DEFAULT NULL,
    amount DECIMAL(10,2) DEFAULT 0.00,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (slot_id) REFERENCES appointment_slots(id) ON DELETE SET NULL
);

-- Ayurvedic Medicines Table
CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) DEFAULT 'Powder', -- e.g., Powder, Tablet, Syrup, Oil
    default_dosage VARCHAR(255) DEFAULT NULL, -- e.g., "1 tsp twice daily"
    stock_quantity INT DEFAULT 0, -- Inventory Level
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Prescriptions Table (Ayurveda Enhanced)
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    diagnosis TEXT NOT NULL,

    -- Ayurvedic Diagnosis Fields
    vikruti VARCHAR(255) DEFAULT NULL, -- Current Imbalance (e.g., High Pitta)
    pulse VARCHAR(255) DEFAULT NULL, -- Nadi Pariksha
    tongue VARCHAR(255) DEFAULT NULL, -- Jihva Pariksha
    skin VARCHAR(255) DEFAULT NULL, -- Sparsha Pariksha

    medicines_json JSON NOT NULL, -- Format: [{"name": "X", "dosage": "Y", "duration": "Z"}]
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Patient Documents/Reports Table
CREATE TABLE IF NOT EXISTS patient_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_id INT DEFAULT NULL, -- Link to specific appointment/call
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL, -- Relative path (e.g., uploads/reports/file.pdf)
    description TEXT,
    uploaded_by INT NOT NULL, -- User ID of uploader (can be patient or doctor)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Video Call Captions Table (For real-time transcription sharing)
CREATE TABLE IF NOT EXISTS call_captions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    user_id INT NOT NULL,
    caption_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed Initial Super Admin User (Password: admin123)
INSERT INTO users (name, email, phone, password, role) VALUES
('Super Admin', 'superadmin@clinic.com', '+15550000000', '$2y$10$Odv5dPTeqhFv9ZD2O.NH8.JLvJqwDOTzAmN.PLN3cM22N8IDeK1B2', 'superadmin');

-- Seed a Regular Admin (Password: admin123)
INSERT INTO users (name, email, phone, password, role) VALUES
('Clinic Admin', 'admin@clinic.com', '+15550000001', '$2y$10$Odv5dPTeqhFv9ZD2O.NH8.JLvJqwDOTzAmN.PLN3cM22N8IDeK1B2', 'admin');

-- Seed a Doctor (Password: doctor123)
INSERT INTO users (name, email, phone, password, role) VALUES
('Dr. Ayurvedic', 'doctor@clinic.com', '+15551234567', '$2y$10$EGbj.bj1g8rm44fX75V37uDz1kjXTTXZrQuzTec/hAResWwy8HWsm', 'doctor');

-- Seed a Patient (Password: patient123)
INSERT INTO users (name, email, phone, password, role, prakruti) VALUES
('John Doe', 'patient@clinic.com', '+15559876543', '$2y$10$KOno9WKy/.t/Kn4kV6Tg.uDE.QxQg.rJ1Qkmhy8hmmaEnjTQiwLeS', 'patient', 'Vata-Pitta');

-- Seed Initial Ayurvedic Medicines
INSERT INTO medicines (name, type, default_dosage, stock_quantity, description) VALUES
('Ashwagandha Churna', 'Powder', '1 tsp twice daily with warm milk', 100, 'Relieves stress and improves vitality'),
('Triphala Churna', 'Powder', '1 tsp at bedtime with warm water', 50, 'Digestive health and detox'),
('Brahmi Vati', 'Tablet', '1 tablet twice daily', 200, 'Memory and cognitive function'),
('Chyawanprash', 'Paste', '1 tsp daily in morning', 30, 'Immunity booster'),
('Mahanarayan Taila', 'Oil', 'Apply externally on joints', 20, 'Joint pain relief');
