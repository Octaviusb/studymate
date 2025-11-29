# Arquitectura del Stack - StudyMate SaaS (Estado Actual)

## Visión general
StudyMate SaaS opera con una arquitectura en 3 capas sobre un stack PHP + MySQL/MariaDB, con frontend web tradicional (HTML/CSS/JS) consumiendo APIs REST en PHP y persistencia en MySQL con esquema multi-tenant (organization_id).

```
┌─────────────────────────────────────────────────────────┐
│                    CAPA DE PRESENTACIÓN                 │
│                (Frontend Web - HTML/CSS/JS)            │
│  • Páginas HTML + JS (fetch)                            │
│  • UI modular (JS)                                      │
└─────────────────────────────────────────────────────────┘
                      ↕ HTTP/REST (JSON)
┌─────────────────────────────────────────────────────────┐
│                   CAPA DE APLICACIÓN                    │
│                       (PHP 7.4+)                        │
│  • Rutas API (controladores ligeros)                    │
│  • Multi-tenant (organization_id)                       │
│  • Seguridad (rate limit, sanitización, permisos)       │
│  • Integraciones (IA, email)                            │
└─────────────────────────────────────────────────────────┘
                          ↕ PDO (MySQL)
┌─────────────────────────────────────────────────────────┐
│                   CAPA DE PERSISTENCIA                  │
│                 (MySQL/MariaDB, InnoDB)                 │
│  • Esquema SaaS compartido                              │
│  • Índices por organización y claves de negocio         │
└─────────────────────────────────────────────────────────┘
```

Archivos relevantes:
- Rutas API: [api/routes/auth.php](api/routes/auth.php), [api/routes/tasks.php](api/routes/tasks.php), [api/routes/wellness.php](api/routes/wellness.php), [api/routes/grades.php](api/routes/grades.php), [api/routes/subjects.php](api/routes/subjects.php), [api/routes/organizations.php](api/routes/organizations.php), [api/routes/reports.php](api/routes/reports.php)
- Utilidades: [utils/security.php](utils/security.php), [utils/tenant.php](utils/tenant.php), [permissions_utils.php](permissions_utils.php)
- Esquema BD: [database/schema_saas.sql](database/schema_saas.sql), [database/schema.sql](database/schema.sql)

---

## Flujo de comunicación entre capas

1) Frontend (HTML/JS) → Backend (PHP)
- Protocolo: HTTP/HTTPS (REST, JSON)
- Autenticación: token “JWT-like” (JSON base64) emitido por [api/routes/auth.php](api/routes/auth.php)
- Ejemplo de consumo desde JS:
```js
// Frontend (vanilla JS)
const token = localStorage.getItem('authToken');
const userId = JSON.parse(atob(token)).userId; // token base64 con JSON { userId, role, exp }

const res = await fetch('/api/tasks?user_id=' + userId, {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  }
});

const data = await res.json();
```

2) Backend (PHP) → Base de Datos (MySQL)
- Driver: PDO (PHP Data Objects)
- Charset: utf8mb4
- Conexión: definida en config (DSN, credenciales) y usada por controladores
- Query multi-tenant (aislar por organization_id):
```php
// Ejemplo en rutas: ver SELECTs en [api/routes/tasks.php](api/routes/tasks.php)
$stmt = $pdo->prepare("
  SELECT t.* 
  FROM tasks t
  WHERE t.organization_id = ? 
  ORDER BY t.due_date ASC
");
$stmt->execute([$organizationId]);
```

---

## Arquitectura multi-tenant

Estrategia: Base de datos compartida, esquema compartido (shared DB, shared schema). Todas las tablas clave incluyen organization_id para segmentación por institución/colegio.

```
┌───────────────────────────────────────────────┐
│         Base de Datos Compartida              │
│  Tablas con columna: organization_id          │
│  • users, classes, subjects, tasks,           │
│    student_tasks, wellness_entries,           │
│    chat_sessions, chat_messages, admin_alerts │
└───────────────────────────────────────────────┘
```

- Validación de tenant por request: [PHP.TenantManager::validateTenantRequest()](utils/tenant.php:65)
- Límite por plan: [PHP.TenantManager::checkPlanLimits()](utils/tenant.php:40)

Ventajas:
- Costos contenidos, administración centralizada, backups y escalado horizontal (rutas sin estado).

Consideraciones:
- Validar siempre organization_id del usuario autenticado antes de operar sobre datos.
- Usar consultas parametrizadas (PDO) para prevenir inyección SQL.

---

## Stack tecnológico

Frontend
- HTML5/CSS3 (plantillas en /, dashboards HTML)
- JavaScript ES6+ (Fetch API, modularidad)
- Carga diferida (dynamic import) p. ej.:
```js
import('./ui_components.js').then(m => m.initUI?.());
```
Archivo relacionado: [ui_components.js](ui_components.js)

Backend
- PHP 7.4+
- PDO (MySQL)
- cURL (integraciones externas)
- Utilidades de seguridad: [PHP.SecurityUtils](utils/security.php), [PHP.PermissionsManager](permissions_utils.php:6), [PHP.TenantManager](utils/tenant.php:7)

Base de datos
- MySQL/MariaDB (InnoDB, utf8mb4)
- Esquema SaaS: [database/schema_saas.sql](database/schema_saas.sql)

