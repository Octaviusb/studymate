USE studymate_saas;

-- Agregar usuario secretaria
INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status) VALUES
(1, 'secretaria', 'secretaria@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Secretaria Demo', 'secretary', NULL, 'active');