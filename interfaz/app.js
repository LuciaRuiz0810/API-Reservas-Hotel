// Variables globales
const API_BASE = '/API';
let currentUser = null;
let userType = null; // 'admin' o 'cliente'

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Verificar que se está accediendo desde el servidor web
    if (window.location.protocol === 'file:') {
        alert('ERROR: Debes acceder a esta página a través del servidor web.\n\nUsa: http://localhost/API/interfaz/demo.html\n\nNO abras el archivo directamente desde el explorador de archivos.');
        document.body.innerHTML = '<div style="padding:40px;text-align:center;"><h1 style="color:red;">Error de acceso</h1><p style="font-size:18px;">Debes acceder a través de:<br><strong>http://localhost/API/interfaz/demo.html</strong></p><p>NO abras el archivo HTML directamente.</p></div>';
        return;
    }

    // Login
    document.getElementById('loginForm').addEventListener('submit', login);

    // Configurar fecha mínima en los inputs de fecha
    setMinDate();
});

// Login
function login(e) {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    // Validar credenciales
    if (username === 'admin' && password === '2DawAp1') {
        currentUser = username;
        userType = 'admin';
        showMainScreen();
    } else if (username === 'Pérez García' && password === '12345678A') {
        currentUser = username;
        userType = 'cliente';
        showMainScreen();
    } else {
        document.getElementById('loginError').textContent = 'Usuario o contraseña incorrectos';
        document.getElementById('loginError').style.display = 'block';
        setTimeout(() => {
            document.getElementById('loginError').style.display = 'none';
        }, 3000);
    }
}

// Mostrar pantalla principal
function showMainScreen() {
    document.getElementById('loginScreen').style.display = 'none';
    document.getElementById('mainScreen').style.display = 'flex';
    document.getElementById('currentUser').textContent = currentUser;

    // Mostrar/ocultar botones según tipo de usuario
    if (userType === 'admin') {
        document.getElementById('btn-new-reserva').style.display = 'block';
        document.getElementById('btn-new-habitacion').style.display = 'block';
        document.getElementById('btn-new-cliente').style.display = 'block';
        document.getElementById('nav-clientes').style.display = 'inline-block';
    } else {
        // Para usuarios cliente, ocultar completamente los botones
        document.getElementById('btn-new-reserva').style.display = 'none';
        document.getElementById('btn-new-habitacion').style.display = 'none';
        document.getElementById('btn-new-cliente').style.display = 'none';
        // Ocultar pestaña de clientes para usuarios no admin
        document.getElementById('nav-clientes').style.display = 'none';
    }

    // Cargar datos iniciales
    loadReservas();
}

// Cerrar sesión
function logout() {
    currentUser = null;
    userType = null;
    document.getElementById('mainScreen').style.display = 'none';
    document.getElementById('loginScreen').style.display = 'flex';
    document.getElementById('username').value = '';
    document.getElementById('password').value = '';
}

// Navegación entre secciones
function showSection(section) {
    // Ocultar todas las secciones
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));

    // Mostrar sección seleccionada
    document.getElementById(section + '-section').classList.add('active');
    event.target.classList.add('active');

    // Cargar datos según la sección
    if (section === 'reservas') {
        loadReservas();
    } else if (section === 'habitaciones') {
        loadHabitaciones();
    } else if (section === 'clientes') {
        loadClientes();
    }
}

// ============ RESERVAS ============

