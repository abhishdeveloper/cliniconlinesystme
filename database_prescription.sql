
-- Ayurvedic Prescription System Database

-- Medicines Table (Managed by Admin)
-- Stores prefilled medicines like 'Ashwagandha Churna', 'Triphala'
CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) DEFAULT 'Powder', -- e.g., Powder, Tablet, Syrup, Oil
    default_dosage VARCHAR(255) DEFAULT NULL, -- e.g., "1 tsp twice daily"
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Prescriptions Table
-- Linked to Appointment. Stores medicines as a JSON blob for flexibility.
-- This avoids complex many-to-many relationships for simple requirements.
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    diagnosis TEXT NOT NULL,
    medicines_json JSON NOT NULL, -- Format: [{"name": "X", "dosage": "Y", "duration": "Z"}]
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed Initial Ayurvedic Medicines
INSERT INTO medicines (name, type, default_dosage, description) VALUES
('Ashwagandha Churna', 'Powder', '1 tsp twice daily with warm milk', 'Relieves stress and improves vitality'),
('Triphala Churna', 'Powder', '1 tsp at bedtime with warm water', 'Digestive health and detox'),
('Brahmi Vati', 'Tablet', '1 tablet twice daily', 'Memory and cognitive function'),
('Chyawanprash', 'Paste', '1 tsp daily in morning', 'Immunity booster'),
('Mahanarayan Taila', 'Oil', 'Apply externally on joints', 'Joint pain relief');
