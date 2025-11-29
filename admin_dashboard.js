
let currentUser = null;

document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    loadDashboard();
    loadRealStats();
    setupSearch();
    
    // Configurar lazy loading para secciones grandes
    document.querySelectorAll('.subsection').forEach(section => {
        if (section.querySelector('table')) {
            section.setAttribute('data-lazy-content', 'table-data');
        }
    });
});

function checkAuth() {
    const user = JSON.parse(localStorage.getItem('studymate_user') || '{}');
    const allowed = ['super_admin', 'admin', 'coordinator', 'secretary'];
    if (!user.id || !allowed.includes(user.role)) {
        alert('Acceso denegado. Solo perfiles autorizados pueden acceder.');
        window.location.href = '/';
        return;
    }
    currentUser = user;
    document.getElementById('user-info').textContent = `${user.name} (${user.role})`;
}

function showSection(section, ev) {
    document.querySelectorAll('[id$="-section"]').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));

    document.getElementById(section + '-section').classList.add('active');

    const target = ev && ev.currentTarget ? ev.currentTarget : (ev && ev.target ? ev.target : null);
    if (target && target.classList) {
        target.classList.add('active');
    } else {
        const btns = Array.from(document.querySelectorAll('.nav-tab'));
        const btn = btns.find(b => (b.getAttribute('onclick') || '').includes(`showSection('${section}'`));
        if (btn) btn.classList.add('active');
    }

    if (section === 'morosity') {
        loadOverdueStudents();
    } else if (section === 'students') {
        loadInstitutionStudents();
    } else if (section === 'teachers') {
        loadInstitutionTeachers();
    } else if (section === 'wellness') {
        loadWellnessData();
    }
}

async function loadDashboard() {
    document.getElementById('total-students').textContent = '45';
    document.getElementById('total-teachers').textContent = '12';
    document.getElementById('overdue-count').textContent = '3';
    document.getElementById('total-debt').textContent = '$450,000';
}