async function loadReservas() {
    const container = document.getElementById('reservas-list');
    container.innerHTML = '<div class="loading">Cargando reservas...</div>';

    try {
        const response = await fetch(`${API_BASE}/reservas`);

        // Verificar si la respuesta es JSON
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const text = await response.text();
            console.error('Respuesta no es JSON:', text);
            throw new Error('La respuesta del servidor no es JSON. Verifica que Apache esté ejecutándose y mod_rewrite activado.');
        }

        const reservas = await response.json();

        // Obtener habitaciones para mostrar nombres
        const habitacionesResponse = await fetch(`${API_BASE}/habitaciones`);
        const habitaciones = await habitacionesResponse.json();
        const habitacionesMap = {};
        if (Array.isArray(habitaciones)) {
            habitaciones.forEach(h => {
                habitacionesMap[h.id] = `Hab. ${h.numero} - ${h.tipo}`;
            });
        }

        if (Array.isArray(reservas) && reservas.length > 0) {
            let html = '<table><thead><tr>';
            html += '<th>ID</th><th>Cliente ID</th><th>Habitación</th><th>Entrada</th><th>Salida</th>';
            html += '<th>Precio Total</th><th>Estado</th>';

            if (userType === 'admin') {
                html += '<th>Acciones</th>';
            }

            html += '</tr></thead><tbody>';

            reservas.forEach(reserva => {
                html += `<tr id="reserva-${reserva.id}" data-habitacion-id="${reserva.habitacion_id}">`;
                html += `<td>${reserva.id}</td>`;
                html += `<td>${reserva.cliente_id}</td>`;
                html += `<td>${habitacionesMap[reserva.habitacion_id] || 'Hab. ' + reserva.habitacion_id}</td>`;
                html += `<td>${reserva.fecha_entrada}</td>`;
                html += `<td>${reserva.fecha_salida}</td>`;
                html += `<td>${reserva.precio_total}€</td>`;
                html += `<td>${reserva.estado}</td>`;

                if (userType === 'admin') {
                    html += `<td>
                        <button onclick="editReserva(${reserva.id})" class="btn-edit">Editar</button>
                        <button onclick="deleteReserva(${reserva.id})" class="btn-delete">Eliminar</button>
                    </td>`;
                }

                html += '</tr>';
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p>No hay reservas disponibles.</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="error-message">Error al cargar reservas: ' + error.message + '</p>';
    }
}

async function editReserva(id) {
    const row = document.getElementById(`reserva-${id}`);
    const cells = row.querySelectorAll('td');

    // Verificar si ya está en modo edición
    if (row.classList.contains('edit-mode')) {
        saveReserva(id);
        return;
    }

    // Activar modo edición
    row.classList.add('edit-mode');

    // Obtener datos actuales
    const habitacionIdActual = row.getAttribute('data-habitacion-id');
    const fechaEntrada = cells[3].textContent;
    const fechaSalida = cells[4].textContent;
    const estado = cells[6].textContent;

    // Obtener todas las habitaciones para el select
    const habitacionesResponse = await fetch(`${API_BASE}/habitaciones`);
    const habitaciones = await habitacionesResponse.json();

    // Crear select de habitaciones
    let habitacionesOptions = '';
    if (Array.isArray(habitaciones)) {
        habitaciones.forEach(h => {
            const selected = h.id == habitacionIdActual ? 'selected' : '';
            habitacionesOptions += `<option value="${h.id}" ${selected}>Hab. ${h.numero} - ${h.tipo}</option>`;
        });
    }

    // Hacer editable la habitación
    cells[2].innerHTML = `<select id="edit-habitacion-${id}">${habitacionesOptions}</select>`;

    // Hacer editables las fechas
    cells[3].innerHTML = `<input type="date" id="edit-entrada-${id}" value="${fechaEntrada}">`;
    cells[4].innerHTML = `<input type="date" id="edit-salida-${id}" value="${fechaSalida}">`;

    // Estado
    cells[6].innerHTML = `
        <select id="edit-estado-${id}">
            <option value="activa" ${estado === 'activa' ? 'selected' : ''}>Activa</option>
            <option value="cancelada" ${estado === 'cancelada' ? 'selected' : ''}>Cancelada</option>
            <option value="completada" ${estado === 'completada' ? 'selected' : ''}>Completada</option>
        </select>
    `;

    // Cambiar botones y añadir espacio para info de fechas
    const actionsCell = cells[7];
    actionsCell.innerHTML = `
        <button onclick="saveReserva(${id})" class="btn-save">Guardar</button>
        <button onclick="loadReservas()" class="btn-cancel">Cancelar</button>
    `;

    // Crear fila adicional para mostrar fechas ocupadas
    const newRow = row.insertAdjacentElement('afterend', document.createElement('tr'));
    newRow.id = `reserva-info-${id}`;
    newRow.innerHTML = `
        <td colspan="8" style="padding:0;">
            <div id="fechas-ocupadas-info-${id}" style="margin:10px; padding:10px; background:#fff3cd; border:1px solid #ffc107; border-radius:4px; display:none;">
                <strong>⚠️ Fechas ocupadas para esta habitación:</strong>
                <div id="fechas-ocupadas-list-${id}"></div>
            </div>
        </td>
    `;

    // Configurar restricciones de fechas
    const selectHabitacion = document.getElementById(`edit-habitacion-${id}`);
    const inputEntrada = document.getElementById(`edit-entrada-${id}`);
    const inputSalida = document.getElementById(`edit-salida-${id}`);

    // Fecha mínima: hoy
    const hoy = new Date().toISOString().split('T')[0];
    inputEntrada.setAttribute('min', hoy);
    inputSalida.setAttribute('min', hoy);

    // Mostrar fechas ocupadas iniciales
    let fechasOcupadas = await obtenerFechasOcupadas(habitacionIdActual, id);
    mostrarFechasOcupadasInfo(habitacionIdActual, fechasOcupadas, id);

    // Listener cuando cambia la habitación
    selectHabitacion.addEventListener('change', async function() {
        const nuevaHabitacionId = selectHabitacion.value;
        // Limpiar fechas
        inputEntrada.value = '';
        inputSalida.value = '';
        // Actualizar fechas ocupadas
        fechasOcupadas = await obtenerFechasOcupadas(nuevaHabitacionId, id);
        mostrarFechasOcupadasInfo(nuevaHabitacionId, fechasOcupadas, id);
    });

    // Listener para validar fechas al cambiar
    inputEntrada.addEventListener('change', async function() {
        const habitacionId = selectHabitacion.value;
        const fechasOcupadas = await obtenerFechasOcupadas(habitacionId, id);
        validarFechaDisponible(inputEntrada, fechasOcupadas, habitacionId);
        // La fecha de salida debe ser posterior a la entrada
        inputSalida.setAttribute('min', inputEntrada.value);
    });

    inputSalida.addEventListener('change', async function() {
        const habitacionId = selectHabitacion.value;
        const fechasOcupadas = await obtenerFechasOcupadas(habitacionId, id);
        validarFechaDisponible(inputSalida, fechasOcupadas, habitacionId);
    });
}

async function saveReserva(id) {
    try {
        // Obtener valores
        const habitacionInput = document.getElementById(`edit-habitacion-${id}`);
        const entradaInput = document.getElementById(`edit-entrada-${id}`);
        const salidaInput = document.getElementById(`edit-salida-${id}`);
        const estadoInput = document.getElementById(`edit-estado-${id}`);

        const estado = estadoInput.value;
        const requestBody = {
            usuario: { usuario: 'admin', contrasena: '2DawAp1' }
        };

        // Si se están editando las fechas o la habitación
        if (entradaInput && salidaInput && habitacionInput) {
            const habitacionId = habitacionInput.value;
            const fechaEntrada = entradaInput.value;
            const fechaSalida = salidaInput.value;

            if (!fechaEntrada || !fechaSalida) {
                alert('Las fechas de entrada y salida son obligatorias');
                return;
            }

            if (new Date(fechaSalida) <= new Date(fechaEntrada)) {
                alert('La fecha de salida debe ser posterior a la fecha de entrada');
                return;
            }

            // Validar disponibilidad (excluyendo la reserva actual)
            const fechasOcupadas = await obtenerFechasOcupadas(habitacionId, id);
            const solapamiento = verificarSolapamiento(fechaEntrada, fechaSalida, fechasOcupadas);

            if (solapamiento.solapa) {
                alert(`La habitación NO está disponible en esas fechas.\n\nConflicto con reserva del ${formatearFecha(solapamiento.rango.entrada)} al ${formatearFecha(solapamiento.rango.salida)}\n\nPor favor, selecciona otras fechas.`);
                return;
            }

            requestBody.reserva = {
                habitacion_id: habitacionId,
                fecha_entrada: fechaEntrada,
                fecha_salida: fechaSalida
            };
        }

        // Si se está cambiando el estado
        if (estado === 'cancelada') {
            requestBody.accion = 'cancelar';
        } else if (estado === 'completada') {
            requestBody.accion = 'completar';
        }

        const response = await fetch(`${API_BASE}/reservas/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });

        const data = await response.json();

        if (response.ok) {
            alert('Reserva actualizada correctamente');
            // Eliminar fila de info si existe
            const infoRow = document.getElementById(`reserva-info-${id}`);
            if (infoRow) infoRow.remove();
            loadReservas();
        } else {
            alert('Error: ' + (data.error || 'No se pudo actualizar'));
        }
    } catch (error) {
        alert('Error al actualizar: ' + error.message);
    }
}

async function deleteReserva(id) {
    if (!confirm('¿Estás seguro de eliminar esta reserva?')) return;

    try {
        const response = await fetch(`${API_BASE}/reservas/${id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario: { usuario: 'admin', contrasena: '2DawAp1' }
            })
        });

        const data = await response.json();

        if (response.ok) {
            alert('Reserva eliminada correctamente');
            loadReservas();
        } else {
            alert('Error: ' + (data.error || 'No se pudo eliminar'));
        }
    } catch (error) {
        alert('Error al eliminar: ' + error.message);
    }
}

