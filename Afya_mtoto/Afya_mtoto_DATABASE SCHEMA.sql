-- =============================================
-- AFYA MTOTO DATABASE SCHEMA
-- Designed for continuum of care + future DHIS2 & MSD integration
-- =============================================

CREATE DATABASE IF NOT EXISTS afya_mtoto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE afya_mtoto;

-- ---------------------------------------------
-- 1. ROLES
-- ---------------------------------------------
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,          -- admin, supervisor, facility_staff, chw
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 2. USERS
-- ---------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 3. FACILITIES (Organisation Units)
-- ---------------------------------------------
CREATE TABLE facilities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    facility_code VARCHAR(50) UNIQUE,          -- Important for DHIS2 & MSD
    name VARCHAR(150) NOT NULL,
    facility_type ENUM('Dispensary','Health Centre','Hospital','Clinic') DEFAULT 'Dispensary',
    region VARCHAR(100),
    district VARCHAR(100),
    ward VARCHAR(100),
    village VARCHAR(100),
    ownership ENUM('Public','Private','FBO') DEFAULT 'Public',
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 4. COMMUNITY HEALTH WORKERS (CHWs)
-- ---------------------------------------------
CREATE TABLE chws (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,                 -- Link to users table if they have login
    chw_code VARCHAR(50) UNIQUE,               -- Important for government systems
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    gender ENUM('Male','Female','Other'),
    facility_id INT UNSIGNED,                  -- Supervising facility
    village VARCHAR(100),
    ward VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 5. CLIENTS (Pregnant women + Children under 5)
-- ---------------------------------------------
CREATE TABLE clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_uid VARCHAR(50) UNIQUE,             -- System unique ID (for DHIS2 Tracker later)
    nida_number VARCHAR(30) NULL,              -- National ID
    full_name VARCHAR(150) NOT NULL,
    client_type ENUM('Pregnant Woman','Child Under 5') NOT NULL,
    date_of_birth DATE NULL,
    gender ENUM('Male','Female','Other') NULL,
    phone VARCHAR(20),
    village VARCHAR(100),
    ward VARCHAR(100),
    facility_id INT UNSIGNED,                  -- Preferred / catchment facility
    chw_id INT UNSIGNED,                       -- Assigned CHW
    mother_id INT UNSIGNED NULL,               -- If child, link to mother
    expected_delivery_date DATE NULL,          -- For pregnant women
    blood_group VARCHAR(10) NULL,
    is_high_risk TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE SET NULL,
    FOREIGN KEY (chw_id) REFERENCES chws(id) ON DELETE SET NULL,
    FOREIGN KEY (mother_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 6. HOME VISITS / SCREENINGS
-- ---------------------------------------------
CREATE TABLE visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_uid VARCHAR(50) UNIQUE,
    client_id INT UNSIGNED NOT NULL,
    chw_id INT UNSIGNED NOT NULL,
    visit_date DATE NOT NULL,
    visit_type VARCHAR(100),                   -- ANC, PNC, Growth Monitoring, etc.
    danger_signs TEXT,                         -- JSON or comma separated
    findings TEXT,
    actions_taken TEXT,
    is_referred TINYINT(1) DEFAULT 0,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (chw_id) REFERENCES chws(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 7. REFERRALS (Core Continuum Feature)
-- ---------------------------------------------
CREATE TABLE referrals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referral_code VARCHAR(50) UNIQUE NOT NULL, -- Unique code for tracking
    client_id INT UNSIGNED NOT NULL,
    chw_id INT UNSIGNED NOT NULL,
    from_facility_id INT UNSIGNED NULL,
    to_facility_id INT UNSIGNED NOT NULL,
    visit_id INT UNSIGNED NULL,
    referral_date DATETIME NOT NULL,
    reason TEXT NOT NULL,
    urgency ENUM('Normal','Urgent','Emergency') DEFAULT 'Normal',
    status ENUM('Pending','Arrived','Completed','Lost to Follow-up','Cancelled') DEFAULT 'Pending',
    arrival_date DATETIME NULL,
    completion_date DATETIME NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (chw_id) REFERENCES chws(id) ON DELETE CASCADE,
    FOREIGN KEY (from_facility_id) REFERENCES facilities(id) ON DELETE SET NULL,
    FOREIGN KEY (to_facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 8. APPOINTMENTS & REMINDERS
-- ---------------------------------------------
CREATE TABLE appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    facility_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_type VARCHAR(100),             -- ANC, Vaccination, PNC, etc.
    status ENUM('Scheduled','Completed','Missed','Cancelled') DEFAULT 'Scheduled',
    reminder_sent TINYINT(1) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 9. MEDICINES (Prepared for future MSD integration)
-- ---------------------------------------------
CREATE TABLE medicines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    msd_item_code VARCHAR(50) UNIQUE,          -- Critical for MSD integration
    name VARCHAR(150) NOT NULL,
    generic_name VARCHAR(150),
    unit VARCHAR(50),                          -- Tablet, Bottle, Vial, etc.
    category VARCHAR(100),
    is_essential TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 10. FACILITY STOCK / STOCK ALERTS (MSD Ready)
-- ---------------------------------------------
CREATE TABLE facility_stock (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    facility_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    quantity INT DEFAULT 0,
    status ENUM('Available','Low','Out of Stock') DEFAULT 'Available',
    last_updated DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    UNIQUE KEY unique_facility_medicine (facility_id, medicine_id)
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 11. ACTIVITY LOGS (Audit Trail)
-- ---------------------------------------------
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(80),
    record_id INT UNSIGNED,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------
-- 12. SYSTEM SETTINGS (for future DHIS2 & MSD credentials)
-- ---------------------------------------------
CREATE TABLE system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------
-- DEFAULT DATA
-- ---------------------------------------------
INSERT INTO roles (name, description) VALUES
('admin', 'System Administrator'),
('supervisor', 'District / Council Supervisor'),
('facility_staff', 'Nurse or Clinician at facility'),
('chw', 'Community Health Worker');

INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('dhis2_base_url', '', 'Future DHIS2 instance URL'),
('dhis2_username', '', 'Future DHIS2 username'),
('dhis2_password', '', 'Future DHIS2 password (store encrypted later)'),
('msd_api_url', '', 'Future MSD API URL'),
('system_name', 'Afya Mtoto', 'System display name'),
('default_language', 'sw', 'sw or en');