async function loadRealStats() {
    try {
        const response = await fetch(`dashboard_api.php?action=admin_stats&user_id=${currentUser.id}`);
        const data = await response.json();
        
        if (response.ok) {
            document.getElementById('total-students').textContent = data.total_students || 0;
            document.getElementById('total-teachers').textContent = data.total_teachers || 0;
            document.getElementById('overdue-count').textContent = data.overdue_count || 0;
            document.getElementById('total-debt').textContent = '$' + (data.total_debt || 0).toLocaleString();
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

async function loadOverdueStudents() {
    try {
        const response = await fetch(`morosity_api.php?action=overdue_students&user_id=${currentUser.id}`);
        const data = await response.json();
        
        if (response.ok) {
            displayOverdueStudents(data.overdue_students || []);
        } else {
            showAlert('Error cargando estudiantes en mora: ' + data.message, 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

function displayOverdueStudents(students) {
    const tbody = document.getElementById('overdue-table');
    if (students.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">No hay estudiantes en mora</td></tr>';
        return;
    }
    
    tbody.innerHTML = students.map(student => `
        <tr class="overdue">
            <td><strong>${student.full_name}</strong><br><small>${student.email}</small></td>
            <td>${student.overdue_invoices}</td>
            <td>$${parseFloat(student.total_debt).toLocaleString()}</td>
            <td>${Math.floor((new Date() - new Date(student.oldest_due_date)) / (1000 * 60 * 60 * 24))} días</td>
            <td><span class="chip chip-warning">PROCESO LEGAL</span></td>
            <td>
                <button class="btn btn-warning" onclick="sendNotification(${student.id})">Notificar</button>
                <button class="btn btn-success" onclick="createAgreement(${student.id})">Acuerdo</button>
            </td>
        </tr>
    `).join('');
}

async function sendLegalNotification() {
    const studentId = document.getElementById('notification-student').value;
    const notificationType = document.getElementById('notification-type').value;
    
    if (!studentId) {
        showAlert('Seleccione un estudiante', 'error');
        return;
    }
    
    try {
        const response = await fetch(`morosity_api.php?action=send_notification&user_id=${currentUser.id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_id: studentId,
                invoice_id: 1,
                notification_type: notificationType
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showAlert(`Notificación enviada. Debido proceso hasta: ${data.due_process_date}`, 'success');
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

let currentStudentsPage = 1;
const studentsPerPage = 10;

async function loadInstitutionStudents(page = 1) {
    const loading = UIComponents.showLoading('Cargando estudiantes...');
    
    try {
        const response = await fetch(`pagination_api.php?action=get_users_paginated&organization_id=${currentUser.organization_id}&role=student&page=${page}&limit=${studentsPerPage}`);
        const data = await response.json();

        UIComponents.hideLoading();

        if (response.ok && data.success) {
            currentStudentsPage = page;
            const tbody = document.getElementById('students-table');
            tbody.innerHTML = data.data.map(student => `
                <tr>
                    <td><input type="checkbox" class="student-checkbox" value="${student.id}"></td>
                    <td><strong>${student.full_name}</strong><br><small>${student.email}</small></td>
                    <td>${student.username}</td>
                    <td>${student.grade_level || 'N/A'}</td>
                    <td><span class="text-success">${student.status}</span></td>
                    <td><span class="text-success">Al día</span></td>
                    <td>
                        <button class="btn" onclick="viewStudent(${student.id})">Ver</button>
                        <button class="btn btn-warning" onclick="editStudent(${student.id})">Editar</button>
                        <button class="btn btn-danger" onclick="deleteStudent(${student.id})">Eliminar</button>
                    </td>
                </tr>
            `).join('');
            
            // Agregar paginación
            const paginationContainer = document.getElementById('students-pagination') || 
                (() => {
                    const container = document.createElement('div');
                    container.id = 'students-pagination';
                    document.getElementById('students-table').parentNode.appendChild(container);
                    return container;
                })();
            
            if (data.pagination.total_pages > 1) {
                const pagination = UIComponents.createPagination(
                    data.pagination.total,
                    data.pagination.per_page,
                    data.pagination.current_page,
                    'loadInstitutionStudents'
                );
                paginationContainer.innerHTML = '';
                paginationContainer.appendChild(pagination);
            }
            
        } else {
            UIComponents.showToast('Error cargando estudiantes: ' + data.message, 'error');
        }
    } catch (error) {
        UIComponents.hideLoading();
        UIComponents.showToast('Error de conexión al cargar estudiantes', 'error');
    }
}

async function loadInstitutionTeachers() {
    try {
        const response = await fetch(`users_crud_api.php?action=get_users&organization_id=${currentUser.organization_id}&role=teacher`);
        const data = await response.json();

        if (response.ok && data.success) {
            const tbody = document.getElementById('teachers-table');
            if (data.users && data.users.length > 0) {
                tbody.innerHTML = data.users.map(teacher => `
                    <tr>
                        <td><strong>${teacher.full_name}</strong><br><small>${teacher.email}</small></td>
                        <td>${teacher.username}</td>
                        <td>${teacher.username ? teacher.username.charAt(0).toUpperCase() + teacher.username.slice(1) : 'Sin asignar'}</td>
                        <td>25</td>
                        <td>Lun-Vie 8:00-12:00</td>
                        <td><span class="text-success">${teacher.status}</span></td>
                        <td>
                            <button class="btn" onclick="viewTeacher(${teacher.id})">Ver</button>
                            <button class="btn btn-warning" onclick="editTeacher(${teacher.id})">Editar</button>
                            <button class="btn btn-danger" onclick="deleteTeacher(${teacher.id})">Eliminar</button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="7">No hay docentes registrados</td></tr>';
            }
        } else {
            document.getElementById('teachers-table').innerHTML = '<tr><td colspan="7">Error cargando docentes</td></tr>';
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
        document.getElementById('teachers-table').innerHTML = '<tr><td colspan="7">Error de conexión</td></tr>';
    }
}

function showAlert(message, type) {
    // Usar el nuevo sistema de toast
    UIComponents.showToast(message, type);
}

// Buscador en tiempo real
function setupSearch() {
    const searchInput = document.getElementById('student-search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchStudents(e.target.value);
            }, 500);
        });
    }
}

async function searchStudents(query) {
    if (query.length < 2) {
        loadInstitutionStudents();
        return;
    }
    
    try {
        const response = await fetch(`pagination_api.php?action=get_users_paginated&organization_id=${currentUser.organization_id}&role=student&search=${encodeURIComponent(query)}&limit=20`);
        const responseData = await response.json();

        if (response.ok && responseData.success) {
            const tbody = document.getElementById('students-table');
            tbody.innerHTML = responseData.data.map(student => `
                <tr>
                    <td><input type="checkbox" class="student-checkbox" value="${student.id}"></td>
                    <td><strong>${student.full_name}</strong><br><small>${student.email}</small></td>
                    <td>${student.username}</td>
                    <td>${student.grade_level || 'N/A'}</td>
                    <td><span class="text-success">${student.status}</span></td>
                    <td><span class="text-success">Al día</span></td>
                    <td>
                        <button class="btn" onclick="viewStudent(${student.id})">Ver</button>
                        <button class="btn btn-warning" onclick="editStudent(${student.id})">Editar</button>
                        <button class="btn btn-danger" onclick="deleteStudent(${student.id})">Eliminar</button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        UIComponents.showToast('Error en búsqueda', 'error');
    }
}

function logout() {
    localStorage.removeItem('studymate_user');
    localStorage.removeItem('studymate_token');
    window.location.href = '/';
}

// Funciones administrativas
function addNewStudent() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-sm">
            <h3>Crear Nuevo Estudiante</h3>
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" id="student-name" placeholder="Ej: Juan Pérez García">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="student-email" placeholder="juan.perez@estudiante.com">
            </div>
            <div class="form-group">
                <label>Grado</label>
                <select id="student-grade">
                    <option value="6°">6°</option>
                    <option value="7°">7°</option>
                    <option value="8°">8°</option>
                    <option value="9°">9°</option>
                    <option value="10°">10°</option>
                    <option value="11°">11°</option>
                </select>
            </div>
            <div class="modal-actions">
                <button onclick="createStudent()" class="btn btn-success">Crear Estudiante</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function createStudent() {
    const name = document.getElementById('student-name').value;
    const email = document.getElementById('student-email').value;
    const grade = document.getElementById('student-grade').value;
    
    if (!name || !email) {
        showAlert('Complete nombre y email', 'warning');
        return;
    }
    
    try {
        const response = await fetch('users_crud_api.php?action=create_user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                full_name: name,
                email: email,
                role: 'student',
                grade_level: grade
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showAlert('Estudiante creado exitosamente', 'success');
            document.querySelector('.modal-overlay')?.remove();
            loadInstitutionStudents();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

function importStudents() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-sm">
            <h3>Importar Estudiantes desde Excel/CSV</h3>
            <div class="form-group">
                <label>Archivo CSV (Formato: Nombre, Email, Grado)</label>
                <input type="file" id="csv-file" accept=".csv,.xlsx,.xls">
            </div>
            <div class="modal-actions">
                <button onclick="processImport()" class="btn btn-success">Importar</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function processImport() {
    const fileInput = document.getElementById('csv-file');
    const file = fileInput.files[0];
    
    if (!file) {
        showAlert('Seleccione un archivo', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('csv_file', file);
    formData.append('organization_id', currentUser.organization_id);
    
    try {
        const response = await fetch('import_api.php?action=import_students', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showAlert(`${data.imported} estudiantes importados exitosamente`, 'success');
            document.querySelector('.modal-overlay')?.remove();
            loadInstitutionStudents();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function generateStudentCards() {
    try {
        const response = await fetch(`reports_api.php?action=academic_report&organization_id=${currentUser.organization_id}`);
        const data = await response.json();
        
        if (response.ok && data.success) {
            showAlert('Reporte de carnés generado exitosamente', 'success');
            // Aquí se podría generar un PDF o abrir una nueva ventana
        } else {
            showAlert('Error generando reporte', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function filterStudents() {
    const grade = document.getElementById('student-grade-filter').value;
    const status = document.getElementById('student-status-filter').value;

    try {
        const response = await fetch(`pagination_api.php?action=get_users_paginated&organization_id=${currentUser.organization_id}&role=student&grade_level=${grade}&status=${status}&limit=20`);
        const data = await response.json();

        if (response.ok && data.success) {
            const tbody = document.getElementById('students-table');
            tbody.innerHTML = data.data.map(student => `
                <tr>
                    <td><input type="checkbox" class="student-checkbox" value="${student.id}"></td>
                    <td><strong>${student.full_name}</strong><br><small>${student.email}</small></td>
                    <td>${student.username}</td>
                    <td>${student.grade_level || 'N/A'}</td>
                    <td><span class="text-success">${student.status}</span></td>
                    <td><span class="text-success">Al día</span></td>
                    <td>
                        <button class="btn" onclick="viewStudent(${student.id})">Ver</button>
                        <button class="btn btn-warning" onclick="editStudent(${student.id})">Editar</button>
                        <button class="btn btn-danger" onclick="deleteStudent(${student.id})">Eliminar</button>
                    </td>
                </tr>
            `).join('');
            showAlert('Estudiantes filtrados exitosamente', 'success');
        } else {
            showAlert('Error filtrando estudiantes', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function bulkStudentActions() {
    const selectedStudents = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);

    if (selectedStudents.length === 0) {
        showAlert('Seleccione al menos un estudiante', 'warning');
        return;
    }

    const action = prompt('¿Qué acción desea realizar?\n1. Activar\n2. Suspender\n3. Eliminar\n4. Enviar notificación');

    try {
        for (const studentId of selectedStudents) {
            switch (action) {
                case '1':
                    await fetch('users_crud_api.php?action=update_user', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: studentId, status: 'active' })
                    });
                    break;
                case '2':
                    await fetch('users_crud_api.php?action=update_user', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: studentId, status: 'suspended' })
                    });
                    break;
                case '3':
                    await fetch(`users_crud_api.php?action=delete_user&id=${studentId}`, { method: 'DELETE' });
                    break;
                case '4':
                    await fetch('notifications_api.php?action=send_notification', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: currentUser.id,
                            recipients: [studentId],
                            message: 'Notificación masiva desde administración'
                        })
                    });
                    break;
            }
        }
        showAlert(`Acción aplicada a ${selectedStudents.length} estudiantes`, 'success');
        loadInstitutionStudents();
    } catch (error) {
        showAlert('Error aplicando acciones masivas', 'error');
    }
}

async function sendBulkNotifications() {
    const selectedStudents = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);

    if (selectedStudents.length === 0) {
        showAlert('Seleccione al menos un estudiante', 'warning');
        return;
    }

    const message = prompt('Mensaje a enviar:');

    if (!message) return;

    try {
        await fetch('notifications_api.php?action=send_notification', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: currentUser.id,
                recipients: selectedStudents,
                message: message
            })
        });
        showAlert(`Notificación enviada a ${selectedStudents.length} estudiantes`, 'success');
    } catch (error) {
        showAlert('Error enviando notificaciones', 'error');
    }
}

function addNewTeacher() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-sm">
            <h3>Crear Nuevo Docente</h3>
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" id="teacher-name" placeholder="Ej: María García López">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="teacher-email" placeholder="maria.garcia@colegio.com">
            </div>
            <div class="form-group">
                <label>Materia Principal</label>
                <select id="teacher-subject">
                    <option value="matematicas">Matemáticas</option>
                    <option value="espanol">Español</option>
                    <option value="ciencias">Ciencias</option>
                    <option value="sociales">Sociales</option>
                    <option value="ingles">Inglés</option>
                    <option value="educacion_fisica">Educación Física</option>
                </select>
            </div>
            <div class="modal-actions">
                <button onclick="createTeacher()" class="btn btn-success">Crear Docente</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function createTeacher() {
    const name = document.getElementById('teacher-name').value;
    const email = document.getElementById('teacher-email').value;
    const subject = document.getElementById('teacher-subject').value;
    
    if (!name || !email) {
        showAlert('Complete nombre y email', 'warning');
        return;
    }
    
    try {
        const response = await fetch('users_crud_api.php?action=create_user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                full_name: name,
                email: email,
                role: 'teacher',
                username: subject
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showAlert('Docente creado exitosamente', 'success');
            document.querySelector('.modal-overlay')?.remove();
            loadInstitutionTeachers();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function assignSubjects() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-md">
            <h3>Asignar Materias a Docentes</h3>
            <div class="form-group">
                <label>Seleccionar Docente</label>
                <select id="assign-teacher">
                    <option value="">Cargando docentes...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Materias Disponibles</label>
                <div id="subjects-list" class="scroll-box">
                    Cargando materias...
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="saveSubjectAssignment()" class="btn btn-success">Guardar Asignación</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    // Cargar docentes y materias
    try {
        const [teachersRes, subjectsRes] = await Promise.all([
            fetch(`users_crud_api.php?action=get_users&organization_id=${currentUser.organization_id}&role=teacher`),
            fetch(`academic_api.php?action=get_subjects&organization_id=${currentUser.organization_id}`)
        ]);

        const teachers = await teachersRes.json();
        const subjects = await subjectsRes.json();

        const teacherSelect = document.getElementById('assign-teacher');
        teacherSelect.innerHTML = '<option value="">Seleccionar docente</option>' +
            (teachers.users || []).map(t => `<option value="${t.id}">${t.full_name}</option>`).join('');

        const subjectsList = document.getElementById('subjects-list');
        subjectsList.innerHTML = (subjects.subjects || []).map(s =>
            `<label class="d-block"><input type="checkbox" value="${s.id}"> ${s.name}</label>`
        ).join('');
    } catch (error) {
        showAlert('Error cargando datos', 'error');
    }
}

async function saveSubjectAssignment() {
    const teacherId = document.getElementById('assign-teacher').value;
    const selectedSubjects = Array.from(document.querySelectorAll('#subjects-list input:checked')).map(cb => cb.value);

    if (!teacherId || selectedSubjects.length === 0) {
        showAlert('Seleccione docente y materias', 'warning');
        return;
    }

    try {
        await fetch('academic_api.php?action=assign_subjects', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                teacher_id: teacherId,
                subject_ids: selectedSubjects
            })
        });
        showAlert('Materias asignadas exitosamente', 'success');
        document.querySelector('.modal-overlay')?.remove();
    } catch (error) {
        showAlert('Error asignando materias', 'error');
    }
}

function teacherSchedule() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-lg">
            <h3>Horarios de Docentes</h3>
            <div id="schedule-calendar" class="calendar-placeholder">
                <p class="text-center p-20">Calendario de horarios en desarrollo...</p>
                <p class="text-center p-20">Próximamente: Vista semanal, asignación de horas, conflictos de horario</p>
            </div>
            <div class="modal-actions">
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cerrar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function teacherReports() {
    try {
        const response = await fetch(`reports_api.php?action=teacher_performance&organization_id=${currentUser.organization_id}`);
        const data = await response.json();

        if (response.ok && data.success) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-card modal-lg modal-scroll">
                    <h3>Reportes de Docentes</h3>
                    <div id="reports-content">
                        ${data.reports.map(report => `
                            <div class="box">
                                <h4>${report.teacher_name}</h4>
                                <p><strong>Materias:</strong> ${report.subjects}</p>
                                <p><strong>Estudiantes:</strong> ${report.students_count}</p>
                                <p><strong>Evaluación Promedio:</strong> ${report.avg_evaluation}/10</p>
                                <p><strong>Asistencia:</strong> ${report.attendance_rate}%</p>
                            </div>
                        `).join('')}
                    </div>
                    <div class="modal-actions">
                        <button onclick="exportTeacherReports()" class="btn btn-success">Exportar PDF</button>
                        <button onclick="this.closest('div').parentElement.remove()" class="btn">Cerrar</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            showAlert('Error generando reportes', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function generateTeacherEvaluation() {
    const period = document.getElementById('evaluation-period').value;

    try {
        const response = await fetch('academic_api.php?action=generate_evaluations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                period: period
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showAlert(`Evaluaciones generadas para ${data.evaluations_count} docentes`, 'success');
        } else {
            showAlert('Error generando evaluaciones', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function viewEvaluationResults() {
    try {
        const response = await fetch(`academic_api.php?action=get_evaluation_results&organization_id=${currentUser.organization_id}`);
        const data = await response.json();

        if (response.ok && data.success) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-card modal-lg modal-scroll">
                    <h3>Resultados de Evaluaciones Docentes</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Docente</th>
                                <th>Período</th>
                                <th>Puntuación</th>
                                <th>Comentarios</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.evaluations.map(ev => `
                                <tr>
                                    <td>${ev.teacher_name}</td>
                                    <td>${ev.period}</td>
                                    <td>${ev.score}/10</td>
                                    <td>${ev.comments || 'Sin comentarios'}</td>
                                    <td>${new Date(ev.created_at).toLocaleDateString()}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    <div class="modal-actions">
                        <button onclick="this.closest('div').parentElement.remove()" class="btn">Cerrar</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            showAlert('Error cargando resultados', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

function manageSubjects() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-md">
            <h3>Gestión de Materias</h3>
            <div class="form-group">
                <label>Nombre de la Materia</label>
                <input type="text" id="subject-name" placeholder="Ej: Matemáticas">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea id="subject-description" rows="3" placeholder="Descripción de la materia"></textarea>
            </div>
            <div class="form-group">
                <label>Grados donde se imparte</label>
                <div id="grades-checkboxes">
                    <label><input type="checkbox" value="6°"> 6°</label>
                    <label><input type="checkbox" value="7°"> 7°</label>
                    <label><input type="checkbox" value="8°"> 8°</label>
                    <label><input type="checkbox" value="9°"> 9°</label>
                    <label><input type="checkbox" value="10°"> 10°</label>
                    <label><input type="checkbox" value="11°"> 11°</label>
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="createSubject()" class="btn btn-success">Crear Materia</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function createSubject() {
    const name = document.getElementById('subject-name').value;
    const description = document.getElementById('subject-description').value;
    const selectedGrades = Array.from(document.querySelectorAll('#grades-checkboxes input:checked')).map(cb => cb.value);

    if (!name || selectedGrades.length === 0) {
        showAlert('Complete nombre y grados', 'warning');
        return;
    }

    try {
        await fetch('academic_api.php?action=create_subject', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                name: name,
                description: description,
                grades: selectedGrades
            })
        });
        showAlert('Materia creada exitosamente', 'success');
        document.querySelector('.modal-overlay')?.remove();
    } catch (error) {
        showAlert('Error creando materia', 'error');
    }
}

function manageGrades() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-md">
            <h3>Gestión de Grados Académicos</h3>
            <div class="form-group">
                <label>Nombre del Grado</label>
                <input type="text" id="grade-name" placeholder="Ej: 6°">
            </div>
            <div class="form-group">
                <label>Nivel Educativo</label>
                <select id="grade-level">
                    <option value="primaria">Primaria</option>
                    <option value="secundaria">Secundaria</option>
                    <option value="bachillerato">Bachillerato</option>
                </select>
            </div>
            <div class="form-group">
                <label>Capacidad Máxima</label>
                <input type="number" id="grade-capacity" placeholder="30" min="1">
            </div>
            <div class="modal-actions">
                <button onclick="createGrade()" class="btn btn-success">Crear Grado</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function createGrade() {
    const name = document.getElementById('grade-name').value;
    const level = document.getElementById('grade-level').value;
    const capacity = document.getElementById('grade-capacity').value;

    if (!name || !level || !capacity) {
        showAlert('Complete todos los campos', 'warning');
        return;
    }

    try {
        await fetch('academic_api.php?action=create_grade', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                name: name,
                level: level,
                capacity: parseInt(capacity)
            })
        });
        showAlert('Grado creado exitosamente', 'success');
        document.querySelector('.modal-overlay')?.remove();
    } catch (error) {
        showAlert('Error creando grado', 'error');
    }
}

function academicCalendar() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-lg">
            <h3>Calendario Académico</h3>
            <div id="calendar-content">
                <h4>Eventos Académicos 2024</h4>
                <div class="box">
                    <strong>Inicio de Clases:</strong> 01 de febrero de 2024
                </div>
                <div class="box">
                    <strong>Fin Primer Bimestre:</strong> 30 de abril de 2024
                </div>
                <div class="box">
                    <strong>Receso Escolar:</strong> 01 al 15 de julio de 2024
                </div>
                <div class="box">
                    <strong>Fin de Año Lectivo:</strong> 15 de diciembre de 2024
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="addCalendarEvent()" class="btn btn-success">Agregar Evento</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cerrar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function addCalendarEvent() {
    const eventName = prompt('Nombre del evento:');
    const eventDate = prompt('Fecha (YYYY-MM-DD):');

    if (eventName && eventDate) {
        const calendarContent = document.getElementById('calendar-content');
        calendarContent.innerHTML += `
            <div class="box">
                <strong>${eventName}:</strong> ${new Date(eventDate).toLocaleDateString()}
            </div>
        `;
        showAlert('Evento agregado al calendario', 'success');
    }
}

function gradingSystem() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-md">
            <h3>Sistema de Calificación</h3>
            <div class="form-group">
                <label>Escala de Calificación</label>
                <select id="grading-scale">
                    <option value="1-10">1-10 (Sistema Colombiano)</option>
                    <option value="1-5">1-5 (Sistema Básico)</option>
                    <option value="A-F">A-F (Sistema Americano)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Porcentaje de Aprobación</label>
                <input type="number" id="passing-percentage" value="60" min="0" max="100">
            </div>
            <div class="form-group">
                <label>Peso de Evaluaciones (%)</label>
                <div class="grid-2">
                    <div>
                        <label>Trabajos</label>
                        <input type="number" id="homework-weight" value="30" min="0" max="100">
                    </div>
                    <div>
                        <label>Exámenes</label>
                        <input type="number" id="exam-weight" value="40" min="0" max="100">
                    </div>
                    <div>
                        <label>Participación</label>
                        <input type="number" id="participation-weight" value="20" min="0" max="100">
                    </div>
                    <div>
                        <label>Proyectos</label>
                        <input type="number" id="project-weight" value="10" min="0" max="100">
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="saveGradingSystem()" class="btn btn-success">Guardar Configuración</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function saveGradingSystem() {
    const scale = document.getElementById('grading-scale').value;
    const passing = document.getElementById('passing-percentage').value;
    const weights = {
        homework: document.getElementById('homework-weight').value,
        exam: document.getElementById('exam-weight').value,
        participation: document.getElementById('participation-weight').value,
        project: document.getElementById('project-weight').value
    };

    try {
        await fetch('academic_api.php?action=save_grading_config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                scale: scale,
                passing_percentage: passing,
                weights: weights
            })
        });
        showAlert('Sistema de calificación guardado', 'success');
        document.querySelector('.modal-overlay')?.remove();
    } catch (error) {
        showAlert('Error guardando configuración', 'error');
    }
}

