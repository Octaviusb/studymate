<?php
require_once 'config.php';
header('Content-Type: application/json');

$out = ['success' => false, 'steps' => []];

try {
    $pdo = getDBConnection();

    // Asegurar organización demo
    $stmt = $pdo->prepare("SELECT id FROM organizations WHERE name = ?");
    $stmt->execute(['Demo School']);
    $org = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$org) {
        $pdo->prepare("INSERT INTO organizations (name) VALUES (?)")->execute(['Demo School']);
        $orgId = $pdo->lastInsertId();
        $out['steps'][] = 'organization Demo School created';
    } else {
        $orgId = $org['id'];
        $out['steps'][] = 'organization Demo School exists';
    }

    // Asegurar coordinador demo
    $email = 'coordinador@demo.studymate.com';
    $fullName = 'Coordinador Demo';
    $passwordHash = password_hash('123456', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $sql = "INSERT INTO users (full_name, email, password_hash, role, status, organization_id)
                VALUES (?, ?, ?, 'coordinator', 'active', ?)";
        $pdo->prepare($sql)->execute([$fullName, $email, $passwordHash, $orgId]);
        $out['steps'][] = 'coordinator user created';
    } else {
        // Asegurar campos
        $sql = "UPDATE users SET role='coordinator', status='active', organization_id=? WHERE email=?";
        $pdo->prepare($sql)->execute([$orgId, $email]);
        $out['steps'][] = 'coordinator user updated';
    }

    $out['success'] = true;
    echo json_encode($out);
} catch (Exception $e) {
    http_response_code(500);
    $out['error'] = $e->getMessage();
    echo json_encode($out);
}
