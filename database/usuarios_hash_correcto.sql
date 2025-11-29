USE studymate_saas;

-- Eliminar usuarios existentes
DELETE FROM users WHERE organization_id = 1;

-- Insertar usuarios con hash generado específicamente para "123456"
INSERT INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status) VALUES
(1, 'admin', 'admin@studymate.com', '$2y$10$EIXw8Z8sJQf8rJkKJxJ8/.vGA8P9wHQOQVwqJxJ8/.vGA8P9wHQOQV', 'Super Administrador', 'super_admin', NULL, 'active'),
(1, 'coordinador', 'coordinador@demo.com', '$2y$10$EIXw8Z8sJQf8rJkKJxJ8/.vGA8P9wHQOQVwqJxJ8/.vGA8P9wHQOQV', 'Coordinador Demo', 'coordinator', NULL, 'active'),
(1, 'secretaria', 'secretaria@demo.com', '$2y$10$EIXw8Z8sJQf8rJkKJxJ8/.vGA8P9wHQOQVwqJxJ8/.vGA8P9wHQOQV', 'Secretaria Demo', 'secretary', NULL, 'active'),
(1, 'matematicas', 'matematicas@demo.com', '$2y$10$EIXw8Z8sJQf8rJkKJxJ8/.vGA8P9wHQOQVwqJxJ8/.vGA8P9wHQOQV', 'Profesora Matemáticas', 'teacher', NULL, 'active'),
(1, 'espanol', 'espanol@demo.com', '$2y$10$EIXw8Z8sJQf8rJkKJxJ8/.vGA8P9wHQOQVwqJxJ8/.vGA8P9wHQOQV', 'Profesor Español', 'teacher', NULL, 'active'),
(1, 'ana.garcia', 'ana.garcia@demo.com', '$2y$10$EIXw8Z8sJQf8rJkKJxJ8/.vGA8P9wHQOQVwqJxJ8/.vGA8P9wHQOQV', 'Ana García', 'student', '6°', 'active'),
(1, 'padre1', 'padre1@demo.com', '$2y$10$EIXw8Z8sJQf8rJkKJxJ8/.vGA8P9wHQOQVwqJxJ8/.vGA8P9wHQOQV', 'Padre de Familia', 'parent', NULL, 'active');