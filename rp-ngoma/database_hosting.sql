-- RP-Ngoma: HOSTING version. Create an empty database in cPanel, select it in phpMyAdmin, then Import this file.

DROP TABLE IF EXISTS sms_log, attendance, uploads, modules, users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  role ENUM('trainer','student','director') NOT NULL,
  phone VARCHAR(20) NULL,          -- trainers: international format, e.g. +250788000001
  reg_no VARCHAR(30) NULL UNIQUE   -- students only
) ENGINE=InnoDB;

-- Modules (courses) are managed by the Director and assigned to a trainer
CREATE TABLE modules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL UNIQUE,
  trainer_id INT NULL,
  FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE uploads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  trainer_id INT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  modules VARCHAR(255) NOT NULL,
  row_count INT NOT NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending','confirmed') NOT NULL DEFAULT 'pending',
  confirmed_by INT NULL,
  confirmed_at DATETIME NULL,
  FOREIGN KEY (trainer_id) REFERENCES users(id),
  FOREIGN KEY (confirmed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- The one table that stores the contents of every uploaded attendance list
CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  upload_id INT NOT NULL,
  reg_no VARCHAR(30) NOT NULL,
  student_name VARCHAR(120) NULL,
  module VARCHAR(100) NOT NULL,
  session_date DATE NULL,
  present TINYINT(1) NOT NULL,
  INDEX (reg_no, module),
  FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sms_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  phone VARCHAR(20) NOT NULL,
  message TEXT NOT NULL,
  status VARCHAR(30) NOT NULL,     -- sent | failed | simulated
  response TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Demo accounts, all with password 1234 (change after first login)
INSERT INTO users (username,password_hash,full_name,role,phone,reg_no) VALUES
('trainer1','$2y$12$thEPnq/mVIIaYVDUyH82/urgQ9wTgThks2fpb4XaM.ahs5yfXMvVW','Mr. Eric Mugisha','trainer','+250788000001',NULL),
('trainer2','$2y$12$thEPnq/mVIIaYVDUyH82/urgQ9wTgThks2fpb4XaM.ahs5yfXMvVW','Ms. Alice Uwase','trainer','+250788000002',NULL),
('student1','$2y$12$thEPnq/mVIIaYVDUyH82/urgQ9wTgThks2fpb4XaM.ahs5yfXMvVW','Jean Claude Habimana','student',NULL,'22RP001'),
('student2','$2y$12$thEPnq/mVIIaYVDUyH82/urgQ9wTgThks2fpb4XaM.ahs5yfXMvVW','Aline Mukamana','student',NULL,'22RP002'),
('qa','$2y$12$thEPnq/mVIIaYVDUyH82/urgQ9wTgThks2fpb4XaM.ahs5yfXMvVW','Director of Quality Assurance','director',NULL,NULL);

INSERT INTO modules (code,name,trainer_id) VALUES
('WDV101','Web Development',(SELECT id FROM users WHERE username='trainer1')),
('DBM102','Databases',(SELECT id FROM users WHERE username='trainer1')),
('NET103','Networking',(SELECT id FROM users WHERE username='trainer2'));
