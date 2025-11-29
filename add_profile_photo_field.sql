-- Agregar campo para foto de perfil
ALTER TABLE user_profiles 
ADD COLUMN profile_photo VARCHAR(255) NULL AFTER medical_conditions;