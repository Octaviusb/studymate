-- ===============================
-- STUDYMATE SAAS - BASE DE DATOS COMPLETA
-- Todas las tablas necesarias para el sistema
-- ===============================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ===============================
-- 1. TABLA ORGANIZATIONS
-- ===============================
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 2. ACTUALIZAR TABLA USERS
-- ===============================
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `organization_id` int(11) NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `role` enum('super_admin','admin','coordinator','secretary','teacher','student','parent') NOT NULL DEFAULT 'student' AFTER `password`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `status` enum('active','inactive') NOT NULL DEFAULT 'active' AFTER `role`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT current_timestamp() AFTER `status`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

-- ===============================
-- 3. TABLA USER_PROFILES
-- ===============================
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `document_type` enum('CC','TI','CE','PP') NOT NULL,
  `document_number` varchar(50) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('M','F','Other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(200) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `medical_info` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 4. TABLA SUBJECTS (MATERIAS)
-- ===============================
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 5. TABLA ACADEMIC_GRADES (GRADOS)
-- ===============================
CREATE TABLE IF NOT EXISTS `academic_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `level` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 6. TABLA ACHIEVEMENTS (LOGROS/INDICADORES)
-- ===============================
CREATE TABLE IF NOT EXISTS `achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 7. TABLA STUDENT_GRADES (CALIFICACIONES)
-- ===============================
CREATE TABLE IF NOT EXISTS `student_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `achievement_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `period` varchar(50) NOT NULL,
  `numeric_grade` decimal(3,1) NOT NULL,
  `qualitative_grade` enum('Superior','Alto','Básico','Bajo') NOT NULL,
  `observations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 8. TABLA TASKS (TAREAS)
-- ===============================
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `due_date` datetime NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 9. TABLA ATTENDANCE (ASISTENCIA)
-- ===============================
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 10. TABLA QUIZZES (EVALUACIONES)
-- ===============================
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `questions` longtext NOT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `time_limit` int(11) DEFAULT NULL,
  `status` enum('draft','active','closed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 11. TABLA QUIZ_RESULTS (RESULTADOS EVALUACIONES)
-- ===============================
CREATE TABLE IF NOT EXISTS `quiz_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `answers` longtext NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `total_points` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 12. TABLA NOTIFICATIONS (NOTIFICACIONES)
-- ===============================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 13. TABLA MESSAGES (MENSAJES)
-- ===============================
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 14. TABLA PSYCHOLOGICAL_CHATS (CHAT PSICOLÓGICO)
-- ===============================
CREATE TABLE IF NOT EXISTS `psychological_chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `response` text NOT NULL,
  `emotion_detected` varchar(50) DEFAULT NULL,
  `support_level` enum('low','medium','high','critical') DEFAULT 'low',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 15. TABLA STUDENT_SUPPORT (APOYO ESTUDIANTIL)
-- ===============================
CREATE TABLE IF NOT EXISTS `student_support` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `support_type` enum('academic','psychological','social','medical') NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- 16. TABLA PAYMENT_RECORDS (REGISTROS DE PAGO)
-- ===============================
CREATE TABLE IF NOT EXISTS `payment_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `concept` varchar(255) NOT NULL,
  `due_date` date NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('pending','paid','overdue','cancelled') DEFAULT 'pending',
  `payment_method` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- DATOS INICIALES
-- ===============================

-- Organización demo
INSERT IGNORE INTO `organizations` (`id`, `name`, `email`, `phone`, `address`, `status`) VALUES
(1, 'StudyMate Demo School', 'admin@studymate.com', '+57 300 123 4567', 'Calle 123 #45-67, Bogotá, Colombia', 'active');

-- Usuarios demo
INSERT IGNORE INTO `users` (`id`, `organization_id`, `username`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 1, 'superadmin', 'superadmin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active'),
(2, 1, 'admin', 'admin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
(3, 1, 'teacher1', 'teacher@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active'),
(4, 1, 'student1', 'student@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
(5, 1, 'parent1', 'parent@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'active');

-- Perfiles de usuario
INSERT IGNORE INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `document_type`, `document_number`, `birth_date`, `gender`, `phone`) VALUES
(1, 'Super', 'Admin', 'CC', '12345678', '1980-01-01', 'M', '3001234567'),
(2, 'Admin', 'Demo', 'CC', '87654321', '1985-05-15', 'F', '3009876543'),
(3, 'María', 'García', 'CC', '11223344', '1990-03-20', 'F', '3001122334'),
(4, 'Juan', 'Pérez', 'TI', '55667788', '2010-08-10', 'M', '3005566778'),
(5, 'Ana', 'López', 'CC', '99887766', '1975-12-05', 'F', '3009988776');

-- Grados académicos
INSERT IGNORE INTO `academic_grades` (`id`, `organization_id`, `name`, `level`) VALUES
(1, 1, 'Preescolar', 0),
(2, 1, 'Primero', 1),
(3, 1, 'Segundo', 2),
(4, 1, 'Tercero', 3),
(5, 1, 'Cuarto', 4),
(6, 1, 'Quinto', 5);

-- Materias
INSERT IGNORE INTO `subjects` (`id`, `organization_id`, `name`, `code`) VALUES
(1, 1, 'Matemáticas', 'MAT'),
(2, 1, 'Español', 'ESP'),
(3, 1, 'Ciencias Naturales', 'CN'),
(4, 1, 'Ciencias Sociales', 'CS'),
(5, 1, 'Inglés', 'ING');

-- Logros/Indicadores
INSERT IGNORE INTO `achievements` (`id`, `organization_id`, `subject_id`, `grade_id`, `code`, `description`) VALUES
(1, 1, 1, 2, 'MAT-1-1', 'Reconoce y utiliza los números del 1 al 100'),
(2, 1, 1, 2, 'MAT-1-2', 'Realiza operaciones básicas de suma y resta'),
(3, 1, 2, 2, 'ESP-1-1', 'Lee y comprende textos sencillos'),
(4, 1, 2, 2, 'ESP-1-2', 'Escribe palabras y oraciones simples');

COMMIT;