async function showAddForm(type) {
    document.getElementById(`add-${type}-form`).style.display = 'block';
    document.getElementById(`btn-new-${type}`).style.display = 'none';

    // Si es formulario de reserva, cargar habitaciones y agregar listeners
    if (type === 'reserva') {
        // Cargar habitaciones en el select
        const habitacionSelect = document.getElementById('new-reserva-habitacion');
        const habitacionesResponse = await fetch(`${API_BASE}/habitaciones`);
        const habitaciones = await habitacionesResponse.json();

        habitacionSelect.innerHTML = '<option value="">Seleccione una habitación</option>';
        if (Array.isArray(habitaciones)) {
            habitaciones.forEach(h => {
                habitacionSelect.innerHTML += `<option value="${h.id}">Hab. ${h.numero} - ${h.tipo} (€${h.precio}/noche)</option>`;
            });
        }

        const entradaInput = document.getElementById('new-reserva-entrada');
        const salidaInput = document.getElementById('new-reserva-salida');

        // Listener cuando cambia la habitación
        habitacionSelect.addEventListener('change', async function() {
            entradaInput.value = '';
            salidaInput.value = '';

            if (habitacionSelect.value) {
                const fechasOcupadas = await obtenerFechasOcupadas(habitacionSelect.value);
                mostrarFechasOcupadasInfo(habitacionSelect.value, fechasOcupadas, 'new');
            } else {
                document.getElementById('fechas-ocupadas-info-new').style.display = 'none';
            }
        });

        // Listener cuando cambia la fecha de entrada
        entradaInput.addEventListener('change', async function() {
            if (habitacionSelect.value) {
                const fechasOcupadas = await obtenerFechasOcupadas(habitacionSelect.value);
                validarFechaDisponible(entradaInput, fechasOcupadas, habitacionSelect.value);
                // Actualizar fecha mínima de salida
                salidaInput.setAttribute('min', entradaInput.value);
            }
        });

        // Listener cuando cambia la fecha de salida
        salidaInput.addEventListener('change', async function() {
            if (habitacionSelect.value) {
                const fechasOcupadas = await obtenerFechasOcupadas(habitacionSelect.value);
                validarFechaDisponible(salidaInput, fechasOcupadas, habitacionSelect.value);
            }
        });
    }
}

