# ESPECIFICACIÓN DE SEGURIDAD – StudyMate SaaS (Estado Actual)

Estado actual: PHP 7.4+ con APIs REST y MySQL/MariaDB multi-tenant (organization_id). Alcance: autenticación, autorización, cifrado de datos sensibles, transporte, CORS/headers, validación, rate limiting y logging con referencias a código.

Documento generado: 2025-11-06
Versión: 1.0

---

1. Modelo de amenaza (resumen)

- Actores: usuarios finales, docentes/admins, super_admin, atacantes externos, personal de soporte.
- Superficies: endpoints bajo /api (auth, tasks, wellness, grades, subjects, organizations, reports, chat), ficheros de carga estáticos, email saliente.
- Riesgos principales: robo/abuso de token, accesos entre tenants, inyección SQL, exposición de PII/psicológica, CORS laxo, falta de firma del token, XSS en render del frontend.
- Controles existentes: sanitización, headers de seguridad, validación de tenant, permisos por rol, límites de tamaño de payload, rate limiting básico y alertas de emergencia.

---

2. Autenticación

2.1 Estado actual
- Login/register en [api/routes/auth.php](api/routes/auth.php)
- Emisión de token “JWT-like” (JSON base64 sin firma) con { userId, email, role, exp }:
  - Generación en [PHP.handleLogin()](api/routes/auth.php:79) y [PHP.handleRegister()](api/routes/auth.php:144)
  - Validación básica en [PHP.SecurityUtils::validateToken()](utils/security.php:31)
- Riesgos: token sin firma puede ser forjado; persistencia en localStorage susceptible a XSS; exp de 24h quizá extensa.

2.2 Recomendación inmediata (corto plazo)
- Migrar a JWT firmado (HMAC-SHA256) con secreto [JWT_SECRET] en config (env).
- Reducir exp (por ejemplo 15–60 min) y añadir refresh token rotatorio (HTTP-only, SameSite=Strict).
- Almacenar access token en memoria o en cookie HTTP-only; evitar localStorage en producción.

2.3 Especificación propuesta (JWT)
- Cabecera: alg=HS256, typ=JWT
- Claims mínimos: sub=userId, role, org=organization_id, iat, exp, jti
- Firma con clave rotada (versión kid en header).
- Endpoint de refresh: /api/auth/refresh con lista de revocación por jti.

2.4 Ejemplo (PHP, conceptual)
```php
$payload = ['sub' => $userId, 'role' => $role, 'org' => $orgId, 'iat' => time(), 'exp' => time()+900, 'jti' => bin2hex(random_bytes(16))];
$jwt = \Firebase\JWT\JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256', $kid);
```

2.5 Gestión de sesión y CSRF
- Para APIs stateless con Authorization: Bearer no aplica CSRF estándar.
- Si se usan cookies, habilitar [SameSite, HttpOnly, Secure] y token CSRF: [PHP.SecurityUtils::generateCSRFToken()](utils/security.php:55), [PHP.SecurityUtils::validateCSRFToken()](utils/security.php:70).

---

3. Autorización (RBAC + Tenant)

3.1 Roles y permisos
- Roles: student, teacher, admin, super_admin (ver users.role en [database/schema_saas.sql](database/schema_saas.sql))
- Resolver permisos con [PHP.PermissionsManager::hasPermission()](permissions_utils.php:8) y exigirlos con [PHP.PermissionsManager::requirePermission()](permissions_utils.php:60)
- Ejemplos de permisos: view_grades, create_grades, modify_grades, manage_users, view_all_reports.

3.2 Aislamiento multi-tenant
- Validación obligatoria por request con [PHP.TenantManager::validateTenantRequest()](utils/tenant.php:65)
- Comprobaciones auxiliares: [PHP.TenantManager::getUserOrganization()](utils/tenant.php:12), [PHP.TenantManager::checkPlanLimits()](utils/tenant.php:40)
- En consultas SQL, filtrar siempre por organization_id del usuario autenticado.

3.3 Ejemplos en rutas
- Calificaciones: [PHP.getGrades()](api/routes/grades.php:32) aplica validateTenantRequest antes de consultar.
- Tareas: [PHP.getTasks()](api/routes/tasks.php:30) valida tenant y diferencia vistas por rol.
- Organizaciones: [PHP.getOrganizations()](api/routes/organizations.php:32) exige super_admin.
- Reportes: [PHP.getStudentReport()](api/routes/reports.php:34) segmenta por organización.

3.4 Endurecimiento recomendado
- Añadir caché de permisos por rol con invalidación.
- Añadir pruebas automáticas de autorización por endpoint (negative testing).

---

4. Protección de datos sensibles

4.1 Clasificación
- Datos personales: users.full_name, email, grade_level.
- Datos sensibles: mensajes del chat, notas de bienestar, alertas de emergencia.

4.2 Cifrado en reposo (recomendado)
- Cifrar columnas sensibles (p.ej., chat_messages.response, chat_messages.message, wellness_entries.notes) con AES-256-GCM.
- Gestión de claves: KMS o variables de entorno con rotación y versionado (KID).

4.3 Ejemplo (PHP openssl, conceptual)
```php
$key = hex2bin($_ENV['DATA_KEY_HEX']);
$iv = random_bytes(12);
$ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
// almacenar base64(iv) + base64(tag) + base64(ciphertext)
```

4.4 Minimización y retención
- Retener solo lo necesario para operación/legales.
- Eliminar/redactar PII en logs; no loggear payloads completos.

---

5. Transporte y capa HTTP

5.1 TLS
- HTTPS obligatorio en producción (Apache). Habilitar HSTS (preload si aplica) y TLS 1.2+.

