USE studymate_saas;

-- Tabla de tareas
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    grade_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    max_score DECIMAL(4,2) DEFAULT 10.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_id) REFERENCES academic_grades(id) ON DELETE CASCADE,
    INDEX idx_teacher_subject (teacher_id, subject_id),
    INDEX idx_due_date (due_date)
);

-- Tabla de entregas de tareas
CREATE TABLE IF NOT EXISTS task_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_text TEXT,
    file_path VARCHAR(500),
    score DECIMAL(4,2),
    feedback TEXT,
    submitted_at TIMESTAMP NULL,
    graded_at TIMESTAMP NULL,
    status ENUM('pending', 'submitted', 'graded', 'late') DEFAULT 'pending',
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_task_student (task_id, student_id),
    INDEX idx_student_status (student_id, status)
);

-- Datos demo
INSERT INTO tasks (organization_id, teacher_id, subject_id, grade_id, title, description, due_date) VALUES
(1, (SELECT id FROM users WHERE email = 'matematicas@demo.com'), 1, 1, 'Ejercicios de Álgebra - Capítulo 5', 'Resolver ejercicios 1-20 del libro de texto', '2024-12-15'),
(1, (SELECT id FROM users WHERE email = 'espanol@demo.com'), 2, 1, 'Ensayo sobre la Independencia', 'Escribir un ensayo de 500 palabras sobre la independencia de Colombia', '2024-12-18'),
(1, (SELECT id FROM users WHERE email = 'matematicas@demo.com'), 1, 1, 'Laboratorio de Reacciones', 'Completar el informe del laboratorio de química', '2024-12-10');

-- Crear entregas para estudiantes
INSERT INTO task_submissions (task_id, student_id, status) 
SELECT t.id, u.id, 'pending'
FROM tasks t
CROSS JOIN users u 
WHERE u.role = 'student' AND u.organization_id = 1;