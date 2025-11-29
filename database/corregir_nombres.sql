USE studymate_saas;

-- Corregir nombres de usuarios
UPDATE users SET full_name = 'Administrador Organizacional' WHERE email = 'admin@studymate.com';
UPDATE users SET full_name = 'Super Administrador' WHERE email = 'superadmin@studymate.com';