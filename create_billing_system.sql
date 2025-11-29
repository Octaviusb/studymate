-- Sistema de facturación y morosidad conforme a la ley colombiana

-- Tabla de períodos académicos
CREATE TABLE IF NOT EXISTS academic_periods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    period_type ENUM('bimestre', 'trimestre', 'semestre') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id)
);

-- Tabla de conceptos de pago
CREATE TABLE IF NOT EXISTS payment_concepts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id)
);

-- Tabla de facturas
CREATE TABLE IF NOT EXISTS student_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    student_id INT NOT NULL,
    period_id INT NOT NULL,
    concept_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (period_id) REFERENCES academic_periods(id),
    FOREIGN KEY (concept_id) REFERENCES payment_concepts(id)
);

-- Tabla de notificaciones de morosidad (cumple Art. 22 - 15 días previos)
CREATE TABLE IF NOT EXISTS morosity_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    student_id INT NOT NULL,
    invoice_id INT NOT NULL,
    notification_type ENUM('first_notice', 'final_notice', 'suspension_notice') NOT NULL,
    notification_date DATE NOT NULL,
    due_process_date DATE NOT NULL, -- 15 días después
    status ENUM('sent', 'acknowledged', 'resolved') DEFAULT 'sent',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id)
);

-- Tabla de suspensiones (solo al final de período)
CREATE TABLE IF NOT EXISTS service_suspensions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    student_id INT NOT NULL,
    period_id INT NOT NULL,
    suspension_type ENUM('partial', 'total') NOT NULL,
    reason TEXT NOT NULL,
    suspension_date DATE NOT NULL,
    reinstatement_date DATE NULL,
    legal_compliance_notes TEXT, -- Documentar cumplimiento legal
    status ENUM('active', 'lifted', 'appealed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (period_id) REFERENCES academic_periods(id)
);

-- Tabla de acuerdos de pago (protege derecho a la educación)
CREATE TABLE IF NOT EXISTS payment_agreements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    student_id INT NOT NULL,
    total_debt DECIMAL(10,2) NOT NULL,
    monthly_payment DECIMAL(10,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'completed', 'breached') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- Insertar datos de ejemplo
INSERT INTO academic_periods (organization_id, name, period_type, start_date, end_date) VALUES
(1, 'Primer Bimestre 2024', 'bimestre', '2024-01-15', '2024-03-15'),
(1, 'Segundo Bimestre 2024', 'bimestre', '2024-03-16', '2024-05-15');

INSERT INTO payment_concepts (organization_id, name, description, amount) VALUES
(1, 'Pensión Mensual', 'Pago mensual de pensión escolar', 150000.00),
(1, 'Matrícula', 'Pago anual de matrícula', 300000.00),
(1, 'Seguro Estudiantil', 'Seguro obligatorio estudiantil', 50000.00);