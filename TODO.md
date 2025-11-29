# TODO: Implementar mejoras basadas en evaluación externa

## 1. Importación masiva de datos
- [ ] Extender import_api.php para importar asignaturas (subjects)
- [ ] Extender import_api.php para importar grados académicos (academic_grades)
- [ ] Extender import_api.php para importar logros/indicadores (achievements)
- [ ] Extender import_api.php para importar calificaciones (student_grades)
- [ ] Mejorar manejo de errores y validaciones en importaciones

## 2. Reportes académicos esenciales
- [ ] Actualizar attendance_report en reports_api.php para usar datos reales de la tabla attendance
- [ ] Agregar reporte de rendimiento por docente (teacher_performance_report)
- [ ] Agregar reporte de promedios por clase/grado (class_averages_report)
- [ ] Agregar reporte de estudiantes en riesgo (at_risk_students_report)
- [ ] Agregar reporte de cumplimiento de tareas (task_completion_report)

## 3. Sistema de backup automático
- [ ] Crear script auto_backup.php para backups automáticos
- [ ] Configurar limpieza automática de backups antiguos (mantener últimos 30 días)
- [ ] Agregar notificación por email opcional para backups exitosos/fallidos
- [ ] Crear guía de configuración de cron job para infinityfree

## 4. Testing y validación
- [ ] Probar todas las nuevas funcionalidades
- [ ] Verificar compatibilidad con infinityfree
- [ ] Actualizar documentación si es necesario
