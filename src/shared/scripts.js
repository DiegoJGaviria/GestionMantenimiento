// Función para cargar contenido dinámicamente
function loadContent(url, targetId) {
    fetch(url)
        .then(response => response.text())
        .then(data => {
            document.getElementById(targetId).innerHTML = data;
        })
        .catch(error => console.error('Error:', error));
}

// Función para mostrar modales
function showModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

// Función para cerrar modales
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Función para cargar datos en las tablas
function loadTableData(url, tableBodyId) {
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById(tableBodyId);
            tableBody.innerHTML = '';
            data.forEach(item => {
                const row = document.createElement('tr');
                Object.values(item).forEach(value => {
                    const cell = document.createElement('td');
                    cell.textContent = value;
                    cell.classList.add('border', 'px-4', 'py-2');
                    row.appendChild(cell);
                });
                tableBody.appendChild(row);
            });
        })
        .catch(error => console.error('Error:', error));
}

// Función para cargar opciones en los selects
function loadSelectOptions(url, selectId, valueKey, textKey) {
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById(selectId);
            select.innerHTML = '<option value="">Seleccione una opción</option>';
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item[valueKey];
                option.textContent = item[textKey];
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error:', error));
}

// Función para enviar formularios
function submitForm(formId, url) {
    const form = document.getElementById(formId);
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Operación exitosa');
                // Cerrar el modal después de generar la factura
                closeModal('facturaModal');
                // Recargar la lista o actualizar la vista si es necesario
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos en las tablas
    loadTableData('/api/request/clientes.php', 'clientesTableBody');
    loadTableData('/api/request/equipos.php', 'equiposTableBody');
    loadTableData('/api/request/procedimientos.php', 'procedimientosTableBody
');

    // Cargar opciones en los selects del formulario de factura
    loadSelectOptions('/api/request/clientes.php', 'clienteId', 'id_cliente', 'nombre_cliente');
    loadSelectOptions('/api/request/equipos.php', 'equipoId', 'id_equipo', 'modelo');
    loadSelectOptions('/api/request/procedimientos.php', 'procedimientoId', 'id_procedimiento', 'nombre_procedimiento');

    // Inicializar formulario de factura
    submitForm('facturaForm', '/api/request/facturas.php');

    // Configurar eventos para cerrar modales
    document.getElementById('closeClientesModal').addEventListener('click', () => closeModal('clientesModal'));
    document.getElementById('closeEquiposModal').addEventListener('click', () => closeModal('equiposModal'));
    document.getElementById('closeProcedimientosModal').addEventListener('click', () => closeModal('procedimientosModal'));
    document.getElementById('closeFacturaModal').addEventListener('click', () => closeModal('facturaModal'));
});

