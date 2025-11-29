-- Sistema de Pagos - StudyMate SaaS
-- Tabla para almacenar pagos realizados por estudiantes

CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('pse', 'nequi', 'daviplata', 'transfer') NOT NULL,
    description VARCHAR(255) NOT NULL,
    reference VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    payment_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_student_id (student_id),
    INDEX idx_reference (reference),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Tabla para configuración de métodos de pago
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    method_name VARCHAR(50) NOT NULL,
    method_code VARCHAR(20) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    config_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_method_code (method_code),
    INDEX idx_is_active (is_active)
);

-- Insertar métodos de pago disponibles
INSERT INTO payment_methods (method_name, method_code, config_data) VALUES
('PSE - Pagos Seguros en Línea', 'pse', '{"url": "https://www.pse.com.co/persona", "banks": ["Bancolombia", "Davivienda", "BBVA"]}'),
('Nequi', 'nequi', '{"phone_required": true, "verification_required": true}'),
('Daviplata', 'daviplata', '{"qr_required": true, "phone_required": true}'),
('Transferencia Bancaria', 'transfer', '{"bank_details": {"name": "Bancolombia", "account": "123-456789-0", "holder": "StudyMate SaaS", "nit": "901.234.567-8"}}')
ON DUPLICATE KEY UPDATE method_name = VALUES(method_name);

-- Tabla para notificaciones de pago
CREATE TABLE IF NOT EXISTS payment_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    notification_type ENUM('email', 'sms', 'webhook') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,

    INDEX idx_payment_id (payment_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Tabla para configuración de emails de notificación
CREATE TABLE IF NOT EXISTS email_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(50) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuración de email por defecto
INSERT INTO email_config (config_key, config_value) VALUES
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_user', 'noreply@studymatesaas.com'),
('smtp_pass', 'your_password_here'),
('from_email', 'noreply@studymatesaas.com'),
('from_name', 'StudyMate SaaS'),
('payment_notification_subject', 'Confirmación de Pago - StudyMate SaaS'),
('payment_notification_enabled', 'true')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);