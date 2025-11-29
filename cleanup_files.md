# LIMPIEZA DE ARCHIVOS - STUDYMATE SAAS

## 🗑️ ARCHIVOS PARA ELIMINAR (Obsoletos/Debug):

### Archivos de Debug/Testing:
- `debug_teacher_login.php`
- `fix_teacher_credentials.php` 
- `test_credentials.php`
- `fix_secretary_password.php`
- `generate_hash.php`
- `test_login.php`
- `fix_all_passwords.php`

### Archivos de Migración Obsoletos:
- `database/agregar_secretaria.sql`
- `database/corregir_nombres.sql`
- `database/corregir_usuarios.sql`
- `database/recrear_usuarios.sql`
- `database/usuarios_correctos.sql`
- `database/usuarios_hash_correcto.sql`
- `database/fix_secretary_credentials.sql`
- `database/fix_all_users.sql`

### Archivos de Reporte:
- `test_buttons_report.md`
- `cleanup_files.md` (este mismo)

## ✅ ARCHIVOS CORE A MANTENER:

### Base de Datos:
- `database/schema_saas.sql` ✅
- `database/academic_system_migration.sql` ✅
- `database/tasks_system.sql` ✅

### APIs Principales:
- `config.php` ✅
- `login_api.php` ✅
- `organizations_api.php` ✅
- `users_crud_api.php` ✅
- `tasks_api.php` ✅
- `psychological_support_api.php` ✅
- `morosity_api.php` ✅

### Dashboards:
- `login.html` ✅
- `super_admin_dashboard.html` ✅
- `admin_dashboard.html` ✅
- `teacher_dashboard.html` ✅
- `student_portal.html` ✅
- `parent_portal.html` ✅

### Archivos de Soporte:
- `notifications.js` ✅
- `communication.js` ✅

## 📊 TOTAL ARCHIVOS:
- **A Eliminar:** 15 archivos
- **A Mantener:** 18 archivos core
- **Reducción:** ~45% menos archivos