function addAcademicPeriod() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-sm">
            <h3>Nuevo Período Académico</h3>
            <div class="form-group">
                <label>Nombre del Período</label>
                <input type="text" id="period-name" placeholder="Ej: Segundo Bimestre 2024">
            </div>
            <div class="form-group">
                <label>Fecha de Inicio</label>
                <input type="date" id="period-start">
            </div>
            <div class="form-group">
                <label>Fecha de Fin</label>
                <input type="date" id="period-end">
            </div>
            <div class="modal-actions">
                <button onclick="createAcademicPeriod()" class="btn btn-success">Crear Período</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function createAcademicPeriod() {
    const name = document.getElementById('period-name').value;
    const start = document.getElementById('period-start').value;
    const end = document.getElementById('period-end').value;

    if (!name || !start || !end) {
        showAlert('Complete todos los campos', 'warning');
        return;
    }

    try {
        await fetch('academic_api.php?action=create_period', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                name: name,
                start_date: start,
                end_date: end
            })
        });
        showAlert('Período académico creado', 'success');
        document.querySelector('.modal-overlay')?.remove();
    } catch (error) {
        showAlert('Error creando período', 'error');
    }
}

function editPeriod(id) {
    showAlert(`Editando período ${id}`, 'info');
    // Implementar edición de período
}

