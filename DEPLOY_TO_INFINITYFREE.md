# Instrucciones para Actualizar en InfinityFree

## Pasos para Subir las Correcciones:

### 1. Preparar Archivos
```bash
# Reemplazar config.php con la versión de InfinityFree
copy config_infinityfree.php config.php
```

### 2. Archivos a Subir (Modificados)
- ✅ `config.php` (con credenciales de InfinityFree)
- ✅ `dashboard_api.php` (consultas SQL corregidas)
- ✅ `tasks_api.php` (dependencia eliminada)

### 3. Scripts de Corrección a Subir
- ✅ `fix_coordinator.php`
- ✅ `fix_student_grades_table.php`
- ✅ `fix_database_complete.php`

### 4. Ejecutar en InfinityFree (en orden)
1. `https://studymate.gt.tc/fix_student_grades_table.php`
2. `https://studymate.gt.tc/fix_database_complete.php`
3. `https://studymate.gt.tc/fix_coordinator.php`

### 5. Verificar Funcionamiento
- Login con coordinador: `coordinador@demo.studymate.com` / `123456`
- Verificar que no hay errores 500 en:
  - `dashboard_api.php?action=student_stats&user_id=53`
  - `tasks_api.php?action=get_student_tasks&student_id=53`

### 6. Limpiar (Opcional)
Eliminar scripts de corrección después de ejecutar:
- `fix_coordinator.php`
- `fix_student_grades_table.php`
- `fix_database_complete.php`

## Resumen de Correcciones Aplicadas:
- ✅ Credenciales de coordinador creadas
- ✅ Tablas faltantes creadas
- ✅ Columnas faltantes agregadas
- ✅ Consultas SQL corregidas
- ✅ Dependencias eliminadas