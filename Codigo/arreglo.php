<?php
// arreglo_crud.php

include('auth.php');
include("conexion.php");

$isAdmin = isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador';
$mensaje = '';

function arregloTieneEstado($conn)
{
    $result = $conn->query("SHOW COLUMNS FROM Arreglo LIKE 'Estado_Arreglo'");
    return $result && $result->num_rows > 0;
}

$estadoColumn = arregloTieneEstado($conn);
$estadosArreglo = ['Pendiente', 'En Progreso', 'Finalizado', 'Entregado'];

// Función para obtener todos los arreglos
function obtenerArreglos($conn, $estadoColumn)
{
    if ($estadoColumn) {
        $sql = "SELECT a.*, COALESCE(a.Estado_Arreglo, 'Pendiente') AS Estado_Arreglo, m.Nombre_Marca, u.Primer_Nombre, u.Primer_Apellido, 
                       (SELECT CONCAT(c.Primer_Nombre, ' ', c.Primer_Apellido)
                        FROM Detalle_Arreglo da
                        JOIN Cliente c ON da.Cliente_idCliente = c.idCliente
                        WHERE da.Arreglo_idArreglo = a.idArreglo
                        LIMIT 1) AS Nombre_Cliente
                FROM Arreglo a 
                JOIN Marca m ON a.Marca_idMarca = m.idMarca 
                JOIN Tecnico u ON a.Tecnico_idTecnico = u.idTecnico";
    } else {
        $sql = "SELECT a.*, m.Nombre_Marca, u.Primer_Nombre, u.Primer_Apellido, 
                       (SELECT CONCAT(c.Primer_Nombre, ' ', c.Primer_Apellido)
                        FROM Detalle_Arreglo da
                        JOIN Cliente c ON da.Cliente_idCliente = c.idCliente
                        WHERE da.Arreglo_idArreglo = a.idArreglo
                        LIMIT 1) AS Nombre_Cliente
                FROM Arreglo a 
                JOIN Marca m ON a.Marca_idMarca = m.idMarca 
                JOIN Tecnico u ON a.Tecnico_idTecnico = u.idTecnico";
    }
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

// Función para obtener tecnicos
function obtenerTecnicos($conn)
{
    $sql = "SELECT * FROM Tecnico";
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

$mensaje = '';

// Crear arreglo
if (isset($_POST['crear_arreglo'])) {
    if (!$isAdmin) {
        $mensaje = "<div class='alert alert-danger'>Solo el administrador puede crear y asignar arreglos.</div>";
    } else {
        $tipo_arreglo = $_POST['tipo_arreglo'];
        $nombre_arreglo = $_POST['nombre_arreglo'];
        $descripcion_cliente = $_POST['descripcion_cliente'];
        $valor_pago = $_POST['valor_pago'];
        $fecha_recibido = $_POST['fecha_recibido'];
        $fecha_entrega = $_POST['fecha_entrega'];
        $marca_id = $_POST['marca_id'];
        $tecnico_id = $_POST['tecnico_id'];
        $estado_arreglo = $_POST['estado_arreglo'] ?? 'Pendiente';

        if ($estadoColumn) {
            $stmt = $conn->prepare("INSERT INTO Arreglo (Tipo_Arreglo, Nombre_Arreglo, Descripcion_Cliente, Valor_Pago, Estado_Arreglo, Fecha_Recibido, Fecha_Entrega, Marca_idMarca, Tecnico_idTecnico) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdsssii", $tipo_arreglo, $nombre_arreglo, $descripcion_cliente, $valor_pago, $estado_arreglo, $fecha_recibido, $fecha_entrega, $marca_id, $tecnico_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO Arreglo (Tipo_Arreglo, Nombre_Arreglo, Descripcion_Cliente, Valor_Pago, Fecha_Recibido, Fecha_Entrega, Marca_idMarca, Tecnico_idTecnico) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdssii", $tipo_arreglo, $nombre_arreglo, $descripcion_cliente, $valor_pago, $fecha_recibido, $fecha_entrega, $marca_id, $tecnico_id);
        }

        if ($stmt && $stmt->execute()) {
            $arreglo_id = $conn->insert_id;
            $detalles = json_decode($_POST['detalles_arreglo'], true);
            foreach ($detalles as $detalle) {
                $cliente_id = $detalle['cliente_id'];
                $cantidad = $detalle['cantidad'];
                $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Arreglo (Cliente_idCliente, Arreglo_idArreglo, Arreglo_Marca_idMarca, Arreglo_Tecnico_idTecnico, Cantidad) VALUES (?, ?, ?, ?, ?)");
                $stmt_detalle->bind_param("iiiii", $cliente_id, $arreglo_id, $marca_id, $tecnico_id, $cantidad);
                $stmt_detalle->execute();
            }
            $mensaje = "<div class='alert alert-success'>Arreglo creado con éxito</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error: " . ($stmt ? $stmt->error : 'Error en la preparación de la consulta') . "</div>";
        }
    }
}

// Actualizar arreglo
if (isset($_POST['actualizar_arreglo'])) {
    if (!$isAdmin) {
        $mensaje = "<div class='alert alert-danger'>Solo el administrador puede actualizar arreglos.</div>";
    } else {
        $id = $_POST['id_arreglo'];
        $tipo_arreglo = $_POST['tipo_arreglo'];
        $nombre_arreglo = $_POST['nombre_arreglo'];
        $descripcion_cliente = $_POST['descripcion_cliente'];
        $valor_pago = $_POST['valor_pago'];
        $fecha_recibido = $_POST['fecha_recibido'];
        $fecha_entrega = $_POST['fecha_entrega'];
        $marca_id = $_POST['marca_id'];
        $tecnico_id = $_POST['tecnico_id'];
        $estado_arreglo = $_POST['estado_arreglo'] ?? 'Pendiente';

        if ($estadoColumn) {
            $stmt = $conn->prepare("UPDATE Arreglo SET Tipo_Arreglo=?, Nombre_Arreglo=?, Descripcion_Cliente=?, Valor_Pago=?, Estado_Arreglo=?, Fecha_Recibido=?, Fecha_Entrega=?, Marca_idMarca=?, Tecnico_idTecnico=? WHERE idArreglo=?");
            $stmt->bind_param("sssdsssiii", $tipo_arreglo, $nombre_arreglo, $descripcion_cliente, $valor_pago, $estado_arreglo, $fecha_recibido, $fecha_entrega, $marca_id, $tecnico_id, $id);
        } else {
            $stmt = $conn->prepare("UPDATE Arreglo SET Tipo_Arreglo=?, Nombre_Arreglo=?, Descripcion_Cliente=?, Valor_Pago=?, Fecha_Recibido=?, Fecha_Entrega=?, Marca_idMarca=?, Tecnico_idTecnico=? WHERE idArreglo=?");
            $stmt->bind_param("sssdssiii", $tipo_arreglo, $nombre_arreglo, $descripcion_cliente, $valor_pago, $fecha_recibido, $fecha_entrega, $marca_id, $tecnico_id, $id);
        }

        if ($stmt && $stmt->execute()) {
            // Eliminar detalles existentes
            $stmt_delete = $conn->prepare("DELETE FROM Detalle_Arreglo WHERE Arreglo_idArreglo=?");
            $stmt_delete->bind_param("i", $id);
            $stmt_delete->execute();

            // Insertar nuevos detalles
            $detalles = json_decode($_POST['detalles_arreglo'], true);
            foreach ($detalles as $detalle) {
                $cliente_id = $detalle['cliente_id'];
                $cantidad = $detalle['cantidad'];
                $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Arreglo (Cliente_idCliente, Arreglo_idArreglo, Arreglo_Marca_idMarca, Arreglo_Tecnico_idTecnico, Cantidad) VALUES (?, ?, ?, ?, ?)");
                $stmt_detalle->bind_param("iiiii", $cliente_id, $id, $marca_id, $tecnico_id, $cantidad);
                $stmt_detalle->execute();
            }
            $mensaje = "<div class='alert alert-success'>Arreglo actualizado con éxito</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error: " . ($stmt ? $stmt->error : 'Error en la preparación de la consulta') . "</div>";
        }
    }
}

// Actualizar solo el estado del arreglo
if (isset($_POST['actualizar_estado_arreglo'])) {
    $id = $_POST['id_arreglo'];
    $estado_arreglo = $_POST['estado_arreglo'] ?? 'Pendiente';

    if ($estadoColumn) {
        $stmt = $conn->prepare("UPDATE Arreglo SET Estado_Arreglo=? WHERE idArreglo=?");
        $stmt->bind_param("si", $estado_arreglo, $id);
        if ($stmt->execute()) {
            $mensaje = "<div class='alert alert-success'>Estado del arreglo actualizado correctamente.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>No existe el campo Estado_Arreglo en la base de datos.</div>";
    }
}

// Eliminar arreglo
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    if (!$isAdmin) {
        $mensaje = "<div class='alert alert-danger'>Solo el administrador puede eliminar arreglos.</div>";
    } else {
        $stmt_delete_detalles = $conn->prepare("DELETE FROM Detalle_Arreglo WHERE Arreglo_idArreglo=?");
        $stmt_delete_detalles->bind_param("i", $id);
        $stmt_delete_detalles->execute();

        $stmt_delete_diagnostico = $conn->prepare("DELETE FROM Detalle_Diagnostico WHERE Arreglo_idArreglo=?");
        $stmt_delete_diagnostico->bind_param("i", $id);
        $stmt_delete_diagnostico->execute();

        $stmt_delete_arreglo = $conn->prepare("DELETE FROM Arreglo WHERE idArreglo=?");
        $stmt_delete_arreglo->bind_param("i", $id);
        if ($stmt_delete_arreglo->execute()) {
            $mensaje = "<div class='alert alert-success'>Arreglo eliminado con éxito. Primero se eliminaron los detalles asociados.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error: " . $stmt_delete_arreglo->error . "</div>";
        }
    }
}

$arreglos = obtenerArreglos($conn, $estadoColumn);
$marcas = obtenerMarcas($conn);
$tecnicos = obtenerTecnicos($conn);
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
    <?php echo $mensaje; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Listar Arreglos</h2>
                <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#crearArregloModal">
                    Crear Arreglo
                </button>
                <?php endif; ?>
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
                                <th>Estado</th>
                                <th>Marca</th>
                                <th>Tecnico</th>
                                <th>Cliente</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($arreglos as $arreglo): ?>
                                <tr>
                                    <td><?php echo $arreglo['idArreglo']; ?></td>
                                    <td><?php echo $arreglo['Tipo_Arreglo']; ?></td>
                                    <td><?php echo $arreglo['Nombre_Arreglo']; ?></td>
                                    <td><?php echo '$ ' . number_format($arreglo['Valor_Pago'], 0); ?></td>
                                    <td><?php echo $arreglo['Fecha_Recibido']; ?></td>
                                    <td><?php echo $arreglo['Fecha_Entrega']; ?></td>
                                    <td><?php echo htmlspecialchars($arreglo['Estado_Arreglo'] ?? 'Pendiente'); ?></td>
                                    <td><?php echo $arreglo['Nombre_Marca']; ?></td>
                                    <td><?php echo $arreglo['Primer_Nombre'] . ' ' . $arreglo['Primer_Apellido']; ?></td>
                                    <td><?php echo $arreglo['Nombre_Cliente']; ?></td>
                                    <td>
                                        <?php if ($isAdmin): ?>
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#editarArregloModal<?php echo $arreglo['idArreglo']; ?>">
                                                Editar
                                            </button>
                                            <a href="?eliminar=<?php echo $arreglo['idArreglo']; ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('¿Estás seguro? Primero se eliminarán los detalles asociados a este arreglo.')">Eliminar</a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal"
                                                data-bs-target="#editarArregloModal<?php echo $arreglo['idArreglo']; ?>">
                                                Actualizar estado
                                            </button>
                                        <?php endif; ?>
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
                                <label for="tipo_arreglo" class="form-label">Tipo de dispositivo</label>
                                <input type="text" class="form-control" id="tipo_arreglo" name="tipo_arreglo" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nombre_arreglo" class="form-label">Daño / actualizacion arreglo</label>
                                <input type="text" class="form-control" id="nombre_arreglo" name="nombre_arreglo"
                                    required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion_cliente" class="form-label">Descripción del dispositivo</label>
                            <textarea class="form-control" id="descripcion_cliente" name="descripcion_cliente" rows="3"
                                required></textarea>
                        </div>
                        <?php if ($estadoColumn): ?>
                        <div class="mb-3">
                            <label for="estado_arreglo" class="form-label">Estado del arreglo</label>
                            <select class="form-select" id="estado_arreglo" name="estado_arreglo" required>
                                <?php foreach ($estadosArreglo as $estado): ?>
                                    <option value="<?php echo htmlspecialchars($estado); ?>"><?php echo htmlspecialchars($estado); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
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
                                <label for="tecnico_id" class="form-label">Tecnico</label>
                                <select class="form-select" id="tecnico_id" name="tecnico_id" required>
                                    <?php foreach ($tecnicos as $tecnico): ?>
                                        <option value="<?php echo $tecnico['idTecnico']; ?>">
                                            <?php echo $tecnico['Primer_Nombre'] . ' ' . $tecnico['Primer_Apellido']; ?>
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
                            <?php if ($isAdmin): ?>
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
                                <?php if ($estadoColumn): ?>
                                <div class="mb-3">
                                    <label for="estado_arreglo<?php echo $arreglo['idArreglo']; ?>" class="form-label">Estado del arreglo</label>
                                    <select class="form-select" id="estado_arreglo<?php echo $arreglo['idArreglo']; ?>" name="estado_arreglo" required>
                                        <?php foreach ($estadosArreglo as $estado): ?>
                                            <option value="<?php echo htmlspecialchars($estado); ?>" <?php echo ($estado === ($arreglo['Estado_Arreglo'] ?? 'Pendiente')) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($estado); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
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
                                        <label for="tecnico_id<?php echo $arreglo['idArreglo']; ?>"
                                            class="form-label">Tecnico</label>
                                        <select class="form-select" id="tecnico_id<?php echo $arreglo['idArreglo']; ?>"
                                            name="tecnico_id" required>
                                            <?php foreach ($tecnicos as $tecnico): ?>
                                                <option value="<?php echo $tecnico['idTecnico']; ?>" <?php echo ($tecnico['idTecnico'] == $arreglo['Tecnico_idTecnico']) ? 'selected' : ''; ?>>
                                                    <?php echo $tecnico['Primer_Nombre'] . ' ' . $tecnico['Primer_Apellido']; ?>
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
                            <?php else: ?>
                                <?php if ($estadoColumn): ?>
                                <div class="mb-3">
                                    <label for="estado_arreglo<?php echo $arreglo['idArreglo']; ?>" class="form-label">Estado del arreglo</label>
                                    <select class="form-select" id="estado_arreglo<?php echo $arreglo['idArreglo']; ?>" name="estado_arreglo" required>
                                        <?php foreach ($estadosArreglo as $estado): ?>
                                            <option value="<?php echo htmlspecialchars($estado); ?>" <?php echo ($estado === ($arreglo['Estado_Arreglo'] ?? 'Pendiente')) ? 'selected' : ''; ?>><?php echo htmlspecialchars($estado); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-warning">
                                    El estado del arreglo no está disponible en la base de datos. Actualice la tabla Arreglo para incluir el campo <strong>Estado_Arreglo</strong>.
                                </div>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <button type="submit" name="actualizar_estado_arreglo" class="btn btn-primary">Actualizar Estado</button>
                                </div>
                            <?php endif; ?>
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