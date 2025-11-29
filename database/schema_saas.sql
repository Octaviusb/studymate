-- StudyMate SaaS Multi-Tenant Schema
-- Versión 2.0 con núcleos separados

CREATE DATABASE IF NOT EXISTS studymate_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studymate_saas;

-- Organizations (Núcleos/Instituciones)
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    domain VARCHAR(100) UNIQUE,
    plan ENUM('free', 'basic', 'premium') DEFAULT 'free',
    max_users INT DEFAULT 50,
    settings JSON,
    status ENUM('active', 'suspended', 'trial') DEFAULT 'trial',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain (domain),
    INDEX idx_status (status)
);

-- Users table (Multi-tenant)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'teacher', 'admin', 'super_admin') DEFAULT 'student',
    grade_level VARCHAR(20),
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_org (email, organization_id),
    INDEX idx_org_role (organization_id, role),
    INDEX idx_email (email)
);

-- Classes/Courses (Para que docentes gestionen grupos)
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    teacher_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    grade_level VARCHAR(20),
    description TEXT,
    status ENUM('active', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_org_teacher (organization_id, teacher_id),
    INDEX idx_status (status)
);

-- Student-Class relationships
CREATE TABLE IF NOT EXISTS class_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_class_student (class_id, student_id),
    INDEX idx_class (class_id),
    INDEX idx_student (student_id)
);

-- Tasks table (Multi-tenant + Class assignments)
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    created_by INT NOT NULL, -- teacher_id
    class_id INT NULL, -- NULL = personal task, NOT NULL = class assignment
    title VARCHAR(200) NOT NULL,
    subject VARCHAR(100),
    description TEXT,
    due_date DATE,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    task_type ENUM('personal', 'assignment') DEFAULT 'personal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    INDEX idx_org_class (organization_id, class_id),
    INDEX idx_due_date (due_date),
    INDEX idx_type (task_type)
);

-- Student task assignments (Individual progress tracking)
CREATE TABLE IF NOT EXISTS student_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    student_id INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    completion_date TIMESTAMP NULL,
    notes TEXT,
    grade DECIMAL(5,2) NULL,
    graded_by INT NULL,
    graded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_task_student (task_id, student_id),
    INDEX idx_student_completed (student_id, completed),
    INDEX idx_task (task_id)
);

-- Wellness entries (Multi-tenant)
CREATE TABLE IF NOT EXISTS wellness_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    user_id INT NOT NULL,
    mood ENUM('excellent', 'good', 'okay', 'bad', 'terrible') NOT NULL,
    mood_value TINYINT NOT NULL,
    notes TEXT,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_org_user (organization_id, user_id),
    INDEX idx_date (date),
    UNIQUE KEY unique_user_date (user_id, date)
);

-- Chat sessions (Multi-tenant)
CREATE TABLE IF NOT EXISTS chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    user_id INT NOT NULL,
    session_id VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_org_user (organization_id, user_id),
    INDEX idx_session_id (session_id)
);

-- Chat messages (Multi-tenant)
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    session_id VARCHAR(100) NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    response TEXT,
    is_emergency BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_org_session (organization_id, session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_emergency (is_emergency)
);

-- Admin alerts (Multi-tenant)
CREATE TABLE IF NOT EXISTS admin_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    user_id INT NOT NULL,
    alert_type ENUM('emergency', 'warning', 'info') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    message TEXT NOT NULL,
    user_message TEXT,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_org_status (organization_id, status),
    INDEX idx_severity (severity)
);

-- Insert demo organization
INSERT IGNORE INTO organizations (name, domain, plan, max_users, status) 
VALUES ('Demo School', 'demo.studymate.com', 'premium', 500, 'active');

-- Insert super admin
INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role) 
VALUES (1, 'superadmin', 'admin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'super_admin');