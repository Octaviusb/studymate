-- ===============================
-- STUDYMATE SAAS - EXPORTACIÓN COMPLETA
-- Para importar en InfinityFree
-- ===============================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ===============================
-- ESTRUCTURA DE TABLAS
-- ===============================

-- Tabla: organizations
CREATE TABLE `organizations` (
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

-- Tabla: users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','coordinator','secretary','teacher','student','parent') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `organization_id` (`organization_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: user_profiles
CREATE TABLE `user_profiles` (
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
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: subjects
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`),
  CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: academic_grades
CREATE TABLE `academic_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `level` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`),
  CONSTRAINT `academic_grades_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: achievements
CREATE TABLE `achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`),
  KEY `subject_id` (`subject_id`),
  KEY `grade_id` (`grade_id`),
  CONSTRAINT `achievements_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `achievements_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `achievements_ibfk_3` FOREIGN KEY (`grade_id`) REFERENCES `academic_grades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: student_grades
CREATE TABLE `student_grades` (
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
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`),
  KEY `achievement_id` (`achievement_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `student_grades_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `student_grades_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  CONSTRAINT `student_grades_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `student_grades_ibfk_4` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`),
  CONSTRAINT `student_grades_ibfk_5` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: tasks
CREATE TABLE `tasks` (
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
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `subject_id` (`subject_id`),
  KEY `grade_id` (`grade_id`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`grade_id`) REFERENCES `academic_grades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================
-- DATOS INICIALES
-- ===============================

-- Organización demo
INSERT INTO `organizations` (`id`, `name`, `email`, `phone`, `address`, `status`) VALUES
(1, 'StudyMate Demo School', 'admin@studymate.com', '+57 300 123 4567', 'Calle 123 #45-67, Bogotá, Colombia', 'active');

-- Usuarios demo
INSERT INTO `users` (`id`, `organization_id`, `username`, `email`, `password`, `role`, `status`) VALUES
(1, 1, 'superadmin', 'superadmin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active'),
(2, 1, 'admin', 'admin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
(3, 1, 'teacher1', 'teacher@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active'),
(4, 1, 'student1', 'student@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
(5, 1, 'parent1', 'parent@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'active');

-- Perfiles de usuario
INSERT INTO `user_profiles` (`user_id`, `first_name`, `last_name`, `document_type`, `document_number`, `birth_date`, `gender`, `phone`) VALUES
(1, 'Super', 'Admin', 'CC', '12345678', '1980-01-01', 'M', '3001234567'),
(2, 'Admin', 'Demo', 'CC', '87654321', '1985-05-15', 'F', '3009876543'),
(3, 'María', 'García', 'CC', '11223344', '1990-03-20', 'F', '3001122334'),
(4, 'Juan', 'Pérez', 'TI', '55667788', '2010-08-10', 'M', '3005566778'),
(5, 'Ana', 'López', 'CC', '99887766', '1975-12-05', 'F', '3009988776');

-- Grados académicos
INSERT INTO `academic_grades` (`organization_id`, `name`, `level`) VALUES
(1, 'Preescolar', 0),
(1, 'Primero', 1),
(1, 'Segundo', 2),
(1, 'Tercero', 3),
(1, 'Cuarto', 4),
(1, 'Quinto', 5);

-- Materias
INSERT INTO `subjects` (`organization_id`, `name`, `code`) VALUES
(1, 'Matemáticas', 'MAT'),
(1, 'Español', 'ESP'),
(1, 'Ciencias Naturales', 'CN'),
(1, 'Ciencias Sociales', 'CS'),
(1, 'Inglés', 'ING');

-- Logros/Indicadores
INSERT INTO `achievements` (`organization_id`, `subject_id`, `grade_id`, `code`, `description`) VALUES
(1, 1, 2, 'MAT-1-1', 'Reconoce y utiliza los números del 1 al 100'),
(1, 1, 2, 'MAT-1-2', 'Realiza operaciones básicas de suma y resta'),
(1, 2, 2, 'ESP-1-1', 'Lee y comprende textos sencillos'),
(1, 2, 2, 'ESP-1-2', 'Escribe palabras y oraciones simples');

COMMIT;