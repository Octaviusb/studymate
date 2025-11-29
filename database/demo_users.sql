-- Demo Users for StudyMate SaaS
-- Password for all demo users: 123456

USE studymate_saas;

-- Insert demo users with proper password hash for "123456"
INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status) VALUES
-- Super Admin
(1, 'superadmin', 'superadmin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrador', 'super_admin', NULL, 'active'),

-- Organization Admin
(1, 'admin', 'admin@demo.studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Demo', 'admin', NULL, 'active'),

-- Teachers
(1, 'teacher1', 'teacher1@demo.studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María García', 'teacher', NULL, 'active'),
(1, 'teacher2', 'teacher2@demo.studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos López', 'teacher', NULL, 'active'),

-- Students
(1, 'student1', 'student1@demo.studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana Rodríguez', 'student', '6°', 'active'),
(1, 'student2', 'student2@demo.studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Pérez', 'student', '7°', 'active'),
(1, 'student3', 'student3@demo.studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Laura Martínez', 'student', '8°', 'active');

-- Insert demo classes
INSERT IGNORE INTO classes (organization_id, teacher_id, name, subject, grade_level, description) VALUES
(1, 3, 'Matemáticas 6°A', 'Matemáticas', '6°', 'Clase de matemáticas para sexto grado'),
(1, 3, 'Matemáticas 7°A', 'Matemáticas', '7°', 'Clase de matemáticas para séptimo grado'),
(1, 4, 'Español 6°A', 'Español', '6°', 'Clase de español para sexto grado'),
(1, 4, 'Español 8°A', 'Español', '8°', 'Clase de español para octavo grado');

-- Assign students to classes
INSERT IGNORE INTO class_students (class_id, student_id) VALUES
(1, 5), -- Ana en Matemáticas 6°A
(2, 6), -- Juan en Matemáticas 7°A  
(3, 5), -- Ana en Español 6°A
(4, 7); -- Laura en Español 8°A