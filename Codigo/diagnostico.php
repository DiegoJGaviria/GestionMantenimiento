<?php
// diagnostico_crud.php

// Conexión a la base de datos
include("conexion.php");

// Función para obtener todos los diagnósticos
function obtenerDiagnosticos($conn) {
    $sql = "SELECT * FROM Diagnostico";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener detalles de un diagnóstico
function obtenerDetallesDiagnostico($conn, $idDiagnostico) {
    $sql = "SELECT dd.*, a.Nombre_Arreglo, m.Nombre_Marca, u.Primer_Nombre, u.Primer_Apellido 
            FROM Detalle_Diagnostico dd 
            JOIN Arreglo a ON dd.Arreglo_idArreglo = a.idArreglo 
            JOIN Marca m ON dd.Arreglo_Marca_idMarca = m.idMarca 
            JOIN Usuario u ON dd.Arreglo_Usuario_idUsuario = u.idUsuario 
            WHERE dd.Diagnostico_idDiagnostico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idDiagnostico);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener arreglos
function obtenerArreglos($conn) {
    $sql = "SELECT a.*, m.Nombre_Marca, u.Primer_Nombre, u.Primer_Apellido 
            FROM Arreglo a 
            JOIN Marca m ON a.Marca_idMarca = m.idMarca 
            JOIN Usuario u ON a.Usuario_idUsuario = u.idUsuario";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Crear diagnóstico
if (isset($_POST['crear_diagnostico'])) {
    $componente = $_POST['componente'];
    $valor = $_POST['valor'];
    $descripcion = $_POST['descripcion'];

    $stmt = $conn->prepare("INSERT INTO Diagnostico (Componente, Valor, Descripcion) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $componente, $valor, $descripcion);
    
    if ($stmt->execute()) {
        $diagnostico_id = $conn->insert_id;
        $detalles = json_decode($_POST['detalles_diagnostico'], true);
        foreach ($detalles as $detalle) {
            $arreglo_id = $detalle['arreglo_id'];
            $marca_id = $detalle['marca_id'];
            $usuario_id = $detalle['usuario_id'];
            $cantidad = $detalle['cantidad'];
            $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Diagnostico (Arreglo_idArreglo, Arreglo_Marca_idMarca, Arreglo_Usuario_idUsuario, Diagnostico_idDiagnostico, Cantidad) VALUES (?, ?, ?, ?, ?)");
            $stmt_detalle->bind_param("iiiii", $arreglo_id, $marca_id, $usuario_id, $diagnostico_id, $cantidad);
            $stmt_detalle->execute();
        }
        echo "<div class='alert alert-success'>Diagnóstico creado con éxito</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
}

// Actualizar diagnóstico
if (isset($_POST['actualizar_diagnostico'])) {
    $id = $_POST['id_diagnostico'];
    $componente = $_POST['componente'];
    $valor = $_POST['valor'];
    $descripcion = $_POST['descripcion'];

    $stmt = $conn->prepare("UPDATE Diagnostico SET Componente=?, Valor=?, Descripcion=? WHERE idDiagnostico=?");
    $stmt->bind_param("sdsi", $componente, $valor, $descripcion, $id);
    
    if ($stmt->execute()) {
        // Eliminar detalles existentes
        $stmt_delete = $conn->prepare("DELETE FROM Detalle_Diagnostico WHERE Diagnostico_idDiagnostico=?");
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();

        // Insertar nuevos detalles
        $detalles = json_decode($_POST['detalles_diagnostico'], true);
        foreach ($detalles as $detalle) {
            $arreglo_id = $detalle['arreglo_id'];
            $marca_id = $detalle['marca_id'];
            $usuario_id = $detalle['usuario_id'];
            $cantidad = $detalle['cantidad'];
            $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Diagnostico (Arreglo_idArreglo, Arreglo_Marca_idMarca, Arreglo_Usuario_idUsuario, Diagnostico_idDiagnostico, Cantidad) VALUES (?, ?, ?, ?, ?)");
            $stmt_detalle->bind_param("iiiii", $arreglo_id, $marca_id, $usuario_id, $id, $cantidad);
            $stmt_detalle->execute();
        }
        echo "<div class='alert alert-success'>Diagnóstico actualizado con éxito</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
}

// Eliminar diagnóstico
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt_delete_detalles = $conn->prepare("DELETE FROM Detalle_Diagnostico WHERE Diagnostico_idDiagnostico=?");
    $stmt_delete_detalles->bind_param("i", $id);
    $stmt_delete_detalles->execute();
    
    $stmt_delete_diagnostico = $conn->prepare("DELETE FROM Diagnostico WHERE idDiagnostico=?");
    $stmt_delete_diagnostico->bind_param("i", $id);
    if ($stmt_delete_diagnostico->execute()) {
        echo "<div class='alert alert-success'>Diagnóstico eliminado con éxito</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt_delete_diagnostico->error . "</div>";
    }
}

$diagnosticos = obtenerDiagnosticos($conn);
$arreglos = obtenerArreglos($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnósticos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Listar Diagnósticos</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearDiagnosticoModal">
                    Crear Diagnóstico
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Componente</th>
                                <th>Valor</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diagnosticos as $diagnostico): ?>
                            <tr>
                                <td><?php echo $diagnostico['idDiagnostico']; ?></td>
                                <td><?php echo $diagnostico['Componente']; ?></td>
                                <td><?php echo $diagnostico['Valor']; ?></td>
                                <td><?php echo $diagnostico['Descripcion']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarDiagnosticoModal<?php echo $diagnostico['idDiagnostico']; ?>">
                                        Editar
                                    </button>
                                    <a href="?eliminar=<?php echo $diagnostico['idDiagnostico']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este diagnóstico?')">Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear diagnóstico -->
    <div class="modal fade" id="crearDiagnosticoModal" tabindex="-1" aria-labelledby="crearDiagnosticoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearDiagnosticoModalLabel">Crear Nuevo Diagnóstico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="crearDiagnosticoForm" action="" method="post">
                        <div class="mb-3">
                            <label for="componente" class="form-label">Componente</label>
                            <input type="text" class="form-control" id="componente" name="componente" required>
                        </div>
                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor</label>
                            <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                        </div>
                        <h5 class="mt-4">Detalles del Diagnóstico</h5>
                        <table class="table table-bordered" id="detallesTable">
                            <thead>
                                <tr>
                                    <th>Arreglo</th>
                                    <th>Cantidad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los detalles se agregarán aquí dinámicamente -->
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-secondary" onclick="agregarDetalle()">Agregar Detalle</button>
                        <input type="hidden" id="detalles_diagnostico" name="detalles_diagnostico" value="[]">
                        <div class="mt-3">
                            <button type="submit" name="crear_diagnostico" class="btn btn-primary">Crear Diagnóstico</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para editar diagnóstico -->
    <?php foreach ($diagnosticos as $diagnostico): ?>
    <div class="modal fade" id="editarDiagnosticoModal<?php echo $diagnostico['idDiagnostico']; ?>" tabindex="-1" aria-labelledby="editarDiagnosticoModalLabel<?php echo $diagnostico['idDiagnostico']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarDiagnosticoModalLabel<?php echo $diagnostico['idDiagnostico']; ?>">Editar Diagnóstico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editarDiagnosticoForm<?php echo $diagnostico['idDiagnostico']; ?>" action="" method="post">
                        <input type="hidden" name="id_diagnostico" value="<?php echo $diagnostico['idDiagnostico']; ?>">
                        <div class="mb-3">
                            <label for="componente<?php echo $diagnostico['idDiagnostico']; ?>" class="form-label">Componente</label>
                            <input type="text" class="form-control" id="componente<?php echo $diagnostico['idDiagnostico']; ?>" name="componente" value="<?php echo $diagnostico['Componente']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="valor<?php echo $diagnostico['idDiagnostico']; ?>" class="form-label">Valor</label>
                            <input type="number" step="0.01" class="form-control" id="valor<?php echo $diagnostico['idDiagnostico']; ?>" name="valor" value="<?php echo $diagnostico['Valor']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion<?php echo $diagnostico['idDiagnostico']; ?>" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion<?php echo $diagnostico['idDiagnostico']; ?>" name="descripcion" rows="3" required><?php echo $diagnostico['Descripcion']; ?></textarea>
                        </div>
                        <h5 class="mt-4">Detalles del Diagnóstico</h5>
                        <table class="table table-bordered" id="detallesTable<?php echo $diagnostico['idDiagnostico']; ?>">
                            <thead>
                                <tr>
                                    <th>Arreglo</th>
                                    <th>Cantidad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $detalles = obtenerDetallesDiagnostico($conn, $diagnostico['idDiagnostico']);
                                foreach ($detalles as $detalle):
                                ?>
                                <tr>
                                    <td>
                                        <select class="form-select arreglo-select" name="arreglo_id[]" required>
                                            <?php foreach ($arreglos as $arreglo): ?>
                                                <option value="<?php echo $arreglo['idArreglo']; ?>" data-marca="<?php echo $arreglo['Marca_idMarca']; ?>" data-usuario="<?php echo $arreglo['Usuario_idUsuario']; ?>" <?php echo ($arreglo['idArreglo'] == $detalle['Arreglo_idArreglo']) ? 'selected' : ''; ?>>
                                                    <?php echo $arreglo['Nombre_Arreglo'] . ' - ' . $arreglo['Nombre_Marca'] . ' - ' . $arreglo['Primer_Nombre'] . ' ' . $arreglo['Primer_Apellido']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control cantidad-input" name="cantidad[]" value="<?php echo $detalle['Cantidad']; ?>" required></td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarDetalle(this)">Eliminar</button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-secondary" onclick="agregarDetalle(<?php echo $diagnostico['idDiagnostico']; ?>)">Agregar Detalle</button>
                        <input type="hidden" id="detalles_diagnostico<?php echo $diagnostico['idDiagnostico']; ?>" name="detalles_diagnostico" value="">
                        <div class="mt-3">
                            <button type="submit" name="actualizar_diagnostico" class="btn btn-primary">Actualizar Diagnóstico</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function agregarDetalle(diagnosticoId = '') {
            const tableId = diagnosticoId ? `detallesTable${diagnosticoId}` : 'detallesTable';
            const table = document.getElementById(tableId).getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td>
                    <select class="form-select arreglo-select" name="arreglo_id[]" required>
                        <?php foreach ($arreglos as $arreglo): ?>
                            <option value="<?php echo $arreglo['idArreglo']; ?>" data-marca="<?php echo $arreglo['Marca_idMarca']; ?>" data-usuario="<?php echo $arreglo['Usuario_idUsuario']; ?>">
                                <?php echo $arreglo['Nombre_Arreglo'] . ' - ' . $arreglo['Nombre_Marca'] . ' - ' . $arreglo['Primer_Nombre'] . ' ' . $arreglo['Primer_Apellido']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" class="form-control cantidad-input" name="cantidad[]" required></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarDetalle(this)">Eliminar</button></td>
            `;
            actualizarDetallesJSON(diagnosticoId);
        }

        function eliminarDetalle(button) {
            const row = button.closest('tr');
            const table = row.closest('table');
            row.remove();
            const diagnosticoId = table.id.replace('detallesTable', '');
            actualizarDetallesJSON(diagnosticoId);
        }

        function actualizarDetallesJSON(diagnosticoId = '') {
            const tableId = diagnosticoId ? `detallesTable${diagnosticoId}` : 'detallesTable';
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].rows;
            const detalles = [];

            for (let i = 0; i < rows.length; i++) {
                const arregloSelect = rows[i].querySelector('.arreglo-select');
                const arregloId = arregloSelect.value;
                const marcaId = arregloSelect.options[arregloSelect.selectedIndex].dataset.marca;
                const usuarioId = arregloSelect.options[arregloSelect.selectedIndex].dataset.usuario;
                const cantidad = rows[i].querySelector('.cantidad-input').value;
                detalles.push({ arreglo_id: arregloId, marca_id: marcaId, usuario_id: usuarioId, cantidad: cantidad });
            }

            const inputId = diagnosticoId ? `detalles_diagnostico${diagnosticoId}` : 'detalles_diagnostico';
            document.getElementById(inputId).value = JSON.stringify(detalles);
        }

        // Agregar event listeners para los cambios en los detalles
        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('arreglo-select') || event.target.classList.contains('cantidad-input')) {
                const table = event.target.closest('table');
                const diagnosticoId = table.id.replace('detallesTable', '');
                actualizarDetallesJSON(diagnosticoId);
            }
        });

        // Inicializar los JSON de detalles al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            actualizarDetallesJSON();
            <?php foreach ($diagnosticos as $diagnostico): ?>
            actualizarDetallesJSON('<?php echo $diagnostico['idDiagnostico']; ?>');
            <?php endforeach; ?>
        });

        // Manejar la sumisión de los formularios de edición
        document.querySelectorAll('form[id^="editarDiagnosticoForm"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Manejar la respuesta, actualizar la página o mostrar un mensaje
                    console.log(data);
                    // Cerrar el modal
                    const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                    modal.hide();
                    // Opcionalmente, actualizar la tabla o recargar la página
                    location.reload();
                })
                .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>
</html>

