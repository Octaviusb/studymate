<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuración de la base de datos
require_once 'config.php';

// Función para verificar permisos de superadmin (simplificada para demo)
function requireSuperAdmin() {
    // Para demo, verificar user_id en GET o POST
    $userId = $_GET['user_id'] ?? null;
    if (!$userId) {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? null;
    }

    if ($userId !== '999') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requieren permisos de superadmin']);
        exit;
    }

    return ['user_id' => $userId, 'role' => 'superadmin'];
}

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_plans':
            // Obtener todos los planes activos (sin requerir superadmin)
            $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE is_active = TRUE ORDER BY monthly_fee ASC");
            $stmt->execute();
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'plans' => $plans
            ]);
            break;

        case 'create_subscription':
            requireSuperAdmin();

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['organization_id']) || !isset($data['plan_id'])) {
                throw new Exception('Datos incompletos');
            }

            // Verificar que la organización existe
            $stmt = $pdo->prepare("SELECT id, name FROM organizations WHERE id = ?");
            $stmt->execute([$data['organization_id']]);
            $org = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$org) {
                throw new Exception('Organización no encontrada');
            }

            // Verificar que el plan existe
            $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = TRUE");
            $stmt->execute([$data['plan_id']]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plan) {
                throw new Exception('Plan no encontrado');
            }

            // Verificar que no exista una suscripción activa
            $stmt = $pdo->prepare("SELECT id FROM organization_subscriptions WHERE organization_id = ? AND status IN ('trial', 'active')");
            $stmt->execute([$data['organization_id']]);
            if ($stmt->fetch()) {
                throw new Exception('La organización ya tiene una suscripción activa');
            }

            // Crear suscripción
            $activationDate = date('Y-m-d');
            $nextBillingDate = date('Y-m-d', strtotime('+1 month'));

            $stmt = $pdo->prepare("
                INSERT INTO organization_subscriptions (
                    organization_id, plan_id, status, activation_date, next_billing_date,
                    current_period_start, current_period_end
                ) VALUES (?, ?, 'trial', ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['organization_id'],
                $data['plan_id'],
                $activationDate,
                $nextBillingDate,
                $activationDate,
                date('Y-m-d', strtotime('+30 days'))
            ]);

            $subscriptionId = $pdo->lastInsertId();

            // Crear registro de uso inicial
            $stmt = $pdo->prepare("
                INSERT INTO organization_usage (organization_id, current_users, current_students, current_teachers)
                VALUES (?, 0, 0, 0)
            ");
            $stmt->execute([$data['organization_id']]);

            echo json_encode([
                'success' => true,
                'subscription_id' => $subscriptionId,
                'message' => 'Suscripción creada exitosamente',
                'organization' => $org['name'],
                'plan' => $plan['display_name']
            ]);
            break;

        case 'get_subscriptions':
            requireSuperAdmin();

            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $offset = ($page - 1) * $limit;

            // Obtener suscripciones con información de organización y plan
            $stmt = $pdo->prepare("
                SELECT
                    os.*,
                    o.name as organization_name,
                    sp.display_name as plan_name,
                    sp.monthly_fee,
                    sp.max_users,
                    ou.current_users,
                    ou.current_students,
                    ou.current_teachers
                FROM organization_subscriptions os
                JOIN organizations o ON os.organization_id = o.id
                JOIN subscription_plans sp ON os.plan_id = sp.id
                LEFT JOIN organization_usage ou ON os.organization_id = ou.organization_id
                ORDER BY os.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si no hay suscripciones, devolver array vacío
            if (!$subscriptions) {
                $subscriptions = [];
            }

            // Contar total
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM organization_subscriptions");
            $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult ? $totalResult['total'] : 0;

            echo json_encode([
                'success' => true,
                'subscriptions' => $subscriptions,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;

        case 'update_subscription_status':
            requireSuperAdmin();

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['subscription_id']) || !isset($data['status'])) {
                throw new Exception('Datos incompletos');
            }

            $allowedStatuses = ['trial', 'active', 'suspended', 'cancelled'];
            if (!in_array($data['status'], $allowedStatuses)) {
                throw new Exception('Estado inválido');
            }

            $stmt = $pdo->prepare("UPDATE organization_subscriptions SET status = ? WHERE id = ?");
            $stmt->execute([$data['status'], $data['subscription_id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Estado de suscripción actualizado'
            ]);
            break;

        case 'get_subscription_payments':
            requireSuperAdmin();

            $subscriptionId = $_GET['subscription_id'] ?? null;
            if (!$subscriptionId) {
                throw new Exception('ID de suscripción requerido');
            }

            $stmt = $pdo->prepare("
                SELECT sp.*, os.organization_id, o.name as organization_name
                FROM subscription_payments sp
                JOIN organization_subscriptions os ON sp.subscription_id = os.id
                JOIN organizations o ON os.organization_id = o.id
                WHERE sp.subscription_id = ?
                ORDER BY sp.created_at DESC
            ");
            $stmt->execute([$subscriptionId]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si no hay pagos, devolver array vacío
            if (!$payments) {
                $payments = [];
            }

            echo json_encode([
                'success' => true,
                'payments' => $payments
            ]);
            break;

        case 'create_subscription_payment':
            requireSuperAdmin();

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['subscription_id']) || !isset($data['amount']) || !isset($data['payment_type'])) {
                throw new Exception('Datos incompletos');
            }

            // Generar referencia única
            $reference = 'SUB' . time() . rand(100, 999);

            $stmt = $pdo->prepare("
                INSERT INTO subscription_payments (
                    subscription_id, amount, payment_type, reference, status, due_date
                ) VALUES (?, ?, ?, ?, 'pending', ?)
            ");

            $dueDate = $data['due_date'] ?? date('Y-m-d');
            $stmt->execute([
                $data['subscription_id'],
                $data['amount'],
                $data['payment_type'],
                $reference,
                $dueDate
            ]);

            echo json_encode([
                'success' => true,
                'payment_id' => $pdo->lastInsertId(),
                'reference' => $reference,
                'message' => 'Pago de suscripción registrado'
            ]);
            break;

        case 'get_organization_subscription':
            // Para que las organizaciones vean su propia suscripción
            $orgId = $_GET['organization_id'] ?? null;
            if (!$orgId) {
                throw new Exception('ID de organización requerido');
            }

            $stmt = $pdo->prepare("
                SELECT
                    os.*,
                    sp.display_name as plan_name,
                    sp.monthly_fee,
                    sp.max_users,
                    sp.features,
                    ou.current_users,
                    ou.current_students,
                    ou.current_teachers
                FROM organization_subscriptions os
                JOIN subscription_plans sp ON os.plan_id = sp.id
                LEFT JOIN organization_usage ou ON os.organization_id = ou.organization_id
                WHERE os.organization_id = ? AND os.status IN ('trial', 'active')
                ORDER BY os.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$orgId]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$subscription) {
                echo json_encode([
                    'success' => true,
                    'subscription' => null,
                    'message' => 'No hay suscripción activa'
                ]);
                break;
            }

            echo json_encode([
                'success' => true,
                'subscription' => $subscription
            ]);
            break;

        case 'update_usage':
            // Actualizar contador de uso (llamado automáticamente por el sistema)
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['organization_id'])) {
                throw new Exception('Datos incompletos');
            }

            $stmt = $pdo->prepare("
                INSERT INTO organization_usage (
                    organization_id, current_users, current_students, current_teachers
                ) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    current_users = VALUES(current_users),
                    current_students = VALUES(current_students),
                    current_teachers = VALUES(current_teachers)
            ");

            $stmt->execute([
                $data['organization_id'],
                $data['current_users'] ?? 0,
                $data['current_students'] ?? 0,
                $data['current_teachers'] ?? 0
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Uso actualizado'
            ]);
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>