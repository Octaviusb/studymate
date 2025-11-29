-- ===============================
-- STUDYMATE SAAS - SOLO LO QUE FALTA
-- ===============================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- ===============================
-- AGREGAR SOLO COLUMNAS FALTANTES A USERS
-- ===============================

-- Agregar columna role si no existe
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `role` enum('super_admin','admin','coordinator','secretary','teacher','student','parent') NOT NULL DEFAULT 'student' AFTER `password`;

-- Agregar columna status si no existe
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `status` enum('active','inactive') NOT NULL DEFAULT 'active' AFTER `role`;

-- ===============================
-- CREAR TABLAS FALTANTES
-- ===============================

CREATE TABLE IF NOT EXISTS `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `academic_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `level` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- DATOS INICIALES
-- ===============================

INSERT IGNORE INTO `organizations` VALUES (1, 'StudyMate Demo School', 'admin@studymate.com', '+57 300 123 4567', 'Calle 123 #45-67, Bogotá', 'active', NOW());

INSERT IGNORE INTO `users` (`id`, `organization_id`, `username`, `email`, `password`, `role`, `status`) VALUES
(1, 1, 'superadmin', 'superadmin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active'),
(2, 1, 'admin', 'admin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
(3, 1, 'teacher1', 'teacher@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active'),
(4, 1, 'student1', 'student@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
(5, 1, 'parent1', 'parent@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'active');

INSERT IGNORE INTO `academic_grades` VALUES
(1, 1, 'Preescolar', 0, 'active', NOW()),
(2, 1, 'Primero', 1, 'active', NOW()),
(3, 1, 'Segundo', 2, 'active', NOW()),
(4, 1, 'Tercero', 3, 'active', NOW()),
(5, 1, 'Cuarto', 4, 'active', NOW()),
(6, 1, 'Quinto', 5, 'active', NOW());

INSERT IGNORE INTO `subjects` VALUES
(1, 1, 'Matemáticas', 'MAT', 'active', NOW()),
(2, 1, 'Español', 'ESP', 'active', NOW()),
(3, 1, 'Ciencias Naturales', 'CN', 'active', NOW()),
(4, 1, 'Ciencias Sociales', 'CS', 'active', NOW()),
(5, 1, 'Inglés', 'ING', 'active', NOW());

INSERT IGNORE INTO `achievements` VALUES
(1, 1, 1, 2, 'MAT-1-1', 'Reconoce y utiliza los números del 1 al 100', 'active', NOW()),
(2, 1, 1, 2, 'MAT-1-2', 'Realiza operaciones básicas de suma y resta', 'active', NOW()),
(3, 1, 2, 2, 'ESP-1-1', 'Lee y comprende textos sencillos', 'active', NOW()),
(4, 1, 2, 2, 'ESP-1-2', 'Escribe palabras y oraciones simples', 'active', NOW());

COMMIT;