function cancelAdd(type) {
    document.getElementById(`add-${type}-form`).style.display = 'none';
    document.getElementById(`btn-new-${type}`).style.display = 'block';
}

async function addReserva() {
    const cliente_id = document.getElementById('new-reserva-cliente').value;
    const habitacion_id = document.getElementById('new-reserva-habitacion').value;
    const fecha_entrada = document.getElementById('new-reserva-entrada').value;
    const fecha_salida = document.getElementById('new-reserva-salida').value;

    if (!cliente_id || !habitacion_id || !fecha_entrada || !fecha_salida) {
        alert('Todos los campos son obligatorios');
        return;
    }

    // Validar que fecha salida sea posterior a fecha entrada
    if (new Date(fecha_salida) <= new Date(fecha_entrada)) {
        alert('La fecha de salida debe ser posterior a la fecha de entrada');
        return;
    }

    // Validar disponibilidad de la habitación
    const fechasOcupadas = await obtenerFechasOcupadas(habitacion_id);
    const solapamiento = verificarSolapamiento(fecha_entrada, fecha_salida, fechasOcupadas);

    if (solapamiento.solapa) {
        alert(`La habitación NO está disponible en esas fechas.\n\nConflicto con reserva del ${formatearFecha(solapamiento.rango.entrada)} al ${formatearFecha(solapamiento.rango.salida)}\n\nPor favor, selecciona otras fechas.`);
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/reservas`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario: { usuario: 'admin', contrasena: '2DawAp1' },
                reserva: { cliente_id, habitacion_id, fecha_entrada, fecha_salida }
            })
        });

        const data = await response.json();

        if (response.ok) {
            alert('Reserva creada correctamente');
            // Limpiar formulario
            document.getElementById('new-reserva-cliente').value = '';
            document.getElementById('new-reserva-habitacion').value = '';
            document.getElementById('new-reserva-entrada').value = '';
            document.getElementById('new-reserva-salida').value = '';
            cancelAdd('reserva');
            loadReservas();
        } else {
            alert('Error: ' + (data.error || 'No se pudo crear'));
        }
    } catch (error) {
        alert('Error al crear: ' + error.message);
    }
}

// ============ HABITACIONES ============

async function loadHabitaciones() {
    const container = document.getElementById('habitaciones-list');
    container.innerHTML = '<div class="loading">Cargando habitaciones...</div>';

    try {
        const response = await fetch(`${API_BASE}/habitaciones`);
        const data = await response.json();

        if (Array.isArray(data) && data.length > 0) {
            let html = '<table><thead><tr>';
            html += '<th>ID</th><th>Número</th><th>Planta</th><th>Tipo</th><th>Precio</th>';
            html += '<th>Personas</th><th>Suite</th>';

            if (userType === 'admin') {
                html += '<th>Acciones</th>';
            }

            html += '</tr></thead><tbody>';

            data.forEach(hab => {
                html += `<tr id="habitacion-${hab.id}">`;
                html += `<td>${hab.id}</td>`;
                html += `<td>${hab.numero}</td>`;
                html += `<td>${hab.planta}</td>`;
                html += `<td>${hab.tipo}</td>`;
                html += `<td>${hab.precio}€</td>`;
                html += `<td>${hab.num_personas}</td>`;
                html += `<td>${hab.suite == 1 ? 'Sí' : 'No'}</td>`;

                if (userType === 'admin') {
                    html += `<td>
                        <button onclick="editHabitacion(${hab.id})" class="btn-edit">Editar</button>
                        <button onclick="deleteHabitacion(${hab.id})" class="btn-delete">Eliminar</button>
                    </td>`;
                }

                html += '</tr>';
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p>No hay habitaciones disponibles.</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="error-message">Error al cargar habitaciones: ' + error.message + '</p>';
    }
}

function editHabitacion(id) {
    const row = document.getElementById(`habitacion-${id}`);
    const cells = row.querySelectorAll('td');

    if (row.classList.contains('edit-mode')) {
        saveHabitacion(id);
        return;
    }

    row.classList.add('edit-mode');

    // Hacer editables los campos
    cells[1].innerHTML = `<input type="number" id="edit-numero-${id}" value="${cells[1].textContent}">`;
    cells[2].innerHTML = `<input type="number" id="edit-planta-${id}" value="${cells[2].textContent}">`;
    cells[3].innerHTML = `
        <select id="edit-tipo-${id}">
            <option value="Individual" ${cells[3].textContent === 'Individual' ? 'selected' : ''}>Individual</option>
            <option value="Doble" ${cells[3].textContent === 'Doble' ? 'selected' : ''}>Doble</option>
            <option value="Triple" ${cells[3].textContent === 'Triple' ? 'selected' : ''}>Triple</option>
            <option value="Suite" ${cells[3].textContent === 'Suite' ? 'selected' : ''}>Suite</option>
        </select>
    `;
    cells[4].innerHTML = `<input type="number" id="edit-precio-${id}" value="${cells[4].textContent.replace('€', '')}">`;
    cells[5].innerHTML = `<input type="number" id="edit-personas-${id}" value="${cells[5].textContent}">`;
    cells[6].innerHTML = `
        <select id="edit-suite-${id}">
            <option value="0" ${cells[6].textContent === 'No' ? 'selected' : ''}>No</option>
            <option value="1" ${cells[6].textContent === 'Sí' ? 'selected' : ''}>Sí</option>
        </select>
    `;

    cells[7].innerHTML = `
        <button onclick="saveHabitacion(${id})" class="btn-save">Guardar</button>
        <button onclick="loadHabitaciones()" class="btn-cancel">Cancelar</button>
    `;
}

async function saveHabitacion(id) {
    const numero = document.getElementById(`edit-numero-${id}`).value;
    const planta = document.getElementById(`edit-planta-${id}`).value;
    const tipo = document.getElementById(`edit-tipo-${id}`).value;
    const precio = document.getElementById(`edit-precio-${id}`).value;
    const num_personas = document.getElementById(`edit-personas-${id}`).value;
    const suite = document.getElementById(`edit-suite-${id}`).value;

    try {
        const response = await fetch(`${API_BASE}/habitaciones/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario: { usuario: 'admin', contrasena: '2DawAp1' },
                habitacion: { numero, planta, tipo, precio, num_personas, suite }
            })
        });

        const data = await response.json();

        if (response.ok) {
            alert('Habitación actualizada correctamente');
            loadHabitaciones();
        } else {
            alert('Error: ' + (data.error || 'No se pudo actualizar'));
        }
    } catch (error) {
        alert('Error al actualizar: ' + error.message);
    }
}