Integraciones externas
- Gemini/OpenAI (IA en chat): [api/routes/chat.php](api/routes/chat.php)
- SMTP (notificaciones/alertas)

---

## Patrones aplicados

- Controladores ligeros por ruta (estilo “micro-MVC”)
  - Rutas → lógica de entrada/salida y orquestación
  - Servicios utilitarios → seguridad/tenant/permisos
- Middleware/guards explícitos en código:
  - Rate limit: [PHP.SecurityUtils::checkRateLimit()](utils/security.php:81)
  - Tenant: [PHP.TenantManager::validateTenantRequest()](utils/tenant.php:65)
  - Permisos: [PHP.PermissionsManager::requirePermission()](permissions_utils.php:60)

---

## Mapeo de módulos a endpoints (resumen)

- Autenticación: [api/routes/auth.php](api/routes/auth.php) (login, register)
- Tareas: [api/routes/tasks.php](api/routes/tasks.php) (GET/POST/PUT/DELETE)
- Bienestar: [api/routes/wellness.php](api/routes/wellness.php) (GET/POST)
- Calificaciones/Nivel Académico: [api/routes/grades.php](api/routes/grades.php)
- Asignaturas: [api/routes/subjects.php](api/routes/subjects.php)
- Organizaciones: [api/routes/organizations.php](api/routes/organizations.php)
- Reportes: [api/routes/reports.php](api/routes/reports.php)
- Chat Psicológico (IA): [api/routes/chat.php](api/routes/chat.php)

Ver detalle de payloads en [api/README.md](api/README.md).

---

## Flujo de datos (ejemplo: Tareas)

```
Usuario (Navegador)
   │ 1. UI HTML/JS
   │
   │ 2. GET /api/tasks?user_id={id} (Bearer token)
   ▼
API PHP (tasks.php)
   │ 3. Validar token y tenant
   │ 4. Consultar DB por organization_id
   ▼
MySQL
   │ 5. Retorno filas
   ▼
API PHP
   │ 6. JSON { tasks, stats }
   ▼
Frontend
   │ 7. Render
```

---

## Seguridad y compliance (resumen)

- HTTPS/TLS en producción (Apache)
- Encabezados de seguridad en rutas:
  - Ejemplos en [api/routes/auth.php](api/routes/auth.php), [api/routes/tasks.php](api/routes/tasks.php), [api/routes/chat.php](api/routes/chat.php)
- CORS controlado (lista de orígenes permitidos + fallback dev)
- Sanitización/validación de entradas: [PHP.SecurityUtils::sanitizeInput()](utils/security.php:12)
- Rate limiting básico: [PHP.SecurityUtils::checkRateLimit()](utils/security.php:81)
- Autorización por roles/permisos: [PHP.PermissionsManager](permissions_utils.php)
- Aislamiento multi-tenant: [PHP.TenantManager](utils/tenant.php)
- Logs de seguridad (error_log): [PHP.SecurityUtils::logSecurityEvent()](utils/security.php:108)
- Límite de tamaño de payload en endpoints críticos (e.g., chat, tasks)

Configuración CORS (ejemplo):
```php
// Ver auth.php
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
```

Nota sobre tokens:
- Actualmente se usa un token base64 con JSON (JWT-like) emitido en [api/routes/auth.php](api/routes/auth.php) (campos: userId, email, role, exp). Para endurecer seguridad se recomienda JWT firmado (HMAC) y rotación de claves (detallado en ESPECIFICACION_SEGURIDAD.md).

---

## Rendimiento y escalabilidad

Optimizaciones
- Índices en columnas de filtrado frecuentes (organization_id, fechas, estados)
- Consultas parametrizadas y específicas por vista
- Carga diferida de módulos del frontend
- Estadísticas agregadas calculadas en SQL cuando aplica
- Endpoints con límites de tamaño (413) para proteger recursos

Índices recomendados (presentes en schema_saas.sql):
- idx_org_role (users)
- idx_org_class (tasks)
- idx_student_completed (student_tasks)
- idx_org_user / idx_date (wellness_entries)
- idx_org_session (chat_messages)
- idx_status / idx_severity (admin_alerts)

---

## Despliegue

Entorno
- Servidor Web: Apache 2.4+
- PHP: 7.4+ (extensiones: PDO, cURL, OpenSSL)
- MySQL/MariaDB: InnoDB, utf8mb4

Variables (ejemplo)
```php
define('DB_HOST', '...');
define('DB_NAME', 'studymate_saas');
define('DB_USER', '...');
define('DB_PASS', '...');
define('GEMINI_API_KEY', '...');
define('ADMIN_EMAIL', 'admin@studymate.com');
```

Hosting
- Compatible con shared hosting (ej. InfinityFree) y despliegue en servicios cloud.

---

## Monitoreo y logging

- error_log para eventos de seguridad y errores de API
- Métricas sugeridas:
  - Latencia por endpoint
  - Tasa de errores (4xx/5xx)
  - Uso de CPU/RAM en servidor
  - Conexiones activas a DB

Ejemplos:
```php
error_log('SECURITY: ' . json_encode($logEntry));
error_log('Emergency keywords detected (User: ' . $userId . ')');
```

---

Documento generado: 2025-11-06
Versión: 1.1 (Estado Actual PHP + MySQL)
Proyecto: StudyMate SaaS
