<?php
// arreglo_crud.php

// Conexión a la base de datos
include("conexion.php");

// Función para obtener todos los arreglos
function obtenerArreglos($conn)
{
    $sql = "SELECT a.*, m.Nombre_Marca, u.Primer_Nombre, u.Primer_Apellido 
            FROM Arreglo a 
            JOIN Marca m ON a.Marca_idMarca = m.idMarca 
            JOIN Usuario u ON a.Usuario_idUsuario = u.idUsuario";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener detalles de un arreglo
function obtenerDetallesArreglo($conn, $idArreglo)
{
    $sql = "SELECT da.*, c.Primer_Nombre, c.Primer_Apellido 
            FROM Detalle_Arreglo da 
            JOIN Cliente c ON da.Cliente_idCliente = c.idCliente 
            WHERE da.Arreglo_idArreglo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idArreglo);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener marcas
function obtenerMarcas($conn)
{
    $sql = "SELECT * FROM Marca";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener usuarios
function obtenerUsuarios($conn)
{
    $sql = "SELECT * FROM Usuario";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener clientes
function obtenerClientes($conn)
{
    $sql = "SELECT * FROM Cliente";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Crear arreglo
if (isset($_POST['crear_arreglo'])) {
    $tipo_arreglo = $_POST['tipo_arreglo'];
    $nombre_arreglo = $_POST['nombre_arreglo'];
    $descripcion_cliente = $_POST['descripcion_cliente'];
    $valor_pago = $_POST['valor_pago'];
    $fecha_recibido = $_POST['fecha_recibido'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $marca_id = $_POST['marca_id'];
    $usuario_id = $_POST['usuario_id'];

    $stmt = $conn->prepare("INSERT INTO Arreglo (Tipo_Arreglo, Nombre_Arreglo, Descripcion_Cliente, Valor_Pago, Fecha_Recibido, Fecha_Entrega, Marca_idMarca, Usuario_idUsuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdssii", $tipo_arreglo, $nombre_arreglo, $descripcion_cliente, $valor_pago, $fecha_recibido, $fecha_entrega, $marca_id, $usuario_id);

    if ($stmt->execute()) {
        $arreglo_id = $conn->insert_id;
        $detalles = json_decode($_POST['detalles_arreglo'], true);
        foreach ($detalles as $detalle) {
            $cliente_id = $detalle['cliente_id'];
            $cantidad = $detalle['cantidad'];
            $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Arreglo (Cliente_idCliente, Arreglo_idArreglo, Arreglo_Marca_idMarca, Arreglo_Usuario_idUsuario, Cantidad) VALUES (?, ?, ?, ?, ?)");
            $stmt_detalle->bind_param("iiiii", $cliente_id, $arreglo_id, $marca_id, $usuario_id, $cantidad);
            $stmt_detalle->execute();
        }
        echo "<div class='alert alert-success'>Arreglo creado con éxito</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
}

// Actualizar arreglo
if (isset($_POST['actualizar_arreglo'])) {
    $id = $_POST['id_arreglo'];
    $tipo_arreglo = $_POST['tipo_arreglo'];
    $nombre_arreglo = $_POST['nombre_arreglo'];
    $descripcion_cliente = $_POST['descripcion_cliente'];
    $valor_pago = $_POST['valor_pago'];
    $fecha_recibido = $_POST['fecha_recibido'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $marca_id = $_POST['marca_id'];
    $usuario_id = $_POST['usuario_id'];

    $stmt = $conn->prepare("UPDATE Arreglo SET Tipo_Arreglo=?, Nombre_Arreglo=?, Descripcion_Cliente=?, Valor_Pago=?, Fecha_Recibido=?, Fecha_Entrega=?, Marca_idMarca=?, Usuario_idUsuario=? WHERE idArreglo=?");
    $stmt->bind_param("sssdssiii", $tipo_arreglo, $nombre_arreglo, $descripcion_cliente, $valor_pago, $fecha_recibido, $fecha_entrega, $marca_id, $usuario_id, $id);

    if ($stmt->execute()) {
        // Eliminar detalles existentes
        $stmt_delete = $conn->prepare("DELETE FROM Detalle_Arreglo WHERE Arreglo_idArreglo=?");
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();

        // Insertar nuevos detalles
        $detalles = json_decode($_POST['detalles_arreglo'], true);
        foreach ($detalles as $detalle) {
            $cliente_id = $detalle['cliente_id'];
            $cantidad = $detalle['cantidad'];
            $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Arreglo (Cliente_idCliente, Arreglo_idArreglo, Arreglo_Marca_idMarca, Arreglo_Usuario_idUsuario, Cantidad) VALUES (?, ?, ?, ?, ?)");
            $stmt_detalle->bind_param("iiiii", $cliente_id, $id, $marca_id, $usuario_id, $cantidad);
            $stmt_detalle->execute();
        }
        echo "<div class='alert alert-success'>Arreglo actualizado con éxito</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
}

// Eliminar arreglo
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt_delete_detalles = $conn->prepare("DELETE FROM Detalle_Arreglo WHERE Arreglo_idArreglo=?");
    $stmt_delete_detalles->bind_param("i", $id);
    $stmt_delete_detalles->execute();

    $stmt_delete_arreglo = $conn->prepare("DELETE FROM Arreglo WHERE idArreglo=?");
    $stmt_delete_arreglo->bind_param("i", $id);
    if ($stmt_delete_arreglo->execute()) {
        echo "<div class='alert alert-success'>Arreglo eliminado con éxito</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt_delete_arreglo->error . "</div>";
    }
}

$arreglos = obtenerArreglos($conn);
$marcas = obtenerMarcas($conn);
$usuarios = obtenerUsuarios($conn);
$clientes = obtenerClientes($conn);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arreglos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Listar Arreglos</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#crearArregloModal">
                    Crear Arreglo
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Nombre</th>
                                <th>Valor</th>
                                <th>Fecha Recibido</th>
                                <th>Fecha Entrega</th>
                                <th>Marca</th>
                                <th>Usuario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($arreglos as $arreglo): ?>
                                <tr>
                                    <td><?php echo $arreglo['idArreglo']; ?></td>
                                    <td><?php echo $arreglo['Tipo_Arreglo']; ?></td>
                                    <td><?php echo $arreglo['Nombre_Arreglo']; ?></td>
                                    <td><?php echo $arreglo['Valor_Pago']; ?></td>
                                    <td><?php echo $arreglo['Fecha_Recibido']; ?></td>
                                    <td><?php echo $arreglo['Fecha_Entrega']; ?></td>
                                    <td><?php echo $arreglo['Nombre_Marca']; ?></td>
                                    <td><?php echo $arreglo['Primer_Nombre'] . ' ' . $arreglo['Primer_Apellido']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#editarArregloModal<?php echo $arreglo['idArreglo']; ?>">
                                            Editar
                                        </button>
                                        <a href="?eliminar=<?php echo $arreglo['idArreglo']; ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('¿Estás seguro de que quieres eliminar este arreglo?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear arreglo -->
    <div class="modal fade" id="crearArregloModal" tabindex="-1" aria-labelledby="crearArregloModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearArregloModalLabel">Crear Nuevo Arreglo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="crearArregloForm" action="" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tipo_arreglo" class="form-label">Tipo de Arreglo</label>
                                <input type="text" class="form-control" id="tipo_arreglo" name="tipo_arreglo" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nombre_arreglo" class="form-label">Nombre del Arreglo</label>
                                <input type="text" class="form-control" id="nombre_arreglo" name="nombre_arreglo"
                                    required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion_cliente" class="form-label">Descripción del Cliente</label>
                            <textarea class="form-control" id="descripcion_cliente" name="descripcion_cliente" rows="3"
                                required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="valor_pago" class="form-label">Valor de Pago</label>
                                <input type="number" step="0.01" class="form-control" id="valor_pago" name="valor_pago"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_recibido" class="form-label">Fecha de Recibido</label>
                                <input type="date" class="form-control" id="fecha_recibido" name="fecha_recibido"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_entrega" class="form-label">Fecha de Entrega</label>
                                <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega"
                                    required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="marca_id" class="form-label">Marca</label>
                                <select class="form-select" id="marca_id" name="marca_id" required>
                                    <?php foreach ($marcas as $marca): ?>
                                        <option value="<?php echo $marca['idMarca']; ?>">
                                            <?php echo $marca['Nombre_Marca']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="usuario_id" class="form-label">Usuario</label>
                                <select class="form-select" id="usuario_id" name="usuario_id" required>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?php echo $usuario['idUsuario']; ?>">
                                            <?php echo $usuario['Primer_Nombre'] . ' ' . $usuario['Primer_Apellido']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <h5 class="mt-4">Detalles del Arreglo</h5>
                        <table class="table table-bordered" id="detallesTable">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Cantidad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los detalles se agregarán aquí dinámicamente -->
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-secondary" onclick="agregarDetalle()">Agregar
                            Detalle</button>
                        <input type="hidden" id="detalles_arreglo" name="detalles_arreglo" value="[]">
                        <div class="mt-3">
                            <button type="submit" name="crear_arreglo" class="btn btn-primary">Crear Arreglo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para editar arreglo -->
    <?php foreach ($arreglos as $arreglo): ?>
        <div class="modal fade" id="editarArregloModal<?php echo $arreglo['idArreglo']; ?>" tabindex="-1"
            aria-labelledby="editarArregloModalLabel<?php echo $arreglo['idArreglo']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarArregloModalLabel<?php echo $arreglo['idArreglo']; ?>">Editar
                            Arreglo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editarArregloForm<?php echo $arreglo['idArreglo']; ?>" action="" method="post">
                            <input type="hidden" name="id_arreglo" value="<?php echo $arreglo['idArreglo']; ?>">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="tipo_arreglo<?php echo $arreglo['idArreglo']; ?>" class="form-label">Tipo de
                                        Arreglo</label>
                                    <input type="text" class="form-control"
                                        id="tipo_arreglo<?php echo $arreglo['idArreglo']; ?>" name="tipo_arreglo"
                                        value="<?php echo $arreglo['Tipo_Arreglo']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="nombre_arreglo<?php echo $arreglo['idArreglo']; ?>"
                                        class="form-label">Nombre del Arreglo</label>
                                    <input type="text" class="form-control"
                                        id="nombre_arreglo<?php echo $arreglo['idArreglo']; ?>" name="nombre_arreglo"
                                        value="<?php echo $arreglo['Nombre_Arreglo']; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion_cliente<?php echo $arreglo['idArreglo']; ?>"
                                    class="form-label">Descripción del Cliente</label>
                                <textarea class="form-control" id="descripcion_cliente<?php echo $arreglo['idArreglo']; ?>"
                                    name="descripcion_cliente" rows="3"
                                    required><?php echo $arreglo['Descripcion_Cliente']; ?></textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="valor_pago<?php echo $arreglo['idArreglo']; ?>" class="form-label">Valor de
                                        Pago</label>
                                    <input type="number" step="0.01" class="form-control"
                                        id="valor_pago<?php echo $arreglo['idArreglo']; ?>" name="valor_pago"
                                        value="<?php echo $arreglo['Valor_Pago']; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="fecha_recibido<?php echo $arreglo['idArreglo']; ?>" class="form-label">Fecha
                                        de Recibido</label>
                                    <input type="date" class="form-control"
                                        id="fecha_recibido<?php echo $arreglo['idArreglo']; ?>" name="fecha_recibido"
                                        value="<?php echo $arreglo['Fecha_Recibido']; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="fecha_entrega<?php echo $arreglo['idArreglo']; ?>" class="form-label">Fecha
                                        de Entrega</label>
                                    <input type="date" class="form-control"
                                        id="fecha_entrega<?php echo $arreglo['idArreglo']; ?>" name="fecha_entrega"
                                        value="<?php echo $arreglo['Fecha_Entrega']; ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="marca_id<?php echo $arreglo['idArreglo']; ?>"
                                        class="form-label">Marca</label>
                                    <select class="form-select" id="marca_id<?php echo $arreglo['idArreglo']; ?>"
                                        name="marca_id" required>
                                        <?php foreach ($marcas as $marca): ?>
                                            <option value="<?php echo $marca['idMarca']; ?>" <?php echo ($marca['idMarca'] == $arreglo['Marca_idMarca']) ? 'selected' : ''; ?>>
                                                <?php echo $marca['Nombre_Marca']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="usuario_id<?php echo $arreglo['idArreglo']; ?>"
                                        class="form-label">Usuario</label>
                                    <select class="form-select" id="usuario_id<?php echo $arreglo['idArreglo']; ?>"
                                        name="usuario_id" required>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <option value="<?php echo $usuario['idUsuario']; ?>" <?php echo ($usuario['idUsuario'] == $arreglo['Usuario_idUsuario']) ? 'selected' : ''; ?>>
                                                <?php echo $usuario['Primer_Nombre'] . ' ' . $usuario['Primer_Apellido']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <h5 class="mt-4">Detalles del Arreglo</h5>
                            <table class="table table-bordered" id="detallesTable<?php echo $arreglo['idArreglo']; ?>">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Cantidad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $detalles = obtenerDetallesArreglo($conn, $arreglo['idArreglo']);
                                    foreach ($detalles as $detalle):
                                        ?>
                                        <tr>
                                            <td>
                                                <select class="form-select cliente-select" name="cliente_id[]" required>
                                                    <?php foreach ($clientes as $cliente): ?>
                                                        <option value="<?php echo $cliente['idCliente']; ?>" <?php echo ($cliente['idCliente'] == $detalle['Cliente_idCliente']) ? 'selected' : ''; ?>>
                                                            <?php echo $cliente['Primer_Nombre'] . ' ' . $cliente['Primer_Apellido']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="number" class="form-control cantidad-input" name="cantidad[]"
                                                    value="<?php echo $detalle['Cantidad']; ?>" required></td>
                                            <td><button type="button" class="btn btn-danger btn-sm"
                                                    onclick="eliminarDetalle(this)">Eliminar</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-secondary"
                                onclick="agregarDetalle(<?php echo $arreglo['idArreglo']; ?>)">Agregar Detalle</button>
                            <input type="hidden" id="detalles_arreglo<?php echo $arreglo['idArreglo']; ?>"
                                name="detalles_arreglo" value="">
                            <div class="mt-3">
                                <button type="submit" name="actualizar_arreglo" class="btn btn-primary">Actualizar
                                    Arreglo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function agregarDetalle(arregloId = '') {
            const tableId = arregloId ? `detallesTable${arregloId}` : 'detallesTable';
            const table = document.getElementById(tableId).getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td>
                    <select class="form-select cliente-select" name="cliente_id[]" required>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['idCliente']; ?>"><?php echo $cliente['Primer_Nombre'] . ' ' . $cliente['Primer_Apellido']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" class="form-control cantidad-input" name="cantidad[]" required></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarDetalle(this)">Eliminar</button></td>
            `;
            actualizarDetallesJSON(arregloId);
        }

        function eliminarDetalle(button) {
            const row = button.closest('tr');
            const table = row.closest('table');
            row.remove();
            const arregloId = table.id.replace('detallesTable', '');
            actualizarDetallesJSON(arregloId);
        }

        function actualizarDetallesJSON(arregloId = '') {
            const tableId = arregloId ? `detallesTable${arregloId}` : 'detallesTable';
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].rows;
            const detalles = [];

            for (let i = 0; i < rows.length; i++) {
                const clienteId = rows[i].querySelector('.cliente-select').value;
                const cantidad = rows[i].querySelector('.cantidad-input').value;
                detalles.push({ cliente_id: clienteId, cantidad: cantidad });
            }

            const inputId = arregloId ? `detalles_arreglo${arregloId}` : 'detalles_arreglo';
            document.getElementById(inputId).value = JSON.stringify(detalles);
        }

        // Agregar event listeners para los cambios en los detalles
        document.addEventListener('change', function (event) {
            if (event.target.classList.contains('cliente-select') || event.target.classList.contains('cantidad-input')) {
                const table = event.target.closest('table');
                const arregloId = table.id.replace('detallesTable', '');
                actualizarDetallesJSON(arregloId);
            }
        });

        // Inicializar los JSON de detalles al cargar la página
        document.addEventListener('DOMContentLoaded', function () {
            actualizarDetallesJSON();
            <?php foreach ($arreglos as $arreglo): ?>
                actualizarDetallesJSON('<?php echo $arreglo['idArreglo']; ?>');
            <?php endforeach; ?>
        });

        // Manejar la sumisión de los formularios de edición
        document.querySelectorAll('form[id^="editarArregloForm"]').forEach(form => {
            form.addEventListener('submit', function (e) {
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