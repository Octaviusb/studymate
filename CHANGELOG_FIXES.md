# Registro de Cambios - Correcciones para InfinityFree

## Fecha: $(date)

### Archivos Modificados para Corregir Errores 500

#### 1. `config.php`
- **Cambio:** Configuración de base de datos cambiada de InfinityFree a XAMPP local
- **Líneas modificadas:** 33-36
- **Antes:**
```php
define('DB_HOST', 'sql309.infinityfree.com');
define('DB_NAME', 'if0_39306959_studymate_saas');
define('DB_USER', 'if0_39306959');
define('DB_PASS', 'Eneroctavio19');
```
- **Después:**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'studymate_saas');
define('DB_USER', 'root');
define('DB_PASS', '');
```

#### 2. `tasks_api.php`
- **Cambio:** Eliminada dependencia de `performance_middleware.php`
- **Líneas eliminadas:** 2-4
- **Código eliminado:**
```php
require_once 'performance_middleware.php';
$startTime = startPerformanceTimer();
```

#### 3. `dashboard_api.php`
- **Cambio:** Actualizada consulta SQL para usar columnas correctas de student_grades
- **Problema:** La tabla usa `score` en lugar de `numeric_grade`
- **Solución:** Usar `COALESCE(sg.numeric_grade, sg.score)` en todas las consultas
- **Consultas modificadas:**
  - Promedio actual del estudiante
  - Rendimiento promedio del profesor
  - Promedio familiar de padres
  - Rendimiento promedio del coordinador

#### 4. Archivos Creados (Scripts de Corrección)
- `fix_coordinator.php` - Crear/actualizar credenciales del coordinador
- `fix_student_grades_table.php` - Agregar columna numeric_grade
- `fix_database_complete.php` - Crear tablas faltantes
- `check_users.php` - Verificar usuarios en BD

### Cambios de Base de Datos Necesarios

#### Tablas Creadas/Modificadas:
1. **student_grades** - Agregada columna `numeric_grade DECIMAL(4,2)`
2. **tasks** - Tabla completa creada
3. **task_submissions** - Tabla completa creada
4. **academic_grades** - Tabla completa creada
5. **parent_student_relations** - Tabla completa creada
6. **subjects** - Datos básicos insertados

#### Usuario Coordinador Creado:
- Email: `coordinador@demo.studymate.com`
- Contraseña: `123456`
- Rol: `coordinator`

### Para Actualizar en InfinityFree:

1. **Revertir config.php:**
```php
define('DB_HOST', 'sql309.infinityfree.com');
define('DB_NAME', 'if0_39306959_studymate_saas');
define('DB_USER', 'if0_39306959');
define('DB_PASS', 'Eneroctavio19');
```

2. **Subir archivos modificados:**
   - `dashboard_api.php`
   - `tasks_api.php`

3. **Ejecutar en InfinityFree:**
   - `fix_coordinator.php`
   - `fix_student_grades_table.php`
   - `fix_database_complete.php`

### Errores Corregidos:
- ✅ Error 500 en `dashboard_api.php?action=student_stats`
- ✅ Error 500 en `tasks_api.php?action=get_student_tasks`
- ✅ Credenciales inválidas del coordinador
- ✅ Tablas faltantes en base de datos
- ✅ Columnas faltantes en student_grades