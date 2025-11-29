-- StudyMate SaaS - Optimización de Performance
-- Índices optimizados para consultas frecuentes

USE studymate_saas;

-- Índices para tabla users (consultas más frecuentes)
CREATE INDEX IF NOT EXISTS idx_users_org_role ON users(organization_id, role);
CREATE INDEX IF NOT EXISTS idx_users_email_status ON users(email, status);
CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role, status);
CREATE INDEX IF NOT EXISTS idx_users_grade_org ON users(grade_level, organization_id);
CREATE INDEX IF NOT EXISTS idx_users_fullname ON users(full_name);

-- Índices para tabla organizations
CREATE INDEX IF NOT EXISTS idx_organizations_status ON organizations(status);
CREATE INDEX IF NOT EXISTS idx_organizations_plan ON organizations(plan);

-- Índices para tabla subjects
CREATE INDEX IF NOT EXISTS idx_subjects_org_status ON subjects(organization_id, status);
CREATE INDEX IF NOT EXISTS idx_subjects_name ON subjects(name);

-- Índices para tabla student_grades
CREATE INDEX IF NOT EXISTS idx_grades_student_period ON student_grades(student_id, period);
CREATE INDEX IF NOT EXISTS idx_grades_subject_period ON student_grades(subject_id, period);
CREATE INDEX IF NOT EXISTS idx_grades_org_period ON student_grades(organization_id, period);
CREATE INDEX IF NOT EXISTS idx_grades_graded_at ON student_grades(graded_at);
CREATE INDEX IF NOT EXISTS idx_grades_score ON student_grades(score);

-- Índices para tabla tasks
CREATE INDEX IF NOT EXISTS idx_tasks_teacher_status ON tasks(teacher_id, status);
CREATE INDEX IF NOT EXISTS idx_tasks_org_status ON tasks(organization_id, status);
CREATE INDEX IF NOT EXISTS idx_tasks_due_date ON tasks(due_date);
CREATE INDEX IF NOT EXISTS idx_tasks_subject_grade ON tasks(subject_id, grade_id);

-- Índices para tabla task_submissions
CREATE INDEX IF NOT EXISTS idx_submissions_student_status ON task_submissions(student_id, status);
CREATE INDEX IF NOT EXISTS idx_submissions_task_status ON task_submissions(task_id, status);
CREATE INDEX IF NOT EXISTS idx_submissions_submitted_at ON task_submissions(submitted_at);

-- Índices para tabla files (si existe)
CREATE INDEX IF NOT EXISTS idx_files_org_category ON files(organization_id, category);
CREATE INDEX IF NOT EXISTS idx_files_user_category ON files(user_id, category);
CREATE INDEX IF NOT EXISTS idx_files_uploaded_at ON files(uploaded_at);

-- Optimización de configuración MySQL
SET GLOBAL innodb_buffer_pool_size = 268435456; -- 256MB
SET GLOBAL query_cache_size = 67108864; -- 64MB
SET GLOBAL query_cache_type = 1;
SET GLOBAL slow_query_log = 1;
SET GLOBAL long_query_time = 2;

-- Análisis de tablas para optimizar estadísticas
ANALYZE TABLE users;
ANALYZE TABLE organizations;
ANALYZE TABLE subjects;
ANALYZE TABLE student_grades;
ANALYZE TABLE tasks;
ANALYZE TABLE task_submissions;

-- Optimizar tablas
OPTIMIZE TABLE users;
OPTIMIZE TABLE organizations;
OPTIMIZE TABLE subjects;
OPTIMIZE TABLE student_grades;
OPTIMIZE TABLE tasks;
OPTIMIZE TABLE task_submissions;