async function saveInstitutionInfo() {
    const info = {
        name: document.getElementById('institution-name').value,
        nit: document.getElementById('institution-nit').value,
        phone: document.getElementById('institution-phone').value,
        email: document.getElementById('institution-email').value,
        address: document.getElementById('institution-address').value,
        city: document.getElementById('institution-city').value
    };

    try {
        const response = await fetch('config_api.php?action=save_institution_info', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                ...info
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showAlert('Información institucional guardada exitosamente', 'success');
        } else {
            showAlert('Error guardando información', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function saveNotificationSettings() {
    const settings = {
        email_notifications: document.querySelector('input[type="checkbox"]:nth-of-type(1)').checked,
        sms_notifications: document.querySelector('input[type="checkbox"]:nth-of-type(2)').checked,
        push_notifications: document.querySelector('input[type="checkbox"]:nth-of-type(3)').checked
    };

    try {
        const response = await fetch('config_api.php?action=save_notification_settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                ...settings
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showAlert('Configuración de notificaciones guardada', 'success');
        } else {
            showAlert('Error guardando configuración', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function createBackup() {
    try {
        const response = await fetch('backup_system.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showAlert(`Backup creado: ${data.filename} (${(data.size/1024).toFixed(1)}KB)`, 'success');
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function restoreBackup() {
    if (confirm('¿Está seguro de restaurar un respaldo?')) {
        try {
            const response = await fetch('backup_system.php?action=list', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                const backupList = data.backups.map(b => `${b.filename} (${b.date})`).join('\n');
                showAlert(`Backups disponibles:\n${backupList}`, 'info');
            }
        } catch (error) {
            showAlert('Error listando backups', 'error');
        }
    }
}
function viewLogs() { showAlert('Mostrando logs del sistema', 'info'); }
function resetSystem() { if (confirm('¿Está seguro de reiniciar el sistema?')) showAlert('Reiniciando sistema institucional', 'warning'); }

async function deleteStudent(id) {
    if (confirm('¿Está seguro de eliminar este estudiante?')) {
        try {
            const response = await fetch(`users_crud_api.php?action=delete_user&id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                showAlert('Estudiante eliminado', 'success');
                loadInstitutionStudents();
            } else {
                showAlert('Error eliminando estudiante', 'error');
            }
        } catch (error) {
            showAlert('Error de conexión', 'error');
        }
    }
}

async function deleteTeacher(id) {
    if (confirm('¿Está seguro de eliminar este docente?')) {
        try {
            const response = await fetch(`users_crud_api.php?action=delete_user&id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                showAlert('Docente eliminado', 'success');
                loadInstitutionTeachers();
            } else {
                showAlert('Error eliminando docente', 'error');
            }
        } catch (error) {
            showAlert('Error de conexión', 'error');
        }
    }
}

async function editStudent(id) {
    const loading = UIComponents.showLoading('Cargando datos del estudiante...');
    
    try {
        // Simular carga de datos
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const content = `
            <form id="edit-student-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre Completo *</label>
                        <input type="text" id="edit-student-name" value="Ana García" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="edit-student-email" value="ana.garcia@demo.com" required>
                    </div>
                    <div class="form-group">
                        <label>Grado</label>
                        <select id="edit-student-grade">
                            <option value="6°">6°</option>
                            <option value="7°" selected>7°</option>
                            <option value="8°">8°</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select id="edit-student-status">
                            <option value="active" selected>Activo</option>
                            <option value="inactive">Inactivo</option>
                            <option value="suspended">Suspendido</option>
                        </select>
                    </div>
                </div>
            </form>
        `;
        
        UIComponents.hideLoading();
        
        UIComponents.createModal('Editar Estudiante', content, [
            {
                text: 'Guardar Cambios',
                class: 'btn-success',
                onclick: `saveStudentChanges(${id})`
            }
        ]);
        
    } catch (error) {
        UIComponents.hideLoading();
        UIComponents.showToast('Error cargando datos del estudiante', 'error');
    }
}

async function saveStudentChanges(id) {
    if (!UIComponents.validateForm('edit-student-form')) {
        UIComponents.showToast('Complete todos los campos requeridos', 'warning');
        return;
    }
    
    const loading = UIComponents.showLoading('Guardando cambios...');
    
    try {
        const data = {
            id: id,
            full_name: document.getElementById('edit-student-name').value,
            email: document.getElementById('edit-student-email').value,
            grade_level: document.getElementById('edit-student-grade').value,
            status: document.getElementById('edit-student-status').value
        };
        
        const response = await fetch('users_crud_api.php?action=update_user', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        UIComponents.hideLoading();
        
        if (response.ok && result.success) {
            UIComponents.showToast('Estudiante actualizado exitosamente', 'success');
            document.querySelector('.modal-overlay').remove();
            loadInstitutionStudents();
        } else {
            UIComponents.showToast('Error: ' + result.message, 'error');
        }
    } catch (error) {
        UIComponents.hideLoading();
        UIComponents.showToast('Error de conexión', 'error');
    }
}

async function editTeacher(id) {
    const loading = UIComponents.showLoading('Cargando datos del docente...');
    
    try {
        await new Promise(resolve => setTimeout(resolve, 800));
        
        const content = `
            <form id="edit-teacher-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre Completo *</label>
                        <input type="text" id="edit-teacher-name" value="María García" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="edit-teacher-email" value="matematicas@demo.com" required>
                    </div>
                    <div class="form-group">
                        <label>Materia Principal</label>
                        <select id="edit-teacher-subject">
                            <option value="matematicas" selected>Matemáticas</option>
                            <option value="espanol">Español</option>
                            <option value="ciencias">Ciencias</option>
                            <option value="sociales">Sociales</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select id="edit-teacher-status">
                            <option value="active" selected>Activo</option>
                            <option value="inactive">Inactivo</option>
                        </select>
                    </div>
                </div>
            </form>
        `;
        
        UIComponents.hideLoading();
        
        UIComponents.createModal('Editar Docente', content, [
            {
                text: 'Guardar Cambios',
                class: 'btn-success',
                onclick: `saveTeacherChanges(${id})`
            }
        ]);
        
    } catch (error) {
        UIComponents.hideLoading();
        UIComponents.showToast('Error cargando datos del docente', 'error');
    }
}

async function saveTeacherChanges(id) {
    if (!UIComponents.validateForm('edit-teacher-form')) {
        UIComponents.showToast('Complete todos los campos requeridos', 'warning');
        return;
    }
    
    const loading = UIComponents.showLoading('Guardando cambios...');
    
    try {
        const data = {
            id: id,
            full_name: document.getElementById('edit-teacher-name').value,
            email: document.getElementById('edit-teacher-email').value,
            username: document.getElementById('edit-teacher-subject').value,
            status: document.getElementById('edit-teacher-status').value,
            role: 'teacher'
        };
        
        const response = await fetch('users_crud_api.php?action=update_user', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        UIComponents.hideLoading();
        
        if (response.ok && result.success) {
            UIComponents.showToast('Docente actualizado exitosamente', 'success');
            document.querySelector('.modal-overlay').remove();
            loadInstitutionTeachers();
        } else {
            UIComponents.showToast('Error: ' + result.message, 'error');
        }
    } catch (error) {
        UIComponents.hideLoading();
        UIComponents.showToast('Error de conexión', 'error');
    }
}

function viewStudent(id) { showAlert(`Viendo perfil del estudiante ${id}`, 'info'); }
function viewTeacher(id) { showAlert(`Viendo perfil del docente ${id}`, 'info'); }

async function sendNotification(studentId) {
    try {
        const response = await fetch(`morosity_api.php?action=send_notification&user_id=${currentUser.id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_id: studentId,
                invoice_id: 1, // Simulado
                notification_type: 'first_notice'
            })
        });

        const data = await response.json();

        if (response.ok) {
            showAlert(`Notificación enviada. Debido proceso hasta: ${data.due_process_date}`, 'success');
        } else {
            showAlert('Error enviando notificación: ' + data.message, 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function createAgreement(studentId) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-sm">
            <h3>Crear Acuerdo de Pago</h3>
            <div class="form-group">
                <label>Deuda Total ($)</label>
                <input type="number" id="agreement-debt" step="0.01" placeholder="450000">
            </div>
            <div class="form-group">
                <label>Pago Mensual ($)</label>
                <input type="number" id="agreement-monthly" step="0.01" placeholder="150000">
            </div>
            <div class="form-group">
                <label>Fecha Inicio</label>
                <input type="date" id="agreement-start">
            </div>
            <div class="modal-actions">
                <button onclick="savePaymentAgreement(${studentId})" class="btn btn-success">Crear Acuerdo</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function savePaymentAgreement(studentId) {
    const debt = document.getElementById('agreement-debt').value;
    const monthly = document.getElementById('agreement-monthly').value;
    const start = document.getElementById('agreement-start').value;

    if (!debt || !monthly || !start) {
        showAlert('Complete todos los campos', 'warning');
        return;
    }

    try {
        const response = await fetch(`morosity_api.php?action=create_payment_agreement&user_id=${currentUser.id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_id: studentId,
                total_debt: parseFloat(debt),
                monthly_payment: parseFloat(monthly),
                start_date: start,
                organization_id: currentUser.organization_id
            })
        });

        const data = await response.json();

        if (response.ok) {
            showAlert('Acuerdo de pago creado exitosamente', 'success');
            document.querySelector('.modal-overlay')?.remove();
        } else {
            showAlert('Error creando acuerdo: ' + data.message, 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function createPaymentAgreement() {
    const studentId = document.getElementById('agreement-student').value;
    const debt = document.getElementById('agreement-debt').value;
    const monthly = document.getElementById('agreement-monthly').value;
    const start = document.getElementById('agreement-start').value;

    if (!studentId || !debt || !monthly || !start) {
        showAlert('Complete todos los campos', 'warning');
        return;
    }

    try {
        const response = await fetch(`morosity_api.php?action=create_payment_agreement&user_id=${currentUser.id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_id: studentId,
                total_debt: parseFloat(debt),
                monthly_payment: parseFloat(monthly),
                start_date: start,
                organization_id: currentUser.organization_id
            })
        });

        const data = await response.json();

        if (response.ok) {
            showAlert('Acuerdo de pago creado exitosamente', 'success');
            // Limpiar formulario
            document.getElementById('agreement-student').value = '';
            document.getElementById('agreement-debt').value = '';
            document.getElementById('agreement-monthly').value = '';
            document.getElementById('agreement-start').value = '';
        } else {
            showAlert('Error creando acuerdo: ' + data.message, 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

function loadWellnessData() {
    // Cargar datos de bienestar
    document.getElementById('wellness-entries').textContent = '127';
    document.getElementById('avg-mood').textContent = '7.2';
    document.getElementById('alerts-count').textContent = '3';
    document.getElementById('chat-sessions').textContent = '45';
}

// Funciones del Sistema de Acompañamiento
async function viewWellnessReport() {
    try {
        const response = await fetch(`wellness_api.php?action=get_wellness_report&organization_id=${currentUser.organization_id}`);
        const data = await response.json();

        if (response.ok && data.success) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-card modal-lg modal-scroll">
                    <h3>Reporte de Bienestar Estudiantil</h3>
                    <div class="stats-grid mb-20">
                        <div class="stat-card">
                            <div class="stat-number">${data.stats.total_entries}</div>
                            <div class="stat-label">Registros Totales</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${data.stats.avg_mood}/10</div>
                            <div class="stat-label">Estado de Ánimo Promedio</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${data.stats.active_alerts}</div>
                            <div class="stat-label">Alertas Activas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${data.stats.chat_sessions}</div>
                            <div class="stat-label">Sesiones de Chat</div>
                        </div>
                    </div>
                    <h4>Distribución por Estado de Ánimo</h4>
                    <canvas id="moodChart" width="400" height="200"></canvas>
                    <div class="modal-actions">
                        <button onclick="exportWellnessData()" class="btn btn-success">Exportar Reporte</button>
                        <button onclick="this.closest('div').parentElement.remove()" class="btn">Cerrar</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Crear gráfico simple
            setTimeout(() => {
                const ctx = document.getElementById('moodChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Muy Bajo', 'Bajo', 'Normal', 'Bueno', 'Excelente'],
                        datasets: [{
                            label: 'Estudiantes',
                            data: data.mood_distribution,
                            backgroundColor: ['#e74c3c', '#f39c12', '#f1c40f', '#27ae60', '#2ecc71']
                        }]
                    }
                });
            }, 100);
        } else {
            showAlert('Error generando reporte', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function viewAlerts() {
    try {
        const response = await fetch(`wellness_api.php?action=get_alerts&organization_id=${currentUser.organization_id}`);
        const data = await response.json();

        if (response.ok && data.success) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-card modal-lg modal-scroll">
                    <h3>Alertas de Bienestar</h3>
                    <div id="alerts-list">
                        ${data.alerts.map(alert => `
                            <div class="severity-card ${alert.severity === 'high' ? 'severity-border-high' : alert.severity === 'medium' ? 'severity-border-medium' : 'severity-border-low'}">
                                <h4>${alert.student_name}</h4>
                                <p><strong>Tipo:</strong> ${alert.alert_type}</p>
                                <p><strong>Severidad:</strong> <span class="${alert.severity === 'high' ? 'text-danger' : alert.severity === 'medium' ? 'text-warning' : 'text-success'}">${alert.severity.toUpperCase()}</span></p>
                                <p><strong>Fecha:</strong> ${new Date(alert.created_at).toLocaleDateString()}</p>
                                <p><strong>Estado:</strong> ${alert.status}</p>
                                <button onclick="reviewAlert(${alert.id})" class="btn">Revisar</button>
                                <button onclick="contactStudent(${alert.student_id})" class="btn btn-success">Contactar</button>
                            </div>
                        `).join('')}
                    </div>
                    <div class="modal-actions">
                        <button onclick="this.closest('div').parentElement.remove()" class="btn">Cerrar</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            showAlert('Error cargando alertas', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function exportWellnessData() {
    try {
        const response = await fetch(`wellness_api.php?action=export_wellness_data&organization_id=${currentUser.organization_id}`);
        const data = await response.json();

        if (response.ok && data.success) {
            // Crear archivo CSV
            const csvContent = data.csv_data;
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `reporte_bienestar_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showAlert('Datos exportados exitosamente', 'success');
        } else {
            showAlert('Error exportando datos', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}
// Acciones de botones en pestaña de Acompañamiento (revisar/contactar/escalar)
function reviewAlert(alertId) {
    try {
        const content = `
            <div class="form-group">
                <label><strong>ID de Alerta:</strong></label>
                <div>${alertId}</div>
            </div>
            <div class="form-group">
                <label><strong>Detalles:</strong></label>
                <div>Los detalles completos de la alerta se cargarán desde el sistema.</div>
            </div>
        `;
        if (typeof UIComponents !== 'undefined' && UIComponents.createModal) {
            UIComponents.createModal('Revisar Alerta', content, [
                { text: 'Cerrar', class: 'btn', onclick: "document.querySelector('.modal-overlay')?.remove()" }
            ]);
        } else {
            alert('Revisando alerta ' + alertId);
        }
    } catch (e) {
        console.error('reviewAlert error:', e);
        if (typeof UIComponents !== 'undefined' && UIComponents.showToast) {
            UIComponents.showToast('Error al abrir la alerta', 'error');
        }
    }
}

function contactStudent(studentId) {
    try {
        if (typeof UIComponents !== 'undefined' && UIComponents.showToast) {
            UIComponents.showToast(`Iniciando contacto con estudiante ${studentId}`, 'info');
        }
        if (typeof openCommunication === 'function') {
            // Intentar abrir módulo de comunicación si está disponible
            openCommunication();
        }
    } catch (e) {
        console.error('contactStudent error:', e);
        if (typeof UIComponents !== 'undefined' && UIComponents.showToast) {
            UIComponents.showToast('Error iniciando contacto', 'error');
        }
    }
}

function escalateAlert(alertId) {
    try {
        if (confirm('¿Escalar esta alerta a nivel crítico?')) {
            if (typeof UIComponents !== 'undefined' && UIComponents.showToast) {
                UIComponents.showToast(`Alerta ${alertId} escalada - Notificando a coordinación`, 'warning');
            } else {
                alert('Alerta ' + alertId + ' escalada');
            }
            // Nota: Si se requiere persistencia, integrar aquí una llamada al backend
            // fetch('wellness_api.php?action=escalate_alert', { ... })
            // Validar la ruta correcta del endpoint antes de habilitarlo.
        }
    } catch (e) {
        console.error('escalateAlert error:', e);
        if (typeof UIComponents !== 'undefined' && UIComponents.showToast) {
            UIComponents.showToast('Error al escalar la alerta', 'error');
        }
    }
}
// === Funciones faltantes migradas desde el inline del HTML (Acompañamiento) ===
function exportStudentList() {
    try {
        if (typeof UIComponents !== 'undefined' && UIComponents.showToast) {
            UIComponents.showToast('Exportación de lista próximamente', 'info');
        } else {
            alert('Exportación de lista próximamente');
        }
    } catch (e) {
        console.error('exportStudentList error:', e);
    }
}

function createSupportTask() {
    const student = document.getElementById('task-student')?.value;
    const type = document.getElementById('task-type')?.value;
    const description = document.getElementById('task-description')?.value;

    if (!student || !description) {
        showAlert('Complete los campos requeridos', 'warning');
        return;
    }

    showAlert(`Tarea de acompañamiento ${type} creada para estudiante ${student}`, 'success');

    // Limpiar formulario
    const studentEl = document.getElementById('task-student');
    const descEl = document.getElementById('task-description');
    if (studentEl) studentEl.value = '';
    if (descEl) descEl.value = '';
}

function scheduleWellnessActivity() {
    const type = document.getElementById('activity-type')?.value;
    const title = document.getElementById('activity-title')?.value;
    const datetime = document.getElementById('activity-datetime')?.value;

    if (!title || !datetime) {
        showAlert('Complete título y fecha de la actividad', 'warning');
        return;
    }

    showAlert(`Actividad "${title}" programada exitosamente`, 'success');

    // Limpiar formulario
    const titleEl = document.getElementById('activity-title');
    const dateEl = document.getElementById('activity-datetime');
    if (titleEl) titleEl.value = '';
    if (dateEl) dateEl.value = '';
}

function viewActivity(activityId) {
    showAlert(`Viendo detalles de actividad ${activityId}`, 'info');
}

function editActivity(activityId) {
    showAlert(`Editando actividad ${activityId}`, 'info');
}

// === Funciones de Bienestar (faltantes) ===
async function activatePsychChat() {
    const status = document.getElementById('chat-system-status')?.value;
    const psychologists = document.getElementById('available-psychologists')?.value;

    try {
        const response = await fetch('wellness_api.php?action=update_chat_system', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                status: status,
                available_psychologists: parseInt(psychologists || '0', 10)
            })
        });
        const data = await response.json();

        if (response.ok && data.success) {
            if (status === 'active') {
                showAlert(`Sistema de chat psicológico ACTIVADO con ${psychologists} psicólogos disponibles`, 'success');
            } else if (status === 'maintenance') {
                showAlert('Sistema de chat psicológico en mantenimiento', 'warning');
            } else {
                showAlert('Sistema de chat psicológico desactivado', 'info');
            }
        } else {
            showAlert('Error actualizando sistema de chat', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function viewChatSessions() {
    try {
        const response = await fetch(`wellness_api.php?action=get_chat_sessions&organization_id=${currentUser.organization_id}`);
        const data = await response.json();

        if (response.ok && data.success) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-card modal-lg modal-scroll">
                    <h3>Sesiones Activas de Chat Psicológico</h3>
                    <div class="stats-grid mb-20">
                        <div class="stat-card">
                            <div class="stat-number">${data.stats.active_sessions}</div>
                            <div class="stat-label">Sesiones Activas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${data.stats.waiting_students}</div>
                            <div class="stat-label">Estudiantes en Espera</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${data.stats.available_psychologists}</div>
                            <div class="stat-label">Psicólogos Disponibles</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${data.stats.avg_response_time}min</div>
                            <div class="stat-label">Tiempo Promedio de Respuesta</div>
                        </div>
                    </div>
                    <h4>Sesiones Activas</h4>
                    <div id="sessions-list">
                        ${data.sessions.map(session => `
                            <div class="box">
                                <h5>${session.student_name}</h5>
                                <p><strong>Psicólogo:</strong> ${session.psychologist_name || 'Esperando asignación'}</p>
                                <p><strong>Estado:</strong> <span class="${session.status === 'active' ? 'text-success' : 'text-warning'}">${session.status}</span></p>
                                <p><strong>Inicio:</strong> ${new Date(session.started_at).toLocaleString()}</p>
                                <p><strong>Duración:</strong> ${Math.floor((new Date() - new Date(session.started_at)) / 60000)} minutos</p>
                                <button onclick="joinChatSession(${session.id})" class="btn">Unirse a Sesión</button>
                                <button onclick="endChatSession(${session.id})" class="btn btn-danger">Finalizar</button>
                            </div>
                        `).join('')}
                    </div>
                    <div class="modal-actions">
                        <button onclick="this.closest('div').parentElement.remove()" class="btn">Cerrar</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            showAlert('Error cargando sesiones', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

function joinChatSession(sessionId) {
    showAlert(`Uniéndose a sesión de chat ${sessionId}`, 'info');
    // Integración real de chat puede añadirse aquí
}

async function endChatSession(sessionId) {
    if (confirm('¿Finalizar esta sesión de chat?')) {
        try {
            await fetch(`wellness_api.php?action=end_chat_session&session_id=${sessionId}`, {
                method: 'POST'
            });
            showAlert('Sesión finalizada', 'success');
            document.querySelector('.modal-overlay')?.remove();
            viewChatSessions(); // Recargar lista
        } catch (error) {
            showAlert('Error finalizando sesión', 'error');
        }
    }
}

async function managePsychologists() {
    try {
        const response = await fetch(`wellness_api.php?action=get_psychologists&organization_id=${currentUser.organization_id}`);
        const data = await response.json();

        if (response.ok && data.success) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-card modal-lg modal-scroll">
                    <h3>Gestión de Psicólogos</h3>
                    <div id="psychologists-list">
                        ${data.psychologists.map(psych => `
                            <div class="box">
                                <h4>${psych.full_name}</h4>
                                <p><strong>Email:</strong> ${psych.email}</p>
                                <p><strong>Estado:</strong> <span class="${psych.status === 'available' ? 'text-success' : psych.status === 'busy' ? 'text-warning' : 'text-danger'}">${psych.status}</span></p>
                                <p><strong>Sesiones Hoy:</strong> ${psych.sessions_today}</p>
                                <p><strong>Especialidad:</strong> ${psych.specialty || 'General'}</p>
                                <button onclick="updatePsychologistStatus(${psych.id}, '${psych.status === 'available' ? 'busy' : 'available'}')" class="btn">
                                    ${psych.status === 'available' ? 'Marcar Ocupado' : 'Marcar Disponible'}
                                </button>
                            </div>
                        `).join('')}
                    </div>
                    <div class="modal-actions">
                        <button onclick="addPsychologist()" class="btn btn-success">Agregar Psicólogo</button>
                        <button onclick="this.closest('div').parentElement.remove()" class="btn">Cerrar</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            showAlert('Error cargando psicólogos', 'error');
        }
    } catch (error) {
        showAlert('Error de conexión', 'error');
    }
}

async function updatePsychologistStatus(psychId, newStatus) {
    try {
        await fetch('wellness_api.php?action=update_psychologist_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                psychologist_id: psychId,
                status: newStatus
            })
        });
        showAlert('Estado actualizado', 'success');
        document.querySelector('.modal-overlay')?.remove();
        managePsychologists(); // Recargar lista
    } catch (error) {
        showAlert('Error actualizando estado', 'error');
    }
}

function addPsychologist() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-card modal-sm">
            <h3>Agregar Psicólogo</h3>
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" id="psych-name" placeholder="Dr. María González">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="psych-email" placeholder="maria.gonzalez@psicologo.com">
            </div>
            <div class="form-group">
                <label>Especialidad</label>
                <select id="psych-specialty">
                    <option value="general">Psicología General</option>
                    <option value="infantil">Psicología Infantil</option>
                    <option value="adolescente">Psicología Adolescente</option>
                    <option value="familiar">Psicología Familiar</option>
                </select>
            </div>
            <div class="modal-actions">
                <button onclick="createPsychologist()" class="btn btn-success">Crear Psicólogo</button>
                <button onclick="this.closest('div').parentElement.remove()" class="btn">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function createPsychologist() {
    const name = document.getElementById('psych-name')?.value;
    const email = document.getElementById('psych-email')?.value;
    const specialty = document.getElementById('psych-specialty')?.value;

    if (!name || !email) {
        showAlert('Complete nombre y email', 'warning');
        return;
    }

    try {
        await fetch('wellness_api.php?action=create_psychologist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organization_id: currentUser.organization_id,
                full_name: name,
                email: email,
                specialty: specialty
            })
        });
        showAlert('Psicólogo agregado exitosamente', 'success');
        document.querySelector('.modal-overlay')?.remove();
        managePsychologists(); // Recargar lista
    } catch (error) {
        showAlert('Error creando psicólogo', 'error');
    }
}

async function emergencyProtocol() {
    if (confirm('¿Activar protocolo de emergencia psicológica? Esto notificará a todos los psicólogos disponibles.')) {
        try {
            const response = await fetch('wellness_api.php?action=activate_emergency_protocol', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    organization_id: currentUser.organization_id,
                    activated_by: currentUser.id
                })
            });
            const data = await response.json();

            if (response.ok && data.success) {
                showAlert(`PROTOCOLO DE EMERGENCIA ACTIVADO - ${data.notified_psychologists} psicólogos notificados`, 'warning');
            } else {
                showAlert('Error activando protocolo', 'error');
            }
        } catch (error) {
            showAlert('Error de conexión', 'error');
        }
    }
}
// === Exponer funciones de uso por atributos onclick en el HTML (compatibilidad producción) ===
(function exposeGlobals() {
    const g = {
        // Navegación
        showSection,

        // Header
        logout,

        // Estudiantes
        addNewStudent,
        importStudents,
        generateStudentCards,
        exportStudentList,
        filterStudents,
        bulkStudentActions,
        sendBulkNotifications,

        // Docentes
        addNewTeacher,
        assignSubjects,
        teacherSchedule,
        teacherReports,
        generateTeacherEvaluation,
        viewEvaluationResults,

        // Académico
        manageSubjects,
        manageGrades,
        academicCalendar,
        gradingSystem,
        addAcademicPeriod,
        editPeriod,

        // Configuración
        saveInstitutionInfo,
        saveNotificationSettings,
        createBackup,
        restoreBackup,
        viewLogs,
        resetSystem,

        // Morosidad
        loadOverdueStudents,
        sendLegalNotification,
        sendNotification,
        createAgreement,
        savePaymentAgreement,
        createPaymentAgreement,

        // Acompañamiento (Bienestar)
        loadWellnessData,
        viewWellnessReport,
        viewAlerts,
        exportWellnessData,
        activatePsychChat,
        viewChatSessions,
        endChatSession,
        managePsychologists,
        updatePsychologistStatus,
        addPsychologist,
        createPsychologist,
        emergencyProtocol,

        // Acciones de tarjetas de alerta
        reviewAlert,
        contactStudent,
        escalateAlert,

        // Acciones de gestión de tareas/actividades de bienestar
        createSupportTask,
        scheduleWellnessActivity,
        viewActivity,
        editActivity
    };

    Object.keys(g).forEach((k) => {
        try {
            if (typeof g[k] === 'function') {
                window[k] = g[k];
            }
        } catch (_) {}
    });
})();