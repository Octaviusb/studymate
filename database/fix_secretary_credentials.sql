USE studymate_saas;

-- Actualizar contraseña de secretaria con hash correcto para "123456"
UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'secretaria@demo.com';

-- Verificar que el usuario existe y está activo
SELECT id, username, email, full_name, role, status 
FROM users 
WHERE email = 'secretaria@demo.com';