5.2 CORS
- Control actual en [api/routes/auth.php](api/routes/auth.php): allowlist + fallback (* en desarrollo).
- Recomendación: lista blanca por entorno (ENV) sin wildcard en producción; incluir sólo headers necesarios.

5.3 Encabezados de seguridad
- Ya presentes: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection en varias rutas (p.ej., [api/routes/tasks.php](api/routes/tasks.php), [api/routes/chat.php](api/routes/chat.php)).
- Añadir Content-Security-Policy (CSP) y Referrer-Policy, Permissions-Policy. Ejemplo:
```php
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; connect-src 'self' https://*.googleapis.com https://api.openai.com; object-src 'none'; frame-ancestors 'none'");
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=()');
```

5.4 Tamaño de payload
- Límites implementados: chat 50KB [api/routes/chat.php](api/routes/chat.php:22), tasks 10KB [api/routes/tasks.php](api/routes/tasks.php:108).
- Recomendación: parametrizar límites por endpoint en config/env.

---

6. Validación y saneamiento

- Entradas sanitizadas con [PHP.SecurityUtils::sanitizeInput()](utils/security.php:12).
- Nota: FILTER_SANITIZE_STRING está deprecado en PHP 8.1; planificar migración a filtros/validaciones específicas (ctype, regex, FILTER_VALIDATE_*, libs).
- Validar tipos y rangos; usar prepared statements (PDO) en todas las consultas.
- Output encoding en UI antes de inyectar HTML (ej.: [PHP.getLocalResponse()](api/routes/chat.php:197) usa htmlspecialchars).

---

7. Rate limiting y anti-abuso

- Rate limit básico por identificador en [PHP.SecurityUtils::checkRateLimit()](utils/security.php:81).
- Recomendación: mover a Redis/Memory store para entornos multi-instancia; usar ventanas deslizantes (token bucket/leaky bucket).
- Registrar eventos de abuso con [PHP.SecurityUtils::logSecurityEvent()](utils/security.php:108) y bloquear temporalmente por IP/usuario.

---

8. Logging, auditoría y alertas

- Seguridad: [PHP.SecurityUtils::logSecurityEvent()](utils/security.php:108) (error_log).
- Alertas de emergencia: detección en [PHP.checkEmergencyKeywords()](api/routes/chat.php:48) y notificación con [PHP.sendAdminAlert()](api/routes/chat.php:220).
- Recomendación: logs estructurados (JSON) con correlación (traceId), niveles, y almacenamiento centralizado (ELK/CloudWatch).
- Auditoría: registrar acciones admin (crear org, permisos, borrados) con usuario/tenant/fecha/IP.
- Privacidad: no incluir PII sensible ni tokens en logs; aplicar redacción.

---

9. Gestión de secretos y configuración

- Mantener secretos en variables de entorno (.env/secret manager). Ejemplos: DB_PASS, JWT_SECRET, DATA_KEY_HEX, GEMINI_API_KEY, ADMIN_EMAIL.
- Rotación programada; evitar hardcode en repositorio.
- Separación por entorno (dev/stage/prod) y principio de mínimo privilegio en credenciales.

---

10. Copias de seguridad, retención y cumplimiento

- Backups cifrados (en reposo y en tránsito). Probar restauraciones periódicas.
- Retención acorde a política local (Habeas Data – Colombia, Ley 1581 de 2012).
- Respuestas a titulares: acceso, rectificación, supresión mediante solicitud autenticada.

---

11. Gestión de incidentes

- Flujo de crisis ya soportado: clasificación de severidad (low/medium/high) en chat y creación de [admin_alerts] (ver [api/routes/chat.php](api/routes/chat.php)).
- Acciones recomendadas: runbooks por severidad, on-call, canales de comunicación y plazos de respuesta.

---

12. Plan de endurecimiento (roadmap)

- Autenticación: JWT firmado + refresh tokens, revocación por jti, rotación de claves (kid).
- Autorización: políticas granulares por endpoint y pruebas automáticas (deny-by-default).
- Cifrado: AES-256-GCM en columnas sensibles con rotación de claves y KMS.
- HTTP: CSP estricta, HSTS preload, sin wildcard CORS en prod.
- Observabilidad: trazas distribuidas, métricas de seguridad (tasa 401/403, abuse rate).
- Seguridad de dependencias: escaneo SCA y actualizaciones regulares.

---

13. Checklist de verificación (operativa)

- [ ] TLS vigente y HSTS activo
- [ ] CORS por allowlist de dominios
- [ ] Token firmado y expiración corta
- [ ] Tenant validado en todos los endpoints
- [ ] Permisos verificados antes de operaciones de escritura
- [ ] Límites de tamaño por endpoint
- [ ] Logs estructurados sin PII sensible
- [ ] Backups cifrados y restauraciones probadas
- [ ] Claves en env con rotación
- [ ] CSP y headers de seguridad activos

---

Referencias rápidas a código

- Auth: [api/routes/auth.php](api/routes/auth.php)
- Tenant: [PHP.TenantManager](utils/tenant.php:7)
- Permisos: [PHP.PermissionsManager](permissions_utils.php:6)
- Seguridad: [PHP.SecurityUtils](utils/security.php:7)
- Chat: [api/routes/chat.php](api/routes/chat.php)
- Tasks: [api/routes/tasks.php](api/routes/tasks.php)
- Reports: [api/routes/reports.php](api/routes/reports.php)
- Subjects: [api/routes/subjects.php](api/routes/subjects.php)
- Grades: [api/routes/grades.php](api/routes/grades.php)
- Esquema BD: [database/schema_saas.sql](database/schema_saas.sql)

Proyecto: StudyMate SaaS