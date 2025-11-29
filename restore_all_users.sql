USE studymate_saas;

DELETE FROM users WHERE organization_id = 1;

INSERT INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status) VALUES
(1, 'superadmin', 'superadmin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrador', 'super_admin', NULL, 'active'),
(1, 'admin', 'admin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin', NULL, 'active'),
(1, 'coordinador', 'coordinador@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Coordinador Demo', 'coordinator', NULL, 'active'),
(1, 'secretaria', 'secretaria@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Secretaria Demo', 'secretary', NULL, 'active'),
(1, 'matematicas', 'matematicas@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Profesora Matemáticas', 'teacher', NULL, 'active'),
(1, 'espanol', 'espanol@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Profesor Español', 'teacher', NULL, 'active'),
(1, 'ana.garcia', 'ana.garcia@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana García', 'student', '6°', 'active'),
(1, 'padre1', 'padre1@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Padre de Familia', 'parent', NULL, 'active');