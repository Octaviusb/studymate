<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input || !isset($input['message']) || !isset($input['student_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensaje y ID de estudiante requeridos']);
    exit;
}

$message = trim($input['message']);
$studentId = $input['student_id'];
$sessionId = $input['session_id'] ?? uniqid();

// Respuestas empáticas y profesionales basadas en palabras clave
function generateEmpathicResponse($message) {
    $message = strtolower($message);
    
    // Detectar emociones y situaciones
    if (strpos($message, 'triste') !== false || strpos($message, 'deprimido') !== false || strpos($message, 'mal') !== false) {
        $responses = [
            "Entiendo que te sientes triste en este momento. Es completamente normal sentirse así a veces. ¿Puedes contarme qué ha estado pasando que te hace sentir de esta manera?",
            "Lamento mucho que estés pasando por un momento difícil. Tus sentimientos son válidos y es valiente de tu parte buscar ayuda. ¿Hay algo específico que haya desencadenado estos sentimientos?",
            "Me preocupo por ti y quiero que sepas que no estás solo en esto. La tristeza puede ser muy abrumadora. ¿Has notado si hay momentos del día en que te sientes un poco mejor?"
        ];
    }
    elseif (strpos($message, 'ansiedad') !== false || strpos($message, 'nervioso') !== false || strpos($message, 'preocupado') !== false) {
        $responses = [
            "La ansiedad puede ser muy desafiante de manejar. Es importante que reconozcas que buscar ayuda es un paso muy positivo. ¿Puedes describirme qué situaciones te generan más ansiedad?",
            "Entiendo lo difícil que puede ser lidiar con la ansiedad. Muchos jóvenes experimentan esto. ¿Has notado si hay pensamientos específicos que aumentan tu preocupación?",
            "Te agradezco por compartir esto conmigo. La ansiedad es tratable y hay muchas estrategias que pueden ayudarte. ¿Te gustaría que exploremos algunas técnicas de respiración juntos?"
        ];
    }
    elseif (strpos($message, 'familia') !== false || strpos($message, 'padres') !== false || strpos($message, 'casa') !== false) {
        $responses = [
            "Las relaciones familiares pueden ser complicadas, especialmente durante la adolescencia. Es normal que surjan conflictos. ¿Te sientes cómodo contándome más sobre lo que está pasando en casa?",
            "Entiendo que las situaciones familiares pueden generar mucho estrés. Cada familia tiene sus desafíos únicos. ¿Hay alguien en tu familia con quien te sientes más cómodo hablando?",
            "Las dinámicas familiares pueden afectar mucho nuestro bienestar emocional. Es importante que tengas un espacio seguro para expresar tus sentimientos. ¿Cómo te gustaría que mejoraran las cosas en casa?"
        ];
    }
    elseif (strpos($message, 'amigos') !== false || strpos($message, 'bullying') !== false || strpos($message, 'solo') !== false) {
        $responses = [
            "Las relaciones con los compañeros pueden ser muy importantes a tu edad. Sentirse excluido o solo puede ser muy doloroso. ¿Puedes contarme más sobre tu experiencia con tus compañeros?",
            "Lamento que estés experimentando dificultades sociales. Hacer amigos puede ser desafiante, pero eres una persona valiosa que merece relaciones positivas. ¿Hay actividades que disfrutes donde podrías conocer personas con intereses similares?",
            "El bullying es algo muy serio y me preocupa mucho que puedas estar pasando por eso. Quiero que sepas que no es tu culpa y que mereces ser tratado con respeto. ¿Te sientes seguro en el colegio?"
        ];
    }
    elseif (strpos($message, 'estudios') !== false || strpos($message, 'examen') !== false || strpos($message, 'notas') !== false) {
        $responses = [
            "El estrés académico es muy común entre los estudiantes. Es importante encontrar un equilibrio entre el rendimiento y el bienestar. ¿Qué aspectos de los estudios te resultan más desafiantes?",
            "Entiendo la presión que puedes sentir con respecto a tus estudios. Es admirable que te importe tu educación. ¿Has hablado con tus profesores sobre las dificultades que estás enfrentando?",
            "Los exámenes y las calificaciones pueden generar mucha ansiedad. Recuerda que tu valor como persona no se define por tus notas. ¿Te gustaría que exploremos algunas estrategias de estudio y manejo del estrés?"
        ];
    }
    elseif (strpos($message, 'hola') !== false || strpos($message, 'ayuda') !== false) {
        $responses = [
            "Hola, me alegra mucho que hayas decidido conectarte conmigo hoy. Soy el Dr. García, psicólogo del colegio. Este es un espacio completamente seguro y confidencial donde puedes expresar lo que sientes. ¿Cómo te sientes en este momento?",
            "Bienvenido/a a nuestro espacio de apoyo. Es muy valiente de tu parte buscar ayuda cuando la necesitas. Mi nombre es Dr. Rodríguez y estoy aquí para escucharte sin juzgarte. ¿Hay algo específico que te gustaría compartir conmigo hoy?",
            "Hola, gracias por confiar en mí para acompañarte en este momento. Soy la Dra. Martínez, y quiero que sepas que todo lo que hablemos aquí es completamente confidencial. ¿Qué te ha motivado a buscar apoyo hoy?"
        ];
    }
    else {
        $responses = [
            "Te escucho y valoro mucho que compartas esto conmigo. Cada experiencia es única e importante. ¿Puedes contarme un poco más sobre cómo te sientes respecto a esta situación?",
            "Gracias por confiar en mí y compartir tus pensamientos. Es normal tener sentimientos complejos sobre las situaciones que vivimos. ¿Qué es lo que más te preocupa en este momento?",
            "Entiendo que puede ser difícil poner en palabras lo que sientes. Tómate el tiempo que necesites. Estoy aquí para escucharte y apoyarte. ¿Hay algo específico en lo que te gustaría que te ayude?"
        ];
    }
    
    return $responses[array_rand($responses)];
}

