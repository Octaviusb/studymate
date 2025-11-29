# Módulos Core – StudyMate SaaS (Estado Actual)

Estado actual basado en PHP + MySQL (multi-tenant) con frontend web (HTML/JS) consumiendo APIs REST en PHP. Este documento mapea los módulos funcionales a endpoints, tablas y utilidades de seguridad/tenant/permisos.

Documento generado: 2025-11-06
Versión: 1.0

---

## Visión general de módulos

```
┌───────────────────────────────────────────────────────────────┐
│                    Módulos Core (SaaS)                        │
│  ┌───────────────┬───────────────┬───────────────┬─────────┐ │
│  │ Autenticación │ Organizaciones│ Usuarios/Roles│ Clases  │ │
│  ├───────────────┼───────────────┼───────────────┼─────────┤ │
│  │ Asignaturas   │ Competencias  │ Calificaciones│ Tareas  │ │
│  ├───────────────┼───────────────┼───────────────┼─────────┤ │
│  │ Reportes      │ Bienestar      │ Chat Psicol.  │ Alertas │ │
│  └───────────────┴───────────────┴───────────────┴─────────┘ │
└───────────────────────────────────────────────────────────────┘
```

Utilidades transversales:
- Seguridad: [PHP.SecurityUtils](utils/security.php:7)
- Permisos: [PHP.PermissionsManager](permissions_utils.php:6)
- Tenant: [PHP.TenantManager](utils/tenant.php:7)

---

## 1) Autenticación

- Endpoints:
  - [api/routes/auth.php](api/routes/auth.php)
    - POST /api/auth/login → [PHP.handleLogin()](api/routes/auth.php:79)
    - POST /api/auth/register → [PHP.handleRegister()](api/routes/auth.php:144)

- Tablas clave:
  - users (multi-tenant en [database/schema_saas.sql](database/schema_saas.sql))
  - organizations (para validar estado/plan)

- Token actual:
  - Token “JWT-like” (JSON base64) con { userId, email, role, exp } emitido en login/register.
  - Validaciones recomendadas: migración a JWT firmado (ver ESPECIFICACION_SEGURIDAD.md).

---

## 2) Organizaciones (Tenant)

- Endpoints:
  - [api/routes/organizations.php](api/routes/organizations.php)
    - GET /api/organizations (super_admin)
    - GET /api/organizations/public
    - POST /api/organizations (super_admin)
    - GET /api/organizations/stats (admin)

- Utilidades clave:
  - Validación de tenant: [PHP.TenantManager::validateTenantRequest()](utils/tenant.php:65)
  - Límite de plan: [PHP.TenantManager::checkPlanLimits()](utils/tenant.php:40)

- Tablas:
  - organizations, users, classes, tasks, admin_alerts

- Permisos:
  - Requiere jerarquía de roles (roleHierarchy) en [PHP.TenantManager::validateTenantRequest()](utils/tenant.php:87)

---

## 3) Usuarios, Roles y Permisos

- Permisos por rol:
  - Evaluados mediante [PHP.PermissionsManager::hasPermission()](permissions_utils.php:8) y
    [PHP.PermissionsManager::requirePermission()](permissions_utils.php:60)

- Tablas:
  - users (roles: student, teacher, admin, super_admin)
  - permissions (mapeo rol → permiso) si existe en el esquema de despliegue actual

- Puntos de integración:
  - Endpoints diversos llaman guards de tenant/permiso antes de operar.

---

## 4) Clases (Courses/Groups)

- Endpoints:
  - [api/routes/classes.php](api/routes/classes.php)  (presente en repo)
    - Gestión de clases/grupos y membresías

- Tablas:
  - classes, class_students

- Flujos:
  - Docentes crean clases; estudiantes se asocian mediante class_students; tareas tipo assignment pueden referenciar class_id.

---

## 5) Asignaturas (Subjects)

- Endpoints:
  - [api/routes/subjects.php](api/routes/subjects.php)
    - GET/POST/PUT/DELETE /api/subjects
    - Lógica principal: [PHP.getSubjects()](api/routes/subjects.php:32),
      [PHP.createSubject()](api/routes/subjects.php:53),
      [PHP.updateSubject()](api/routes/subjects.php:89),
      [PHP.deleteSubject()](api/routes/subjects.php:119)

- Tablas:
  - subjects

- Permisos:
  - Operaciones de escritura requieren admin vía tenant guard.

---

## 6) Competencias y Logros (Achievements)

- Endpoints:
  - [api/routes/achievements.php](api/routes/achievements.php)  (presente en repo)

- Tablas:
  - achievements (definiciones por asignatura/periodo), posible relación con student_grades

- Integración:
  - Reportes consolidan logros/competencias con calificaciones (ver módulo de Reportes).

---

## 7) Calificaciones / Niveles Académicos (Grades)

- Endpoints:
  - [api/routes/grades.php](api/routes/grades.php)
    - GET/POST/PUT/DELETE /api/grades
    - Funciones: [PHP.getGrades()](api/routes/grades.php:32),
      [PHP.createGrade()](api/routes/grades.php:55),
      [PHP.updateGrade()](api/routes/grades.php:90),
      [PHP.deleteGrade()](api/routes/grades.php:119)

- Tablas:
  - academic_grades (catálogo de grados académicos)
  - student_grades (registro por estudiante/asignatura/logro/periodo)

- Permisos:
  - Escritura reservada a admin/teacher según política; lectura restringida por tenant.

---

## 8) Tareas (Tasks)

