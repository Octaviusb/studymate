-- Sistema de Suscripciones SaaS para StudyMate
-- Ejecutar después de la base de datos principal

-- Tabla de planes de suscripción
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    activation_fee DECIMAL(10,2) NOT NULL,
    monthly_fee DECIMAL(10,2) NOT NULL,
    max_users INT NOT NULL,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de suscripciones por organización
CREATE TABLE IF NOT EXISTS organization_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('trial', 'active', 'suspended', 'cancelled') DEFAULT 'trial',
    activation_date DATE,
    next_billing_date DATE,
    current_period_start DATE,
    current_period_end DATE,
    cancel_at_period_end BOOLEAN DEFAULT FALSE,
    stripe_subscription_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

-- Tabla de pagos de suscripción
CREATE TABLE IF NOT EXISTS subscription_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subscription_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'COP',
    payment_type ENUM('activation', 'monthly', 'upgrade') NOT NULL,
    payment_method VARCHAR(50),
    reference VARCHAR(100) UNIQUE,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP NULL,
    due_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES organization_subscriptions(id) ON DELETE CASCADE
);

-- Tabla de uso por organización (para control de límites)
CREATE TABLE IF NOT EXISTS organization_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    current_users INT DEFAULT 0,
    current_students INT DEFAULT 0,
    current_teachers INT DEFAULT 0,
    storage_used_mb DECIMAL(10,2) DEFAULT 0,
    api_calls_month INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_org (organization_id)
);

-- Insertar planes de suscripción
INSERT INTO subscription_plans (name, display_name, description, activation_fee, monthly_fee, max_users, features) VALUES
('basic', 'Plan Básico', 'Ideal para instituciones pequeñas', 2000000.00, 300000.00, 200, '{"students": true, "teachers": true, "reports": true, "communication": true, "mobile_app": false, "api_access": false, "custom_branding": false}'),
('intermediate', 'Plan Intermedio', 'Para instituciones en crecimiento', 2000000.00, 400000.00, 500, '{"students": true, "teachers": true, "reports": true, "communication": true, "mobile_app": true, "api_access": false, "custom_branding": true}'),
('advanced', 'Plan Avanzado', 'Solución completa para grandes instituciones', 2000000.00, 600000.00, 1000, '{"students": true, "teachers": true, "reports": true, "communication": true, "mobile_app": true, "api_access": true, "custom_branding": true, "advanced_analytics": true, "priority_support": true}');

-- Índices para mejor rendimiento (solo crear si no existen)
CREATE INDEX IF NOT EXISTS idx_org_subscriptions ON organization_subscriptions(organization_id, status);
CREATE INDEX IF NOT EXISTS idx_subscription_payments ON subscription_payments(subscription_id, status, due_date);
CREATE INDEX IF NOT EXISTS idx_org_usage ON organization_usage(organization_id);