// Detectar mensajes de crisis (autolesiones/suicidio)
function isCrisisMessage($message) {
    $m = mb_strtolower($message, 'UTF-8');
    $keywords = [
        'suicid', 'quitarme la vida', 'matarme', 'morirme', 'hacerme daño', 'autolesion', 'autolesión', 'me quiero morir', 'me quiero matar'
    ];
    foreach ($keywords as $k) {
        if (strpos($m, $k) !== false) return true;
    }
    return false;
}

// Respuesta segura para crisis
function generateCrisisResponse() {
    return "Siento que estés pasando por algo tan difícil. Tu vida es muy valiosa y no estás solo/a. Ahora mismo lo más importante es tu seguridad. Si estás en peligro inmediato o sientes que podrías lastimarte, por favor:
1) Llama a los servicios de emergencia locales de tu país de inmediato.
2) Contacta a un adulto de confianza (familiar, docente, orientador) para que esté contigo.
Si puedes, cuéntame dónde te encuentras (ciudad/país) para orientarte con recursos de ayuda cercanos. Estoy aquí para acompañarte ahora mismo.";
}

// Integración opcional con IA (OpenAI) para respuestas contextuales
function aiAvailable() {
    return defined('AI_PROVIDER') && AI_PROVIDER === 'openai' && defined('OPENAI_API_KEY') && OPENAI_API_KEY;
}

function callOpenAIChat($message) {
    $system = "Eres un psicólogo escolar empático y profesional. Responde en español con calidez, validando emociones, haciendo preguntas abiertas y ofreciendo pasos prácticos. Evita diagnósticos, recetas médicas o consejos peligrosos. Si detectas riesgo de autolesión o suicidio, NO des instrucciones; prioriza seguridad, motiva a buscar ayuda inmediata y sugiere contactar a un adulto de confianza.";

    $payload = [
        'model' => defined('AI_MODEL') ? AI_MODEL : 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 0.7,
        'max_tokens' => 300
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $result = curl_exec($ch);
    if ($result === false) {
        curl_close($ch);
        return null;
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status >= 200 && $status < 300) {
        $data = json_decode($result, true);
        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }
    }
    return null;
}

try {
    $pdo = getDBConnection();
    
    // Preparar respuesta con priorización de seguridad y IA
    if (isCrisisMessage($message)) {
        $response = generateCrisisResponse();
        $strategy = 'crisis';
    } elseif (aiAvailable()) {
        $ai = callOpenAIChat($message);
        if ($ai) {
            $response = $ai;
            $strategy = 'openai';
        } else {
            $response = generateEmpathicResponse($message);
            $strategy = 'fallback_empathic';
        }
    } else {
        $response = generateEmpathicResponse($message);
        $strategy = 'fallback_empathic';
    }

    // Guardar mensaje en la base de datos (si la tabla existe)
    $stmt = $pdo->prepare("INSERT INTO psychological_support_sessions (student_id, session_id, message, response, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$studentId, $sessionId, $message, $response]);
    
    echo json_encode([
        'success' => true,
        'response' => $response,
        'session_id' => $sessionId,
        'psychologist' => 'Dr. García - Psicólogo Escolar',
        'timestamp' => date('H:i'),
        'strategy' => $strategy
    ]);
    
} catch (PDOException $e) {
    // Si no existe la tabla o hay error de DB, responder sin guardar
    $resp = isCrisisMessage($message) ? generateCrisisResponse() : generateEmpathicResponse($message);
    $strat = isCrisisMessage($message) ? 'crisis_no_db' : 'fallback_no_db';
    echo json_encode([
        'success' => true,
        'response' => $resp,
        'session_id' => $sessionId,
        'psychologist' => 'Dr. García - Psicólogo Escolar',
        'timestamp' => date('H:i'),
        'strategy' => $strat
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>