async function deleteHabitacion(id) {
    if (!confirm('¿Estás seguro de eliminar esta habitación?')) return;

    try {
        const response = await fetch(`${API_BASE}/habitaciones/${id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario: { usuario: 'admin', contrasena: '2DawAp1' }
            })
        });

        const data = await response.json();

        if (response.ok) {
            alert('Habitación eliminada correctamente');
            loadHabitaciones();
        } else {
            alert('Error: ' + (data.error || 'No se pudo eliminar'));
        }
    } catch (error) {
        alert('Error al eliminar: ' + error.message);
    }
}

async function addHabitacion() {
    const numero = document.getElementById('new-habitacion-numero').value;
    const planta = document.getElementById('new-habitacion-planta').value;
    const tipo = document.getElementById('new-habitacion-tipo').value;
    const precio = document.getElementById('new-habitacion-precio').value;
    const num_personas = document.getElementById('new-habitacion-personas').value;
    const suite = document.getElementById('new-habitacion-suite').value;

    if (!numero || !planta || !precio || !num_personas) {
        alert('Todos los campos son obligatorios');
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/habitaciones`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario: { usuario: 'admin', contrasena: '2DawAp1' },
                habitacion: { numero, planta, tipo, precio, suite, num_personas },
                opcion: { n: 1 }
            })
        });

        const data = await response.json();

        if (response.ok) {
            alert('Habitación creada correctamente');
            // Limpiar formulario
            document.getElementById('new-habitacion-numero').value = '';
            document.getElementById('new-habitacion-planta').value = '';
            document.getElementById('new-habitacion-precio').value = '';
            document.getElementById('new-habitacion-personas').value = '';
            cancelAdd('habitacion');
            loadHabitaciones();
        } else {
            alert('Error: ' + (data.error || 'No se pudo crear'));
        }
    } catch (error) {
        alert('Error al crear: ' + error.message);
    }
}

