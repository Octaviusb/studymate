<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

$input = file_get_contents('php://input');
$payload = json_decode($input, true);

function ok($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function err($msg, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

switch ($action) {
    case 'get_wellness_report':
        ok([
            'stats' => [
                'total_entries' => 127,
                'avg_mood' => 7.2,
                'active_alerts' => 3,
                'chat_sessions' => 45
            ],
            'mood_distribution' => [5, 12, 23, 30, 7]
        ]);
        break;

    case 'get_alerts':
        $alerts = [
            [
                'id' => 1,
                'student_id' => 1,
                'student_name' => 'Ana García',
                'alert_type' => 'Estado de ánimo bajo',
                'severity' => 'medium',
                'status' => 'pending',
                'created_at' => date('c', strtotime('-1 day'))
            ],
            [
                'id' => 2,
                'student_id' => 2,
                'student_name' => 'Carlos López',
                'alert_type' => 'Mensaje de emergencia',
                'severity' => 'high',
                'status' => 'tracking',
                'created_at' => date('c', strtotime('-2 day'))
            ]
        ];
        ok(['alerts' => $alerts]);
        break;

    case 'export_wellness_data':
        $csv = "student,alert_type,severity,date\n";
        $csv .= "Ana García,Estado de ánimo bajo,medium," . date('Y-m-d') . "\n";
        $csv .= "Carlos López,Mensaje de emergencia,high," . date('Y-m-d') . "\n";
        ok(['csv_data' => $csv]);
        break;

    case 'update_chat_system':
        ok(['updated' => true]);
        break;

    case 'get_chat_sessions':
        $stats = [
            'active_sessions' => 2,
            'waiting_students' => 1,
            'available_psychologists' => 3,
            'avg_response_time' => 4
        ];
        $sessions = [
            [
                'id' => 101,
                'student_name' => 'Ana García',
                'psychologist_name' => 'Dr. Pérez',
                'status' => 'active',
                'started_at' => date('c', strtotime('-15 minutes'))
            ],
            [
                'id' => 102,
                'student_name' => 'Carlos López',
                'psychologist_name' => null,
                'status' => 'waiting',
                'started_at' => date('c', strtotime('-5 minutes'))
            ]
        ];
        ok(['stats' => $stats, 'sessions' => $sessions]);
        break;

    case 'end_chat_session':
        ok(['ended' => true, 'session_id' => ($_GET['session_id'] ?? null)]);
        break;

    case 'get_psychologists':
        $psychologists = [
            [
                'id' => 1,
                'full_name' => 'Dra. María González',
                'email' => 'maria@example.com',
                'status' => 'available',
                'sessions_today' => 2,
                'specialty' => 'infantil'
            ],
            [
                'id' => 2,
                'full_name' => 'Dr. Juan Pérez',
                'email' => 'juan@example.com',
                'status' => 'busy',
                'sessions_today' => 4,
                'specialty' => 'adolescente'
            ]
        ];
        ok(['psychologists' => $psychologists]);
        break;

    case 'update_psychologist_status':
        ok(['updated' => true]);
        break;

    case 'create_psychologist':
        ok(['created' => true]);
        break;

    case 'activate_emergency_protocol':
        ok(['notified_psychologists' => 3]);
        break;

    default:
        err('Invalid or missing action');
}

?>