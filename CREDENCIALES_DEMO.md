# Credenciales Demo - StudyMate SaaS

## Usuarios de Prueba
**Contraseña para todos los usuarios: `123456`**

### Super Administrador
- **Usuario:** `superadmin`
- **Email:** `superadmin@studymate.com`
- **Rol:** Super Admin
- **Panel:** Super Admin Dashboard

### Administrador de Organización
- **Usuario:** `admin`
- **Email:** `admin@studymate.com`
- **Rol:** Admin
- **Panel:** Admin Dashboard

### Profesores
1. **Profesor 1:**
   - **Usuario:** `teacher1`
   - **Email:** `teacher1@demo.studymate.com`
   - **Nombre:** María García
   - **Panel:** Teacher Dashboard

2. **Profesor 2:**
   - **Usuario:** `teacher2`
   - **Email:** `teacher2@demo.studymate.com`
   - **Nombre:** Carlos López
   - **Panel:** Teacher Dashboard

### Estudiantes
1. **Estudiante 1:**
   - **Usuario:** `student1`
   - **Email:** `student1@demo.studymate.com`
   - **Nombre:** Ana Rodríguez
   - **Grado:** 6°
   - **Panel:** Student Portal

2. **Estudiante 2:**
   - **Usuario:** `student2`
   - **Email:** `student2@demo.studymate.com`
   - **Nombre:** Juan Pérez
   - **Grado:** 7°
   - **Panel:** Student Portal

3. **Estudiante 3:**
   - **Usuario:** `student3`
   - **Email:** `student3@demo.studymate.com`
   - **Nombre:** Laura Martínez
   - **Grado:** 8°
   - **Panel:** Student Portal

## Instrucciones de Instalación

1. Ejecutar el archivo SQL principal:
   ```sql
   SOURCE c:\xampp\htdocs\StudyMateSaaS\database\schema_saas.sql;
   ```

2. Ejecutar el archivo de usuarios demo:
   ```sql
   SOURCE c:\xampp\htdocs\StudyMateSaaS\database\demo_users.sql;
   ```

3. Ejecutar el archivo de sistema académico:
   ```sql
   SOURCE c:\xampp\htdocs\StudyMateSaaS\database\academic_system_migration.sql;
   ```

## Acceso al Sistema
- **URL:** `http://localhost/StudyMateSaaS/login.html`
- Usar cualquiera de las credenciales de arriba
- El sistema redirigirá automáticamente al panel correspondiente según el rol