- Endpoints:
  - [api/routes/tasks.php](api/routes/tasks.php)
    - GET/POST/PUT/DELETE /api/tasks
    - Funciones: [PHP.getTasks()](api/routes/tasks.php:30),
      [PHP.createTask()](api/routes/tasks.php:106),
      [PHP.updateTask()](api/routes/tasks.php:164),
      [PHP.deleteTask()](api/routes/tasks.php:238)

- Tablas:
  - tasks (personal o assignment con class_id)
  - student_tasks (seguimiento individual: completed, grade, notes)

- Comportamiento:
  - Estudiante: ve sus tareas asignadas (JOIN con student_tasks).
  - Docente/Admin: ven tareas creadas por ellos y métricas agregadas.

---

## 9) Reportes

- Endpoints:
  - [api/routes/reports.php](api/routes/reports.php)
    - GET /api/reports/student → [PHP.getStudentReport()](api/routes/reports.php:34)
    - GET /api/reports/grade → [PHP.getGradeReport()](api/routes/reports.php:119)
    - GET /api/reports/subject → [PHP.getSubjectReport()](api/routes/reports.php:199)
    - GET /api/reports/teacher → [PHP.getTeacherReport()](api/routes/reports.php:278)
    - GET /api/reports/achievements → [PHP.getAchievementsReport()](api/routes/reports.php:327)

- Tablas:
  - student_grades, subjects, achievements, academic_grades, users

- Notas:
  - KPIs agregados en SQL; respuestas JSON optimizadas por área/periodo/asignatura.

---

## 10) Bienestar Emocional (Wellness)

- Endpoints:
  - [api/routes/wellness.php](api/routes/wellness.php)
    - GET/POST /api/wellness
    - Funciones: [PHP.getWellnessEntries()](api/routes/wellness.php:26),
      [PHP.createWellnessEntry()](api/routes/wellness.php:63)

- Tablas:
  - wellness_entries (mood, mood_value, notes, date)

- Notas:
  - Estadísticos básicos en respuesta (promedios, total_entries).

---

## 11) Chat Psicológico con IA

- Endpoints:
  - [api/routes/chat.php](api/routes/chat.php) (POST /api/chat)
  - Funciones: detección de emergencia [PHP.checkEmergencyKeywords()](api/routes/chat.php:48),
    integración IA [PHP.getAIResponse()](api/routes/chat.php:114),
    fallback local [PHP.getLocalResponse()](api/routes/chat.php:197),
    alertas admin [PHP.sendAdminAlert()](api/routes/chat.php:220)

- Tablas:
  - chat_sessions, chat_messages, admin_alerts

- Integraciones:
  - Gemini/OpenAI via cURL; envío de email de alerta si aplica.

---

## 12) Alertas y Notificaciones

- Generación:
  - Alertas de emergencia creadas desde chat → admin_alerts (estado pending/reviewed/resolved)

- Tablas:
  - admin_alerts

- Outputs:
  - Envío de correo a ADMIN_EMAIL cuando se detectan palabras de emergencia.

---

## Utilidades transversales

- Seguridad: [PHP.SecurityUtils::sanitizeInput()](utils/security.php:12),
  [PHP.SecurityUtils::validateToken()](utils/security.php:31),
  [PHP.SecurityUtils::generateCSRFToken()](utils/security.php:55),
  [PHP.SecurityUtils::validateCSRFToken()](utils/security.php:70),
  [PHP.SecurityUtils::checkRateLimit()](utils/security.php:81),
  [PHP.SecurityUtils::logSecurityEvent()](utils/security.php:108)

- Permisos: [PHP.PermissionsManager::hasPermission()](permissions_utils.php:8),
  [PHP.PermissionsManager::getUserRole()](permissions_utils.php:33),
  [PHP.PermissionsManager::requirePermission()](permissions_utils.php:60)

- Tenant: [PHP.TenantManager::getUserOrganization()](utils/tenant.php:12),
  [PHP.TenantManager::validateTenantRequest()](utils/tenant.php:65),
  [PHP.TenantManager::checkPlanLimits()](utils/tenant.php:40)

---

## Diagrama textual de dependencias

```
[Frontend HTML/JS]
     │ fetch (Bearer token)
     ▼
[API PHP (auth, tasks, wellness, grades, subjects, orgs, reports, chat)]
     │ Guards: TenantManager.validateTenantRequest(), PermissionsManager.requirePermission()
     │ Seguridad: SecurityUtils.sanitizeInput(), checkRateLimit(), headers
     ▼
[MySQL/MariaDB]
     ├─ organizations, users (roles)
     ├─ classes, class_students
     ├─ subjects, achievements, academic_grades
     ├─ tasks, student_tasks
     ├─ wellness_entries
     ├─ chat_sessions, chat_messages
     └─ admin_alerts
```

---

## Notas de uso y ejemplos

- Carga de tareas (estudiante):
  - GET /api/tasks?user_id={id}
  - Respuesta: { tasks, stats } diferenciada por rol (student vs teacher/admin)

- Creación de asignatura (admin):
  - POST /api/subjects (JSON: { name, code, area, ... })

- Chat:
  - POST /api/chat (JSON: { message, sessionId, userId }) con límites de tamaño; emergencia dispara alerta.

---

## Recomendaciones (alineación con seguridad y escalabilidad)

- Migrar token a JWT firmado (HMAC) con expiración corta y refresh.
- Añadir cifrado en reposo para datos sensibles (chat_messages.response/notes).
- Fortalecer CORS con lista blanca por entorno y sin wildcard en producción.
- Auditoría de acciones administrativas (logs estructurados por usuario/tenant).
- Índices adicionales según patrones reales de consulta y volumen.

---

Documento generado: 2025-11-06
Versión: 1.0 (Estado Actual PHP + MySQL)
Proyecto: StudyMate SaaS