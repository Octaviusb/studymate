-- ===============================
-- STUDYMATE SAAS - TABLAS FALTANTES
-- Solo las tablas que no existen en InfinityFree
-- ===============================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ===============================
-- CREAR SOLO TABLAS FALTANTES
-- ===============================

-- Tabla: organizations (si no existe)
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

-- Tabla: user_profiles (si no existe)
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

-- Tabla: subjects (si no existe)
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

-- Tabla: academic_grades (si no existe)
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

-- Tabla: achievements (si no existe)
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

-- Tabla: student_grades (si no existe)
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

-- Tabla: tasks (si no existe)
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
-- AGREGAR COLUMNAS FALTANTES A USERS
-- ===============================

-- Agregar columna organization_id si no existe
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `organization_id` int(11) NOT NULL DEFAULT 1 AFTER `id`;

-- Agregar columna role si no existe o modificarla
ALTER TABLE `users` MODIFY COLUMN `role` enum('super_admin','admin','coordinator','secretary','teacher','student','parent') NOT NULL;

-- ===============================
-- DATOS INICIALES
-- ===============================

-- Organización demo
INSERT IGNORE INTO `organizations` (`id`, `name`, `email`, `phone`, `address`, `status`) VALUES
(1, 'StudyMate Demo School', 'admin@studymate.com', '+57 300 123 4567', 'Calle 123 #45-67, Bogotá, Colombia', 'active');

-- Actualizar usuarios existentes con organization_id
UPDATE `users` SET `organization_id` = 1 WHERE `organization_id` = 0 OR `organization_id` IS NULL;

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