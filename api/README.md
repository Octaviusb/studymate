# StudyMate API Documentation

## Endpoints Disponibles

### Autenticación

#### POST /api/auth/login
Iniciar sesión de usuario
```json
{
  "email": "usuario@email.com",
  "password": "contraseña"
}
```

#### POST /api/auth/register
Registrar nuevo usuario
```json
{
  "name": "Nombre Completo",
  "email": "usuario@email.com",
  "password": "contraseña",
  "role": "student|teacher",
  "grade": "11°",
  "school": "Nombre del Colegio"
}
```

### Chat Inteligente

#### POST /api/chat
Enviar mensaje al chat de IA
```json
{
  "message": "Hola, necesito ayuda con matemáticas",
  "sessionId": "session_123",
  "userId": 1
}
```

### Gestión de Tareas

#### GET /api/tasks?user_id=1
Obtener tareas del usuario

#### POST /api/tasks
Crear nueva tarea
```json
{
  "title": "Estudiar para examen",
  "subject": "Matemáticas",
  "description": "Repasar capítulos 1-5",
  "dueDate": "2024-01-15",
  "priority": "high",
  "user_id": 1
}
```

#### PUT /api/tasks/{id}
Actualizar tarea existente

#### DELETE /api/tasks/{id}
Eliminar tarea

### Bienestar Emocional

#### GET /api/wellness?user_id=1
Obtener entradas de bienestar

#### POST /api/wellness
Registrar estado de ánimo
```json
{
  "mood": "good",
  "notes": "Me siento bien hoy",
  "user_id": 1
}
```

## Códigos de Respuesta

- 200: Éxito
- 400: Datos inválidos
- 401: No autorizado
- 404: Endpoint no encontrado
- 413: Payload demasiado grande
- 500: Error interno del servidor

## Seguridad

- Todas las entradas son sanitizadas
- Rate limiting implementado
- Headers de seguridad configurados
- Validación de tipos de datos
- Logs de seguridad activados