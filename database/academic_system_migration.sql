-- StudyMate Academic System Migration
-- Sistema de Calificaciones y Gestión Académica

USE studymate_saas;

-- 1. Tabla de Asignaturas/Materias
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20),
    description TEXT,
    area VARCHAR(50),
    hours_per_week INT DEFAULT 2,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_org_status (organization_id, status),
    UNIQUE KEY unique_code_org (code, organization_id)
);

-- 2. Tabla de Grados Académicos
CREATE TABLE IF NOT EXISTS academic_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    level VARCHAR(20),
    description TEXT,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_org_status (organization_id, status),
    INDEX idx_sort_order (sort_order)
);

-- 3. Tabla de Logros/Indicadores
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    subject_id INT NOT NULL,
    grade_id INT NOT NULL,
    period ENUM('1', '2', '3', '4') NOT NULL,
    code VARCHAR(20),
    description TEXT NOT NULL,
    competency_type ENUM('cognitive', 'procedural', 'attitudinal') DEFAULT 'cognitive',
    weight DECIMAL(3,2) DEFAULT 1.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_id) REFERENCES academic_grades(id) ON DELETE CASCADE,
    INDEX idx_org_subject_grade (organization_id, subject_id, grade_id),
    INDEX idx_period (period)
);

-- 4. Tabla de Calificaciones
CREATE TABLE IF NOT EXISTS student_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    achievement_id INT NOT NULL,
    grade_id INT NOT NULL,
    period ENUM('1', '2', '3', '4') NOT NULL,
    score DECIMAL(4,2) NOT NULL,
    qualitative_grade ENUM('Superior', 'Alto', 'Básico', 'Bajo') NOT NULL,
    observations TEXT,
    graded_by INT NOT NULL,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_id) REFERENCES academic_grades(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_period (student_id, period),
    INDEX idx_org_grade_period (organization_id, grade_id, period),
    UNIQUE KEY unique_student_achievement (student_id, achievement_id, period)
);

-- 5. Tabla de Períodos Académicos
CREATE TABLE IF NOT EXISTS academic_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    period_number ENUM('1', '2', '3', '4') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_org_active (organization_id, is_active)
);

-- Datos demo para organización 1
INSERT IGNORE INTO subjects (organization_id, name, code, area) VALUES
(1, 'Matemáticas', 'MAT', 'Ciencias Exactas'),
(1, 'Español', 'ESP', 'Humanidades'),
(1, 'Ciencias Naturales', 'CN', 'Ciencias'),
(1, 'Ciencias Sociales', 'CS', 'Humanidades'),
(1, 'Inglés', 'ING', 'Idiomas'),
(1, 'Educación Física', 'EF', 'Deportes');

INSERT IGNORE INTO academic_grades (organization_id, name, level, sort_order) VALUES
(1, '6°', 'secundaria', 6),
(1, '7°', 'secundaria', 7),
(1, '8°', 'secundaria', 8),
(1, '9°', 'secundaria', 9),
(1, '10°', 'media', 10),
(1, '11°', 'media', 11);

INSERT IGNORE INTO academic_periods (organization_id, name, period_number, start_date, end_date, is_active) VALUES
(1, 'Primer Período 2024', '1', '2024-02-01', '2024-04-30', TRUE);