// ============ CLIENTES ============

async function loadClientes() {
    const container = document.getElementById('clientes-list');
    container.innerHTML = '<div class="loading">Cargando clientes...</div>';

    try {
        const response = await fetch(`${API_BASE}/clientes`);
        const data = await response.json();

        if (Array.isArray(data) && data.length > 0) {
            let html = '<table><thead><tr>';
            html += '<th>ID</th><th>Nombre</th><th>Apellidos</th><th>DNI</th><th>Correo</th><th>Teléfono</th>';

            if (userType === 'admin') {
                html += '<th>Acciones</th>';
            }

            html += '</tr></thead><tbody>';

            data.forEach(cliente => {
                html += `<tr id="cliente-${cliente.id}">`;
                html += `<td>${cliente.id}</td>`;
                html += `<td>${cliente.nombre}</td>`;
                html += `<td>${cliente.apellidos}</td>`;
                html += `<td>${cliente.dni}</td>`;
                html += `<td>${cliente.correo}</td>`;
                html += `<td>${cliente.telefono || '-'}</td>`;

                if (userType === 'admin') {
                    html += `<td>
                        <button onclick="editCliente(${cliente.id})" class="btn-edit">Editar</button>
                        <button onclick="deleteCliente(${cliente.id})" class="btn-delete">Eliminar</button>
                    </td>`;
                }

                html += '</tr>';
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p>No hay clientes disponibles.</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="error-message">Error al cargar clientes: ' + error.message + '</p>';
    }
}

function editCliente(id) {
    const row = document.getElementById(`cliente-${id}`);
    const cells = row.querySelectorAll('td');

    if (row.classList.contains('edit-mode')) {
        saveCliente(id);
        return;
    }

    row.classList.add('edit-mode');

    // Hacer editables los campos (no el DNI)
    cells[1].innerHTML = `<input type="text" id="edit-nombre-${id}" value="${cells[1].textContent}">`;
    cells[2].innerHTML = `<input type="text" id="edit-apellidos-${id}" value="${cells[2].textContent}">`;
    cells[4].innerHTML = `<input type="email" id="edit-correo-${id}" value="${cells[4].textContent}">`;
    cells[5].innerHTML = `<input type="tel" id="edit-telefono-${id}" value="${cells[5].textContent}">`;

    cells[6].innerHTML = `
        <button onclick="saveCliente(${id})" class="btn-save">Guardar</button>
        <button onclick="loadClientes()" class="btn-cancel">Cancelar</button>
    `;
}

async function saveCliente(id) {
    const nombre = document.getElementById(`edit-nombre-${id}`).value;
    const apellidos = document.getElementById(`edit-apellidos-${id}`).value;
    const correo = document.getElementById(`edit-correo-${id}`).value;
    const telefono = document.getElementById(`edit-telefono-${id}`).value;

    try {
        const response = await fetch(`${API_BASE}/clientes/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario: { usuario: 'admin', contrasena: '2DawAp1' },
                cliente: { nombre, apellidos, correo, telefono }
            })
        });

        const data = await response.json();

        if (response.ok) {
            alert('Cliente actualizado correctamente');
            loadClientes();
        } else {
            alert('Error: ' + (data.error || 'No se pudo actualizar'));
        }
    } catch (error) {
        alert('Error al actualizar: ' + error.message);
    }
}

async function deleteCliente(id) {
    if (!confirm('¿Estás seguro de eliminar este cliente?')) return;

    try {
        const response = await fetch(`${API_BASE}/clientes/${id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario: { usuario: 'admin', contrasena: '2DawAp1' }
            })
        });

        const data = await response.json();

        if (response.ok) {
            alert('Cliente eliminado correctamente');
            loadClientes();
        } else {
            alert('Error: ' + (data.error || 'No se pudo eliminar'));
        }
    } catch (error) {
        alert('Error al eliminar: ' + error.message);
    }
}

async function addCliente() {
    const nombre = document.getElementById('new-cliente-nombre').value;
    const apellidos = document.getElementById('new-cliente-apellidos').value;
    const dni = document.getElementById('new-cliente-dni').value;
    const correo = document.getElementById('new-cliente-correo').value;
    const telefono = document.getElementById('new-cliente-telefono').value;

    if (!nombre || !apellidos || !dni || !correo) {
        alert('Nombre, apellidos, DNI y correo son obligatorios');
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/clientes`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario: { usuario: 'admin', contrasena: '2DawAp1' },
                cliente: { nombre, apellidos, dni, correo, telefono }
            })
        });

        const data = await response.json();

        if (response.ok) {
            alert('Cliente creado correctamente');
            // Limpiar formulario
            document.getElementById('new-cliente-nombre').value = '';
            document.getElementById('new-cliente-apellidos').value = '';
            document.getElementById('new-cliente-dni').value = '';
            document.getElementById('new-cliente-correo').value = '';
            document.getElementById('new-cliente-telefono').value = '';
            cancelAdd('cliente');
            loadClientes();
        } else {
            alert('Error: ' + (data.error || 'No se pudo crear'));
        }
    } catch (error) {
        alert('Error al crear: ' + error.message);
    }
}

// ============ UTILIDADES ============

function setMinDate() {
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.setAttribute('min', today);
    });
}

// Obtener fechas ocupadas para una habitación específica
async function obtenerFechasOcupadas(habitacionId, reservaIdActual = null) {
    try {
        const response = await fetch(`${API_BASE}/reservas`);
        const reservas = await response.json();

        if (!Array.isArray(reservas)) return [];

        // Filtrar reservas activas de la misma habitación (excluyendo la reserva actual en edición)
        const reservasHabitacion = reservas.filter(r =>
            r.habitacion_id == habitacionId &&
            r.estado === 'activa' &&
            r.id != reservaIdActual
        );

        // Crear array de rangos de fechas ocupadas
        const fechasOcupadas = reservasHabitacion.map(r => ({
            entrada: new Date(r.fecha_entrada),
            salida: new Date(r.fecha_salida)
        }));

        return fechasOcupadas;
    } catch (error) {
        console.error('Error al obtener fechas ocupadas:', error);
        return [];
    }
}

// Validar si una fecha está disponible
function validarFechaDisponible(input, fechasOcupadas, habitacionId) {
    const fechaSeleccionada = new Date(input.value);

    for (const rango of fechasOcupadas) {
        if (fechaSeleccionada >= rango.entrada && fechaSeleccionada < rango.salida) {
            alert(`La fecha ${input.value} ya está ocupada para esta habitación.\n\nPeriodo ocupado: ${formatearFecha(rango.entrada)} - ${formatearFecha(rango.salida)}`);
            input.value = '';
            return false;
        }
    }
    return true;
}

// Verificar si un rango de fechas se solapa con fechas ocupadas
function verificarSolapamiento(fechaEntrada, fechaSalida, fechasOcupadas) {
    const entrada = new Date(fechaEntrada);
    const salida = new Date(fechaSalida);

    for (const rango of fechasOcupadas) {
        // Verificar si hay solapamiento
        if (entrada < rango.salida && salida > rango.entrada) {
            return {
                solapa: true,
                rango: rango
            };
        }
    }

    return { solapa: false };
}

// Formatear fecha para mostrar
function formatearFecha(fecha) {
    return fecha.toISOString().split('T')[0];
}

// Mostrar información de fechas ocupadas
function mostrarFechasOcupadasInfo(habitacionId, fechasOcupadas, contexto) {
    const infoDiv = document.getElementById(`fechas-ocupadas-info-${contexto}`);
    const listDiv = document.getElementById(`fechas-ocupadas-list-${contexto}`);

    if (!infoDiv) {
        // Si no existe el div de info, insertarlo después de h3
        const form = contexto === 'new' ?
            document.getElementById('add-reserva-form') :
            document.getElementById(`reserva-${contexto}`);

        if (form && fechasOcupadas.length > 0) {
            const infoHtml = `
                <div id="fechas-ocupadas-info-${contexto}" style="margin:10px 0; padding:10px; background:#fff3cd; border:1px solid #ffc107; border-radius:4px;">
                    <strong>⚠️ Fechas ocupadas para esta habitación:</strong>
                    <ul id="fechas-ocupadas-list-${contexto}" style="margin:5px 0 0 20px; padding:0;">
                        ${fechasOcupadas.map(f => `<li>${formatearFecha(f.entrada)} al ${formatearFecha(f.salida)}</li>`).join('')}
                    </ul>
                </div>
            `;

            // Insertar después del h3 o botones
            if (contexto === 'new') {
                const h3 = form.querySelector('h3');
                h3.insertAdjacentHTML('afterend', infoHtml);
            }
        }
        return;
    }

    if (fechasOcupadas.length > 0) {
        listDiv.innerHTML = '<ul style="margin:5px 0 0 20px; padding:0;">' +
            fechasOcupadas.map(f => `<li>${formatearFecha(f.entrada)} al ${formatearFecha(f.salida)}</li>`).join('') +
            '</ul>';
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}
