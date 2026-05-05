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
$puedeCrearArreglos = $isAdmin || tienePermiso('arreglos');

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Funciones auxiliares
function obtenerEstados($conn)
{
    $result = $conn->query("SELECT * FROM Estado");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerTiposDispositivo($conn)
{
    $result = $conn->query("SELECT * FROM Tipo_Dispositivo ORDER BY Nombre_Tipo");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerMarcas($conn)
{
    $result = $conn->query("SELECT * FROM Marca ORDER BY Nombre_Marca");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerTiposArreglo($conn)
{
    $result = $conn->query("SELECT * FROM Tipo_Arreglo ORDER BY nombre");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Solo tecnicos (Rol_idRol = 2) pueden ser asignados a arreglos
function obtenerTecnicosAsignables($conn)
{
    $result = $conn->query("SELECT * FROM Tecnico WHERE Rol_idRol = 2 ORDER BY Nombre_Tecnico");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerClientes($conn)
{
    $result = $conn->query("SELECT * FROM Cliente ORDER BY Primer_Nombre");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerTodosReingresos($conn)
{
    $result = $conn->query("SELECT * FROM Reingreso_Arreglo ORDER BY Arreglo_idArreglo, Fecha_Registro DESC");
    $reingresos = [];
    if ($result) {
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $r) {
            $reingresos[$r['Arreglo_idArreglo']][] = $r;
        }
    }
    return $reingresos;
}

// Arreglos segun rol: Admin o tecnico con permiso ve todos, Tecnico solo los suyos
function obtenerArreglos($conn, $isAdmin, $idTecnico, $puedeVer = false)
{
    $sql = "SELECT a.*, e.Nombre_Estado, m.Nombre_Marca, u.Nombre_Tecnico, td.Nombre_Tipo,
                   ta.nombre AS Nombre_Tipo_Arreglo,
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
            JOIN Tipo_Dispositivo td ON a.Tipo_Dispositivo_idTipo = td.idTipoDispositivo
            JOIN Tipo_Arreglo ta ON a.tipo_arreglo_id = ta.id";

    // Tecnico sin permiso especial solo ve sus propios arreglos
    if (!$isAdmin && !$puedeVer) {
        $sql .= " WHERE a.Tecnico_idTecnico = " . (int) $idTecnico;
    }

    $sql .= " ORDER BY a.idArreglo DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Crear arreglo (Admin o tecnico con permiso 'arreglos')
if (isset($_POST['crear_arreglo'])) {
    if (!$puedeCrearArreglos) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>No tiene permiso para crear arreglos.</div>";
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $tipo_dispositivo_id = (int) $_POST['tipo_dispositivo_id'];
        $nombre_arreglo = trim($_POST['nombre_arreglo']);
        $descripcion_cliente = trim($_POST['descripcion_cliente']);
        $valor_pago = $_POST['valor_pago'];
        $fecha_recibido = $_POST['fecha_recibido'];
        $fecha_entrega = $_POST['fecha_entrega'];
        $marca_id = (int) $_POST['marca_id'];
        $tecnico_id = (int) $_POST['tecnico_id'];
        $tipo_arreglo_id = (int) $_POST['tipo_arreglo_id'];
        $estado_id = 1; // Siempre inicia en "En diagnostico"
        $cliente_id = (int) $_POST['cliente_id'];

        $conn->begin_transaction();
        try {
            // Crear el arreglo
            $stmt = $conn->prepare("INSERT INTO Arreglo (Tipo_Dispositivo_idTipo, tipo_arreglo_id, Nombre_Arreglo, Descripcion_Cliente, Valor_Pago, Fecha_Recibido, Fecha_Entrega, Marca_idMarca, Tecnico_idTecnico, Estado_idEstado, Fecha_Cambio_Estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iissdssiii", $tipo_dispositivo_id, $tipo_arreglo_id, $nombre_arreglo, $descripcion_cliente, $valor_pago, $fecha_recibido, $fecha_entrega, $marca_id, $tecnico_id, $estado_id);
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

// Actualizar arreglo (Admin o tecnico con permiso 'arreglos')
if (isset($_POST['actualizar_arreglo'])) {
    if (!$puedeCrearArreglos) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>No tiene permiso para actualizar arreglos.</div>";
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int) $_POST['id_arreglo'];
        $tipo_dispositivo_id = (int) $_POST['tipo_dispositivo_id'];
        $tipo_arreglo_id = (int) $_POST['tipo_arreglo_id'];
        $nombre_arreglo = trim($_POST['nombre_arreglo']);
        $descripcion_cliente = trim($_POST['descripcion_cliente']);
        $valor_pago = $_POST['valor_pago'];
        $fecha_recibido = $_POST['fecha_recibido'];
        $fecha_entrega = $_POST['fecha_entrega'];
        $marca_id = (int) $_POST['marca_id'];
        $tecnico_id = (int) $_POST['tecnico_id'];
        $cliente_id = (int) $_POST['cliente_id'];

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE Arreglo SET Tipo_Dispositivo_idTipo=?, tipo_arreglo_id=?, Nombre_Arreglo=?, Descripcion_Cliente=?, Valor_Pago=?, Fecha_Recibido=?, Fecha_Entrega=?, Marca_idMarca=?, Tecnico_idTecnico=? WHERE idArreglo=?");
            $stmt->bind_param("iissdssiis", $tipo_dispositivo_id, $tipo_arreglo_id, $nombre_arreglo, $descripcion_cliente, $valor_pago, $fecha_recibido, $fecha_entrega, $marca_id, $tecnico_id, $id);
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


// Actualizar estado (Tecnicos — incluyendo los que tienen permiso 'arreglos')
if (isset($_POST['actualizar_estado_arreglo'])) {
    if ($isAdmin) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>El administrador no puede actualizar el estado directamente.</div>";
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int) $_POST['id_arreglo'];
        $estado_id = (int) $_POST['estado_id'];
        $comentario_estado = trim($_POST['comentario_estado'] ?? '');

        if (empty($comentario_estado)) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>El comentario del estado es obligatorio.</div>";
        } else {
            $stmt = $conn->prepare("UPDATE Arreglo SET Estado_idEstado=?, comentario_estado=?, Fecha_Cambio_Estado=NOW() WHERE idArreglo=?");
            $stmt->bind_param("isi", $estado_id, $comentario_estado, $id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Estado del arreglo actualizado correctamente.</div>";
            } else {
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
    header("Location: arreglo.php");
    exit();
}

// Crear reingreso (Admin o tecnico con permiso 'arreglos')
if (isset($_POST['crear_reingreso'])) {
    if (!$puedeCrearArreglos) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>No tiene permiso para registrar reingresos.</div>";
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int) $_POST['id_arreglo'];
        $fecha_reingreso = $_POST['fecha_reingreso'];
        $motivo_reingreso = trim($_POST['motivo_reingreso'] ?? '');

        if (empty($motivo_reingreso) || empty($fecha_reingreso)) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Fecha y motivo de reingreso son obligatorios.</div>";
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO Reingreso_Arreglo (Arreglo_idArreglo, Fecha_Reingreso, Motivo_Reingreso) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $id, $fecha_reingreso, $motivo_reingreso);
                $stmt->execute();
                $stmt->close();

                $stmt_update = $conn->prepare("UPDATE Arreglo SET Fecha_Recibido=?, Estado_idEstado=1, Fecha_Cambio_Estado=NOW() WHERE idArreglo=?");
                $stmt_update->bind_param("si", $fecha_reingreso, $id);
                $stmt_update->execute();
                $stmt_update->close();

                $conn->commit();
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Reingreso registrado correctamente y el arreglo se ha puesto nuevamente en diagnostico.</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }
    header("Location: arreglo.php");
    exit();
}

// Eliminar arreglo (Admin o tecnico con permiso 'arreglos')
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    if (!$puedeCrearArreglos) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>No tiene permiso para eliminar arreglos.</div>";
    } else {
        $conn->begin_transaction();
        try {
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

$arreglos = obtenerArreglos($conn, $isAdmin, $idTecnicoActual, $puedeCrearArreglos);
$estados = obtenerEstados($conn);
$tiposDispositivo = obtenerTiposDispositivo($conn);
$tiposArreglo = obtenerTiposArreglo($conn);
$marcas = obtenerMarcas($conn);
$tecnicos = obtenerTecnicosAsignables($conn);
$clientes = obtenerClientes($conn);
$todosReingresos = obtenerTodosReingresos($conn);
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
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
    <style>
        .select2-container--open {
            z-index: 9999 !important;
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <?php echo $mensaje; ?>

    <div class="container-fluid px-4 mt-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h4 class="mb-0"><i class="bi bi-tools"></i> Listar Arreglos</h4>
                <?php if ($puedeCrearArreglos): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#crearArregloModal">
                        <i class="bi bi-plus-lg"></i> Crear Arreglo
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered align-middle mb-0" style="font-size:0.9rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width:50px;">ID</th>
                                <th>Tipo Dispositivo</th>
                                <th>Tipo Arreglo</th>
                                <th>Descripcion</th>
                                <?php if ($puedeCrearArreglos): ?>
                                    <th class="text-end">Valor</th>
                                <?php endif; ?>
                                <th class="text-center">Fecha Recibido</th>
                                <th class="text-center">Estado</th>
                                <th>Marca</th>
                                <th>Tecnico</th>
                                <th>Cliente</th>
                                <th>Telefono</th>
                                <th class="text-center">Reingresos</th>
                                <th class="text-center" style="width:150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($arreglos as $arreglo):
                                $reingresos = $todosReingresos[$arreglo['idArreglo']] ?? [];
                                $totalReingresos = count($reingresos);
                            ?>
                                <tr>
                                    <td class="text-center fw-bold"><?php echo $arreglo['idArreglo']; ?></td>
                                    <td><?php echo htmlspecialchars($arreglo['Nombre_Tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($arreglo['Nombre_Tipo_Arreglo']); ?></td>
                                    <td><?php echo htmlspecialchars($arreglo['Nombre_Arreglo']); ?></td>
                                    <?php if ($puedeCrearArreglos): ?>
                                        <td class="text-end">$ <?php echo number_format($arreglo['Valor_Pago'], 0); ?></td>
                                    <?php endif; ?>
                                    <td class="text-center"><?php echo $arreglo['Fecha_Recibido']; ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php
                                        echo match ($arreglo['Nombre_Estado']) {
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
                                    <td class="text-center">
                                        <?php if ($totalReingresos > 0): ?>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#historialReingresoModal<?php echo $arreglo['idArreglo']; ?>"
                                                title="Ver historial de reingresos">
                                                <i class="bi bi-clock-history"></i>
                                                <span class="badge bg-warning text-dark ms-1"><?php echo $totalReingresos; ?></span>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin reingresos</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        <?php if ($puedeCrearArreglos): ?>
                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal"
                                                data-bs-target="#reingresoArregloModal<?php echo $arreglo['idArreglo']; ?>"
                                                title="Registrar Reingreso">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#editarArregloModal<?php echo $arreglo['idArreglo']; ?>"
                                                title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="?eliminar=<?php echo $arreglo['idArreglo']; ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('¿Estas seguro de eliminar este arreglo?')"
                                                title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!$isAdmin): ?>
                                            <?php if ($arreglo['Estado_idEstado'] == 3): // 3 = Finalizado ?>
                                                <span class="badge bg-success px-2 py-2">
                                                    <i class="bi bi-check-circle-fill"></i> Finalizado
                                                </span>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal"
                                                    data-bs-target="#estadoArregloModal<?php echo $arreglo['idArreglo']; ?>"
                                                    title="Actualizar Estado">
                                                    <i class="bi bi-pencil-square"></i> Estado
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        </div>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post" id="formCrearArreglo">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> El arreglo se creara con estado <strong>"En
                                diagnostico"</strong>. Solo el tecnico asignado puede cambiar el estado.
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <label class="form-label">Tipo de Arreglo (*)</label>
                                <select class="form-select" name="tipo_arreglo_id" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($tiposArreglo as $tipo_arreglo): ?>
                                        <option value="<?php echo $tipo_arreglo['id']; ?>">
                                            <?php echo htmlspecialchars($tipo_arreglo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
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
                            <label class="form-label">Descripcion del problema (Tecnico (*))</label>
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
    <?php foreach ($arreglos as $arreglo):
        $reingresos = $todosReingresos[$arreglo['idArreglo']] ?? [];
        $totalReingresos = count($reingresos);
    ?>

        <!-- Modal historial de reingresos -->
        <?php if ($totalReingresos > 0): ?>
        <div class="modal fade" id="historialReingresoModal<?php echo $arreglo['idArreglo']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="bi bi-clock-history"></i>Arreglo #<?php echo $arreglo['idArreglo']; ?>
                            <small class="ms-2 text-dark"><?php echo htmlspecialchars($arreglo['Nombre_Arreglo']); ?></small>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th class="text-center" style="width:40px;">#</th>
                                    <th class="text-center" style="width:130px;">Fecha Reingreso</th>
                                    <th>Motivo</th>
                                    <th class="text-center" style="width:150px;">Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reingresos as $i => $r): ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo $totalReingresos - $i; ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($r['Fecha_Reingreso']); ?></td>
                                        <td><?php echo htmlspecialchars($r['Motivo_Reingreso']); ?></td>
                                        <td class="text-center text-muted small"><?php echo htmlspecialchars($r['Fecha_Registro']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Modal para editar arreglo (Admin) -->
        <div class="modal fade" id="editarArregloModal<?php echo $arreglo['idArreglo']; ?>" tabindex="-1"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Arreglo
                            #<?php echo $arreglo['idArreglo']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="id_arreglo" value="<?php echo $arreglo['idArreglo']; ?>">
                            <div class="alert alert-warning">
                                Estado actual: <strong><?php echo htmlspecialchars($arreglo['Nombre_Estado']); ?></strong> -
                                Solo el tecnico puede modificar el estado.
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Tipo de Dispositivo (*)</label>
                                    <select class="form-select" name="tipo_dispositivo_id" required>
                                        <?php foreach ($tiposDispositivo as $tipo): ?>
                                            <option value="<?php echo $tipo['idTipoDispositivo']; ?>" <?php echo ($tipo['idTipoDispositivo'] == $arreglo['Tipo_Dispositivo_idTipo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tipo['Nombre_Tipo']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tipo de Arreglo (*)</label>
                                    <select class="form-select" name="tipo_arreglo_id" required>
                                        <?php foreach ($tiposArreglo as $tipo_arreglo): ?>
                                            <option value="<?php echo $tipo_arreglo['id']; ?>" <?php echo ($tipo_arreglo['id'] == $arreglo['tipo_arreglo_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tipo_arreglo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
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
                                <input type="text" class="form-control" name="nombre_arreglo"
                                    value="<?php echo htmlspecialchars($arreglo['Nombre_Arreglo']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripcion del Cliente (*)</label>
                                <textarea class="form-control" name="descripcion_cliente" rows="3"
                                    required><?php echo htmlspecialchars($arreglo['Descripcion_Cliente']); ?></textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Valor de Pago ($)</label>
                                    <input type="number" step="0.01" class="form-control" name="valor_pago"
                                        value="<?php echo $arreglo['Valor_Pago']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha de Recibido (*)</label>
                                    <input type="date" class="form-control" name="fecha_recibido"
                                        value="<?php echo $arreglo['Fecha_Recibido']; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha de Entrega</label>
                                    <input type="date" class="form-control" name="fecha_entrega"
                                        value="<?php echo $arreglo['Fecha_Entrega']; ?>">
                                </div>
                            </div>

                            <button type="submit" name="actualizar_arreglo" class="btn btn-primary">Actualizar
                                Arreglo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para reingreso (Admin) -->
        <div class="modal fade" id="reingresoArregloModal<?php echo $arreglo['idArreglo']; ?>" tabindex="-1"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-secondary text-white">
                        <h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Registrar Reingreso — Arreglo
                            #<?php echo $arreglo['idArreglo']; ?>
                            <small class="ms-2 opacity-75"><?php echo htmlspecialchars($arreglo['Nombre_Arreglo']); ?></small>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="id_arreglo" value="<?php echo $arreglo['idArreglo']; ?>">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Fecha de Reingreso (*)</label>
                                    <input type="date" class="form-control" name="fecha_reingreso" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Motivo de Reingreso (*)</label>
                                    <textarea class="form-control" name="motivo_reingreso" rows="2" required></textarea>
                                </div>
                            </div>
                            <button type="submit" name="crear_reingreso" class="btn btn-secondary">
                                <i class="bi bi-plus-lg"></i> Registrar Reingreso
                            </button>
                        </form>

                        <?php if ($totalReingresos > 0): ?>
                        <hr>
                        <h6 class="mt-3"><i class="bi bi-clock-history"></i> Historial de Reingresos
                            <span class="badge bg-secondary"><?php echo $totalReingresos; ?></span>
                        </h6>
                        <table class="table table-sm table-bordered align-middle mt-2">
                            <thead class="table-secondary">
                                <tr>
                                    <th class="text-center" style="width:40px;">#</th>
                                    <th class="text-center" style="width:130px;">Fecha</th>
                                    <th>Motivo</th>
                                    <th class="text-center" style="width:150px;">Registrado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reingresos as $i => $r): ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo $totalReingresos - $i; ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($r['Fecha_Reingreso']); ?></td>
                                        <td><?php echo htmlspecialchars($r['Motivo_Reingreso']); ?></td>
                                        <td class="text-center text-muted small"><?php echo htmlspecialchars($r['Fecha_Registro']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para actualizar estado (Tecnico) -->
        <div class="modal fade" id="estadoArregloModal<?php echo $arreglo['idArreglo']; ?>" tabindex="-1"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Actualizar Estado del Arreglo</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="id_arreglo" value="<?php echo $arreglo['idArreglo']; ?>">
                            <p><strong>Arreglo:</strong> <?php echo htmlspecialchars($arreglo['Nombre_Arreglo']); ?></p>
                            <p><strong>Cliente:</strong>
                                <?php echo htmlspecialchars($arreglo['Nombre_Cliente'] ?? 'Sin asignar'); ?></p>
                            <p><strong>Telefono:</strong>
                                <?php echo htmlspecialchars($arreglo['Telefono_Cliente'] ?? '-'); ?></p>
                            <p><strong>Estado actual:</strong>
                                <span class="badge bg-<?php
                                echo match ($arreglo['Nombre_Estado']) {
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
                            <div class="mb-3">
                                <label class="form-label">Comentario del estado (*)</label>
                                <textarea class="form-control" name="comentario_estado" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="actualizar_estado_arreglo" class="btn btn-primary">Actualizar
                                Estado</button>
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