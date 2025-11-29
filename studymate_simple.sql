-- ===============================
-- STUDYMATE SAAS - VERSIÓN SIMPLE
-- ===============================

-- Agregar columnas faltantes a users
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `role` enum('super_admin','admin','coordinator','secretary','teacher','student','parent') NOT NULL DEFAULT 'student';
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `status` enum('active','inactive') NOT NULL DEFAULT 'active';

-- Crear tablas básicas
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `academic_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(100) NOT NULL,
  `level` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL DEFAULT 1,
  `subject_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL DEFAULT 1,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `achievement_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `period` varchar(50) NOT NULL,
  `numeric_grade` decimal(3,1) NOT NULL,
  `qualitative_grade` enum('Superior','Alto','Básico','Bajo') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL DEFAULT 1,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `due_date` datetime NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Datos básicos
INSERT IGNORE INTO `organizations` (`id`, `name`, `email`, `status`) VALUES (1, 'StudyMate Demo School', 'admin@studymate.com', 'active');

INSERT IGNORE INTO `academic_grades` (`id`, `organization_id`, `name`, `level`) VALUES
(1, 1, 'Preescolar', 0),
(2, 1, 'Primero', 1),
(3, 1, 'Segundo', 2),
(4, 1, 'Tercero', 3),
(5, 1, 'Cuarto', 4),
(6, 1, 'Quinto', 5);

INSERT IGNORE INTO `subjects` (`id`, `organization_id`, `name`, `code`) VALUES
(1, 1, 'Matemáticas', 'MAT'),
(2, 1, 'Español', 'ESP'),
(3, 1, 'Ciencias Naturales', 'CN'),
(4, 1, 'Ciencias Sociales', 'CS'),
(5, 1, 'Inglés', 'ING');

-- Actualizar usuarios existentes
UPDATE `users` SET `organization_id` = 1, `role` = 'admin' WHERE `email` LIKE '%admin%';
UPDATE `users` SET `organization_id` = 1, `role` = 'teacher' WHERE `email` LIKE '%teacher%';
UPDATE `users` SET `organization_id` = 1, `role` = 'student' WHERE `email` LIKE '%student%';
UPDATE `users` SET `organization_id` = 1, `role` = 'parent' WHERE `email` LIKE '%parent%';
UPDATE `users` SET `organization_id` = 1, `role` = 'super_admin' WHERE `email` LIKE '%superadmin%';