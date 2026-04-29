<?php
/**
 * Gestionar Arreglos
 * - Admin: Crear/Editar datos (no estado), ver valor, asignar solo a tecnicos
 * - Tecnico: Solo ve sus arreglos asignados, puede actualizar estado, NO ve valor
 */

include('auth.php');
include("conexion.php");

$isAdmin = isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador';
$idTecnicoActual = $_SESSION['idTecnico'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Funciones auxiliares
function obtenerEstados($conn) {
    $result = $conn->query("SELECT * FROM Estado");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerTiposDispositivo($conn) {
    $result = $conn->query("SELECT * FROM Tipo_Dispositivo ORDER BY Nombre_Tipo");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerMarcas($conn) {
    $result = $conn->query("SELECT * FROM Marca ORDER BY Nombre_Marca");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Solo tecnicos (Rol_idRol = 2) pueden ser asignados a arreglos
function obtenerTecnicosAsignables($conn) {
    $result = $conn->query("SELECT * FROM Tecnico WHERE Rol_idRol = 2 ORDER BY Nombre_Tecnico");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerClientes($conn) {
    $result = $conn->query("SELECT * FROM Cliente ORDER BY Primer_Nombre");
    return $result->fetch_all(MYSQLI_ASSOC);
}



// Arreglos segun rol: Admin ve todos, Tecnico solo los suyos
function obtenerArreglos($conn, $isAdmin, $idTecnico) {
    $sql = "SELECT a.*, e.Nombre_Estado, m.Nombre_Marca, u.Nombre_Tecnico, td.Nombre_Tipo,
                   (SELECT CONCAT(c.Primer_Nombre, ' ', c.Primer_Apellido)
                    FROM Detalle_Arreglo da
                    JOIN Cliente c ON da.Cliente_idCliente = c.idCliente
                    WHERE da.Arreglo_idArreglo = a.idArreglo
                    LIMIT 1) AS Nombre_Cliente,
                   (SELECT c.Telefono
                    FROM Detalle_Arreglo da
                    JOIN Cliente c ON da.Cliente_idCliente = c.idCliente
                    WHERE da.Arreglo_idArreglo = a.idArreglo
                    LIMIT 1) AS Telefono_Cliente,
                   (SELECT da.Cliente_idCliente
                    FROM Detalle_Arreglo da
                    WHERE da.Arreglo_idArreglo = a.idArreglo
                    LIMIT 1) AS Cliente_idCliente
            FROM Arreglo a 
            JOIN Estado e ON a.Estado_idEstado = e.idEstado
            JOIN Marca m ON a.Marca_idMarca = m.idMarca 
            JOIN Tecnico u ON a.Tecnico_idTecnico = u.idTecnico
            JOIN Tipo_Dispositivo td ON a.Tipo_Dispositivo_idTipo = td.idTipoDispositivo";
    
    // Tecnico solo ve sus arreglos asignados
    if (!$isAdmin) {
        $sql .= " WHERE a.Tecnico_idTecnico = " . (int)$idTecnico;
    }
    
    $sql .= " ORDER BY a.idArreglo DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Crear arreglo (Solo Admin) - AHORA CON PRODUCTOS DEL INVENTARIO
if (isset($_POST['crear_arreglo'])) {
    if (!$isAdmin) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Solo el administrador puede crear arreglos.</div>";
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $tipo_dispositivo_id = (int)$_POST['tipo_dispositivo_id'];
        $nombre_arreglo = trim($_POST['nombre_arreglo']);
        $descripcion_cliente = trim($_POST['descripcion_cliente']);
        $valor_pago = $_POST['valor_pago'];
        $fecha_recibido = $_POST['fecha_recibido'];
        $fecha_entrega = $_POST['fecha_entrega'];
        $marca_id = (int)$_POST['marca_id'];
        $tecnico_id = (int)$_POST['tecnico_id'];
        $estado_id = 1; // Siempre inicia en "En diagnostico"
        $cliente_id = (int)$_POST['cliente_id'];
        
        // Productos seleccionados del inventario eliminados - módulo de inventario no disponible

        $conn->begin_transaction();
        try {
            // Crear el arreglo
            $stmt = $conn->prepare("INSERT INTO Arreglo (Tipo_Dispositivo_idTipo, Nombre_Arreglo, Descripcion_Cliente, Valor_Pago, Fecha_Recibido, Fecha_Entrega, Marca_idMarca, Tecnico_idTecnico, Estado_idEstado, Fecha_Cambio_Estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issdssiii", $tipo_dispositivo_id, $nombre_arreglo, $descripcion_cliente, $valor_pago, $fecha_recibido, $fecha_entrega, $marca_id, $tecnico_id, $estado_id);
            $stmt->execute();
            $arreglo_id = $conn->insert_id;
            $stmt->close();

            // Crear detalle arreglo (cliente)
            $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Arreglo (Cliente_idCliente, Arreglo_idArreglo) VALUES (?, ?)");
            $stmt_detalle->bind_param("ii", $cliente_id, $arreglo_id);
            $stmt_detalle->execute();
            $stmt_detalle->close();

            $conn->commit();
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Arreglo creado con exito.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
    header("Location: arreglo.php");
    exit();
}

// Actualizar arreglo (Solo Admin, sin estado)
if (isset($_POST['actualizar_arreglo'])) {
    if (!$isAdmin) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Solo el administrador puede actualizar arreglos.</div>";
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int)$_POST['id_arreglo'];
        $tipo_dispositivo_id = (int)$_POST['tipo_dispositivo_id'];
        $nombre_arreglo = trim($_POST['nombre_arreglo']);
        $descripcion_cliente = trim($_POST['descripcion_cliente']);
        $valor_pago = $_POST['valor_pago'];
        $fecha_recibido = $_POST['fecha_recibido'];
        $fecha_entrega = $_POST['fecha_entrega'];
        $marca_id = (int)$_POST['marca_id'];
        $tecnico_id = (int)$_POST['tecnico_id'];
        $cliente_id = (int)$_POST['cliente_id'];

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE Arreglo SET Tipo_Dispositivo_idTipo=?, Nombre_Arreglo=?, Descripcion_Cliente=?, Valor_Pago=?, Fecha_Recibido=?, Fecha_Entrega=?, Marca_idMarca=?, Tecnico_idTecnico=? WHERE idArreglo=?");
            $stmt->bind_param("issdssiis", $tipo_dispositivo_id, $nombre_arreglo, $descripcion_cliente, $valor_pago, $fecha_recibido, $fecha_entrega, $marca_id, $tecnico_id, $id);
            $stmt->execute();
            $stmt->close();

            $stmt_delete = $conn->prepare("DELETE FROM Detalle_Arreglo WHERE Arreglo_idArreglo=?");
            $stmt_delete->bind_param("i", $id);
            $stmt_delete->execute();
            $stmt_delete->close();

            $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Arreglo (Cliente_idCliente, Arreglo_idArreglo) VALUES (?, ?)");
            $stmt_detalle->bind_param("ii", $cliente_id, $id);
            $stmt_detalle->execute();
            $stmt_detalle->close();

            $conn->commit();
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Arreglo actualizado con exito</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
    header("Location: arreglo.php");
    exit();
}


// Actualizar estado (Solo Tecnico)
if (isset($_POST['actualizar_estado_arreglo'])) {
    if ($isAdmin) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Solo el tecnico puede actualizar el estado del arreglo.</div>";
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int)$_POST['id_arreglo'];
        $estado_id = (int)$_POST['estado_id'];

        $stmt = $conn->prepare("UPDATE Arreglo SET Estado_idEstado=?, Fecha_Cambio_Estado=NOW() WHERE idArreglo=?");
        $stmt->bind_param("ii", $estado_id, $id);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Estado del arreglo actualizado correctamente.</div>";
        } else {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
    header("Location: arreglo.php");
    exit();
}

// Eliminar arreglo (Solo Admin)
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    if (!$isAdmin) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Solo el administrador puede eliminar arreglos.</div>";
    } else {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM Detalle_Diagnostico_Componente WHERE Diagnostico_idDiagnostico IN (SELECT idDiagnostico FROM Diagnostico WHERE Arreglo_idArreglo = $id)");
            $conn->query("DELETE FROM Diagnostico WHERE Arreglo_idArreglo = $id");
            $conn->query("DELETE FROM Detalle_Arreglo WHERE Arreglo_idArreglo = $id");
            $conn->query("DELETE FROM Arreglo WHERE idArreglo = $id");
            $conn->commit();
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Arreglo eliminado con exito.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
    header("Location: arreglo.php");
    exit();
}

// Mostrar mensaje
$mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

$arreglos = obtenerArreglos($conn, $isAdmin, $idTecnicoActual);
$estados = obtenerEstados($conn);
$tiposDispositivo = obtenerTiposDispositivo($conn);
$marcas = obtenerMarcas($conn);
$tecnicos = obtenerTecnicosAsignables($conn);
$clientes = obtenerClientes($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arreglos - Sistema de Arreglo de Computadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
    <style>
        .select2-container--open { z-index: 9999 !important; }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <?php echo $mensaje; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-tools"></i> Listar Arreglos <?php if (!$isAdmin): ?><small class="text-muted"></small><?php endif; ?></h2>
                <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearArregloModal">
                    <i class="bi bi-plus-lg"></i> Crear Arreglo
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo Dispositivo</th>
                                <th>Descripcion</th>
                                <?php if ($isAdmin): ?><th>Valor</th><?php endif; ?>
                                <th>Fecha Recibido</th>
                                <th>Estado</th>
                                <th>Marca</th>
                                <th>Tecnico</th>
                                <th>Cliente</th>
                                <th>Telefono</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($arreglos as $arreglo): ?>
                            <tr>
                                <td><?php echo $arreglo['idArreglo']; ?></td>
                                <td><?php echo htmlspecialchars($arreglo['Nombre_Tipo']); ?></td>
                                <td><?php echo htmlspecialchars($arreglo['Nombre_Arreglo']); ?></td>
                                <?php if ($isAdmin): ?>
                                <td>$ <?php echo number_format($arreglo['Valor_Pago'], 0); ?></td>
                                <?php endif; ?>
                                <td><?php echo $arreglo['Fecha_Recibido']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($arreglo['Nombre_Estado']) {
                                            'En diagnostico' => 'warning',
                                            'En reparacion' => 'info',
                                            'Finalizado' => 'success',
                                            'Entregado' => 'secondary',
                                            default => 'primary'
                                        };
                                    ?>">
                                        <?php echo htmlspecialchars($arreglo['Nombre_Estado']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($arreglo['Nombre_Marca']); ?></td>
                                <td><?php echo htmlspecialchars($arreglo['Nombre_Tecnico']); ?></td>
                                <td><?php echo htmlspecialchars($arreglo['Nombre_Cliente'] ?? 'Sin asignar'); ?></td>
                                <td><?php echo htmlspecialchars($arreglo['Telefono_Cliente'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($isAdmin): ?>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarArregloModal<?php echo $arreglo['idArreglo']; ?>" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?eliminar=<?php echo $arreglo['idArreglo']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estas seguro de eliminar este arreglo?')" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#estadoArregloModal<?php echo $arreglo['idArreglo']; ?>">
                                        Actualizar Estado
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

    <!-- Modal para crear arreglo (Admin) -->
    <div class="modal fade" id="crearArregloModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-lg"></i> Crear Nuevo Arreglo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post" id="formCrearArreglo">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> El arreglo se creara con estado <strong>"En diagnostico"</strong>. Solo el tecnico asignado puede cambiar el estado.
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Dispositivo (*)</label>
                                <select class="form-select" name="tipo_dispositivo_id" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($tiposDispositivo as $tipo): ?>
                                    <option value="<?php echo $tipo['idTipoDispositivo']; ?>">
                                        <?php echo htmlspecialchars($tipo['Nombre_Tipo']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Marca (*)</label>
                                <select class="form-select" name="marca_id" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($marcas as $marca): ?>
                                    <option value="<?php echo $marca['idMarca']; ?>">
                                        <?php echo htmlspecialchars($marca['Nombre_Marca']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Cliente (*)</label>
                                <select class="form-select" name="cliente_id" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['idCliente']; ?>">
                                        <?php echo htmlspecialchars($cliente['Primer_Nombre'] . ' ' . $cliente['Primer_Apellido'] . ' - Tel: ' . $cliente['Telefono']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tecnico Asignado (*)</label>
                                <select class="form-select" name="tecnico_id" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($tecnicos as $tecnico): ?>
                                    <option value="<?php echo $tecnico['idTecnico']; ?>">
                                        <?php echo htmlspecialchars($tecnico['Nombre_Tecnico']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Solo se muestran usuarios con rol Tecnico</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion del problema (*)</label>
                            <input type="text" class="form-control" name="nombre_arreglo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion del Cliente (*)</label>
                            <textarea class="form-control" name="descripcion_cliente" rows="3" required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Valor de Pago ($)</label>
                                <input type="number" step="0.01" class="form-control" name="valor_pago">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha de Recibido (*)</label>
                                <input type="date" class="form-control" name="fecha_recibido" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha de Entrega</label>
                                <input type="date" class="form-control" name="fecha_entrega">
                            </div>
                        </div>

                        <!-- Seccion de Productos del Inventario -->
                        <button type="submit" name="crear_arreglo" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Crear Arreglo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para cada arreglo -->
    <?php foreach ($arreglos as $arreglo): ?>
    
    <!-- Modal para editar arreglo (Admin) -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar arreglo (Admin) -->
    <div class="modal fade" id="editarArregloModal<?php echo $arreglo['idArreglo']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Arreglo #<?php echo $arreglo['idArreglo']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id_arreglo" value="<?php echo $arreglo['idArreglo']; ?>">
                        <div class="alert alert-warning">
                            Estado actual: <strong><?php echo htmlspecialchars($arreglo['Nombre_Estado']); ?></strong> - Solo el tecnico puede modificar el estado.
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Dispositivo (*)</label>
                                <select class="form-select" name="tipo_dispositivo_id" required>
                                    <?php foreach ($tiposDispositivo as $tipo): ?>
                                    <option value="<?php echo $tipo['idTipoDispositivo']; ?>" <?php echo ($tipo['idTipoDispositivo'] == $arreglo['Tipo_Dispositivo_idTipo']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['Nombre_Tipo']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Marca (*)</label>
                                <select class="form-select" name="marca_id" required>
                                    <?php foreach ($marcas as $marca): ?>
                                    <option value="<?php echo $marca['idMarca']; ?>" <?php echo ($marca['idMarca'] == $arreglo['Marca_idMarca']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($marca['Nombre_Marca']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Cliente (*)</label>
                                <select class="form-select" name="cliente_id" required>
                                    <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['idCliente']; ?>" <?php echo ($cliente['idCliente'] == $arreglo['Cliente_idCliente']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cliente['Primer_Nombre'] . ' ' . $cliente['Primer_Apellido']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tecnico Asignado (*)</label>
                                <select class="form-select" name="tecnico_id" required>
                                    <?php foreach ($tecnicos as $tecnico): ?>
                                    <option value="<?php echo $tecnico['idTecnico']; ?>" <?php echo ($tecnico['idTecnico'] == $arreglo['Tecnico_idTecnico']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tecnico['Nombre_Tecnico']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion del problema (*)</label>
                            <input type="text" class="form-control" name="nombre_arreglo" value="<?php echo htmlspecialchars($arreglo['Nombre_Arreglo']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion del Cliente (*)</label>
                            <textarea class="form-control" name="descripcion_cliente" rows="3" required><?php echo htmlspecialchars($arreglo['Descripcion_Cliente']); ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Valor de Pago ($)</label>
                                <input type="number" step="0.01" class="form-control" name="valor_pago" value="<?php echo $arreglo['Valor_Pago']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha de Recibido (*)</label>
                                <input type="date" class="form-control" name="fecha_recibido" value="<?php echo $arreglo['Fecha_Recibido']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha de Entrega</label>
                                <input type="date" class="form-control" name="fecha_entrega" value="<?php echo $arreglo['Fecha_Entrega']; ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="actualizar_arreglo" class="btn btn-primary">Actualizar Arreglo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para actualizar estado (Tecnico) -->
    <div class="modal fade" id="estadoArregloModal<?php echo $arreglo['idArreglo']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Actualizar Estado del Arreglo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id_arreglo" value="<?php echo $arreglo['idArreglo']; ?>">
                        <p><strong>Arreglo:</strong> <?php echo htmlspecialchars($arreglo['Nombre_Arreglo']); ?></p>
                        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($arreglo['Nombre_Cliente'] ?? 'Sin asignar'); ?></p>
                        <p><strong>Telefono:</strong> <?php echo htmlspecialchars($arreglo['Telefono_Cliente'] ?? '-'); ?></p>
                        <p><strong>Estado actual:</strong> 
                            <span class="badge bg-<?php 
                                echo match($arreglo['Nombre_Estado']) {
                                    'En diagnostico' => 'warning',
                                    'En reparacion' => 'info',
                                    'Finalizado' => 'success',
                                    'Entregado' => 'secondary',
                                    default => 'primary'
                                };
                            ?>">
                                <?php echo htmlspecialchars($arreglo['Nombre_Estado']); ?>
                            </span>
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Nuevo Estado (*)</label>
                            <select class="form-select" name="estado_id" required>
                                <?php foreach ($estados as $estado): ?>
                                <option value="<?php echo $estado['idEstado']; ?>" <?php echo ($estado['idEstado'] == $arreglo['Estado_idEstado']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($estado['Nombre_Estado']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="actualizar_estado_arreglo" class="btn btn-primary">Actualizar Estado</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</body>
</html>