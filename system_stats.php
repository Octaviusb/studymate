<?php
require_once 'config.php';

header('Content-Type: application/json');

// Forzar que no se muestren errores HTML
ini_set('display_errors', 0);
error_reporting(0);

try {
    $pdo = getDBConnection();

    // Total organizaciones
    $stmt = $pdo->query("SELECT COUNT(*) FROM organizations");
    $totalOrgs = $stmt->fetchColumn();

    // Total usuarios
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();

    // Total docentes
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'");
    $totalTeachers = $stmt->fetchColumn();

    // Total estudiantes
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $totalStudents = $stmt->fetchColumn();

    // Estadísticas de suscripciones (con manejo de errores si no existe la tabla)
    $activeSubscriptions = 0;
    $monthlyRevenue = 0;

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM organization_subscriptions WHERE status = 'active'");
        $activeSubscriptions = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT SUM(monthly_fee) FROM organization_subscriptions os JOIN subscription_plans sp ON os.plan_id = sp.id WHERE os.status = 'active'");
        $monthlyRevenue = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        // Si las tablas de suscripciones no existen aún, devolver 0
        $activeSubscriptions = 0;
        $monthlyRevenue = 0;
    }

    echo json_encode([
        'success' => true,
        'total_organizations' => (int)$totalOrgs,
        'total_users' => (int)$totalUsers,
        'total_teachers' => (int)$totalTeachers,
        'total_students' => (int)$totalStudents,
        'active_subscriptions' => (int)$activeSubscriptions,
        'monthly_revenue' => (float)$monthlyRevenue
    ]);

} catch (Exception $e) {
    // Asegurar que siempre se devuelve JSON válido
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'total_organizations' => 0,
        'total_users' => 0,
        'total_teachers' => 0,
        'total_students' => 0,
        'active_subscriptions' => 0,
        'monthly_revenue' => 0
    ]);
}
?>