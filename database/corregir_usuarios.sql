USE studymate_saas;

-- Corregir roles de usuarios
UPDATE users SET role = 'admin' WHERE email = 'admin@studymate.com';
UPDATE users SET role = 'super_admin' WHERE email = 'superadmin@studymate.com';

-- Verificar que existan ambos usuarios
INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status) VALUES
(1, 'admin', 'admin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Organizacional', 'admin', NULL, 'active'),
(1, 'superadmin', 'superadmin@studymate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrador', 'super_admin', NULL, 'active');