<?php
/**
 * Gestionar Diagnostico Avanzado
 * - Selectores dinamicos: Arreglo, Tipo Dispositivo, Marca, Cliente
 * - Multiselect Select2 para componentes afectados (solo activos del inventario)
 * - Telefono del cliente visible
 * - NUEVO: Flujo de asignacion a tecnico despues de crear diagnostico
 */

include('auth.php');
include("conexion.php");

$isAdmin = isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador';
$idTecnicoActual = $_SESSION['idTecnico'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Solo componentes activos
function obtenerComponentes($conn)
{
    $result = $conn->query("SELECT * FROM Componente WHERE Activo = 1 ORDER BY Nombre_Componente");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Obtener tecnicos asignables (Rol_idRol = 2)
function obtenerTecnicosAsignables($conn)
{
    $result = $conn->query("SELECT * FROM Tecnico WHERE Rol_idRol = 2 ORDER BY Nombre_Tecnico");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Obtener tipos de dispositivo
function obtenerTiposDispositivo($conn)
{
    $result = $conn->query("SELECT * FROM Tipo_Dispositivo ORDER BY Nombre_Tipo");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Obtener marcas
function obtenerMarcas($conn)
{
    $result = $conn->query("SELECT * FROM Marca ORDER BY Nombre_Marca");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Obtener clientes
function obtenerClientes($conn)
{
    $result = $conn->query("SELECT * FROM Cliente ORDER BY Primer_Nombre");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerArreglosParaDiagnostico($conn, $isAdmin, $idTecnico)
{
    $sql = "SELECT a.*, td.Nombre_Tipo, m.Nombre_Marca, e.Nombre_Estado,
                   CONCAT(c.Primer_Nombre, ' ', c.Primer_Apellido) AS Nombre_Cliente,
                   c.Telefono AS Telefono_Cliente
            FROM Arreglo a
            JOIN Tipo_Dispositivo td ON a.Tipo_Dispositivo_idTipo = td.idTipoDispositivo
            JOIN Marca m ON a.Marca_idMarca = m.idMarca
            JOIN Estado e ON a.Estado_idEstado = e.idEstado
            LEFT JOIN Detalle_Arreglo da ON da.Arreglo_idArreglo = a.idArreglo
            LEFT JOIN Cliente c ON da.Cliente_idCliente = c.idCliente";
    if (!$isAdmin)
        $sql .= " WHERE a.Tecnico_idTecnico = " . (int) $idTecnico;
    $sql .= " ORDER BY a.idArreglo DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerDiagnosticos($conn, $isAdmin, $idTecnico)
{
    $sql = "SELECT d.*, a.Nombre_Arreglo, a.idArreglo, td.Nombre_Tipo, m.Nombre_Marca,
                   CONCAT(c.Primer_Nombre, ' ', c.Primer_Apellido) AS Nombre_Cliente,
                   c.Telefono AS Telefono_Cliente,
                   t.Nombre_Tecnico AS Tecnico_Asignado_Nombre,
                   d.Asignado_Tecnico, d.Tecnico_Asignado, d.Fecha_Asignacion
            FROM Diagnostico d
            JOIN Arreglo a ON d.Arreglo_idArreglo = a.idArreglo
            JOIN Tipo_Dispositivo td ON a.Tipo_Dispositivo_idTipo = td.idTipoDispositivo
            JOIN Marca m ON a.Marca_idMarca = m.idMarca
            LEFT JOIN Detalle_Arreglo da ON da.Arreglo_idArreglo = a.idArreglo
            LEFT JOIN Cliente c ON da.Cliente_idCliente = c.idCliente
            LEFT JOIN Tecnico t ON d.Tecnico_Asignado = t.idTecnico";
    if (!$isAdmin)
        $sql .= " WHERE a.Tecnico_idTecnico = " . (int) $idTecnico;
    $sql .= " ORDER BY d.idDiagnostico DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerComponentesDiagnostico($conn, $idDiagnostico)
{
    $sql = "SELECT c.* FROM Detalle_Diagnostico_Componente ddc
            JOIN Componente c ON ddc.Componente_idComponente = c.idComponente
            WHERE ddc.Diagnostico_idDiagnostico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idDiagnostico);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Crear diagnostico (NUEVO FLUJO: ahora permite crear sin arreglo existente)
if (isset($_POST['crear_diagnostico'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $modo_creacion = $_POST['modo_creacion'] ?? 'existente';
        $descripcion_problema = trim($_POST['descripcion_problema']);
        $valor_estimado = $_POST['valor_estimado'];
        $componentes = $_POST['componentes'] ?? [];

        $conn->begin_transaction();
        try {
            if ($modo_creacion === 'nuevo') {
                // Crear nuevo arreglo desde diagnostico
                $tipo_dispositivo_id = (int) $_POST['tipo_dispositivo_id'];
                $marca_id = (int) $_POST['marca_id'];
                $cliente_id = (int) $_POST['cliente_id'];
                $nombre_arreglo = trim($_POST['nombre_arreglo']);
                $descripcion_cliente = trim($_POST['descripcion_cliente']);
                $fecha_recibido = $_POST['fecha_recibido'];

                // Crear arreglo sin tecnico asignado aun (se asignara despues)
                // Temporalmente se asigna al admin o al tecnico que crea
                $tecnico_temp = $idTecnicoActual;

                $stmt = $conn->prepare("INSERT INTO Arreglo (Tipo_Dispositivo_idTipo, Nombre_Arreglo, Descripcion_Cliente, Valor_Pago, Fecha_Recibido, Marca_idMarca, Tecnico_idTecnico, Estado_idEstado, Fecha_Cambio_Estado) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
                $stmt->bind_param("issdsii", $tipo_dispositivo_id, $nombre_arreglo, $descripcion_cliente, $valor_estimado, $fecha_recibido, $marca_id, $tecnico_temp);
                $stmt->execute();
                $arreglo_id = $conn->insert_id;
                $stmt->close();

                // Crear detalle arreglo (cliente)
                $stmt_detalle = $conn->prepare("INSERT INTO Detalle_Arreglo (Cliente_idCliente, Arreglo_idArreglo) VALUES (?, ?)");
                $stmt_detalle->bind_param("ii", $cliente_id, $arreglo_id);
                $stmt_detalle->execute();
                $stmt_detalle->close();
            } else {
                $arreglo_id = (int) $_POST['arreglo_id'];
            }

            // Crear diagnostico
            $stmt = $conn->prepare("INSERT INTO Diagnostico (Arreglo_idArreglo, Descripcion_Problema, Valor_Estimado, Asignado_Tecnico) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("isd", $arreglo_id, $descripcion_problema, $valor_estimado);
            $stmt->execute();
            $idDiagnostico = $conn->insert_id;
            $stmt->close();

            if (!empty($componentes)) {
                $stmtComp = $conn->prepare("INSERT INTO Detalle_Diagnostico_Componente (Diagnostico_idDiagnostico, Componente_idComponente) VALUES (?, ?)");
                foreach ($componentes as $comp_id) {
                    $comp_id = (int) $comp_id;
                    $stmtComp->bind_param("ii", $idDiagnostico, $comp_id);
                    $stmtComp->execute();
                }
                $stmtComp->close();
            }
            $conn->commit();

            // Redirigir al modal de asignacion de tecnico
            $_SESSION['diagnostico_pendiente_asignacion'] = $idDiagnostico;
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Diagnostico creado correctamente. Ahora asigne un tecnico para que aparezca en Arreglos.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
    header("Location: diagnostico.php");
    exit();
}

// NUEVO: Asignar tecnico a diagnostico
if (isset($_POST['asignar_tecnico'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id_diagnostico = (int) $_POST['id_diagnostico'];
        $tecnico_id = (int) $_POST['tecnico_id'];

        $conn->begin_transaction();
        try {
            // Obtener datos del diagnostico
            $stmt = $conn->prepare("SELECT d.*, a.idArreglo FROM Diagnostico d JOIN Arreglo a ON d.Arreglo_idArreglo = a.idArreglo WHERE d.idDiagnostico = ?");
            $stmt->bind_param("i", $id_diagnostico);
            $stmt->execute();
            $diag = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$diag)
                throw new Exception("Diagnostico no encontrado.");

            // Actualizar el arreglo con el tecnico asignado
            $stmt = $conn->prepare("UPDATE Arreglo SET Tecnico_idTecnico = ?, Estado_idEstado = 2 WHERE idArreglo = ?");
            $stmt->bind_param("ii", $tecnico_id, $diag['idArreglo']);
            $stmt->execute();
            $stmt->close();

            // Marcar diagnostico como asignado
            $stmt = $conn->prepare("UPDATE Diagnostico SET Asignado_Tecnico = 1, Tecnico_Asignado = ?, Fecha_Asignacion = NOW() WHERE idDiagnostico = ?");
            $stmt->bind_param("ii", $tecnico_id, $id_diagnostico);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Tecnico asignado correctamente. El arreglo ahora aparece en la lista de Arreglos con estado 'En reparacion'.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
    header("Location: diagnostico.php");
    exit();
}

// Actualizar diagnostico
if (isset($_POST['actualizar_diagnostico'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int) $_POST['id_diagnostico'];
        $arreglo_id = (int) $_POST['arreglo_id'];
        $descripcion_problema = trim($_POST['descripcion_problema']);
        $valor_estimado = $_POST['valor_estimado'];
        $componentes = $_POST['componentes'] ?? [];

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE Diagnostico SET Arreglo_idArreglo=?, Descripcion_Problema=?, Valor_Estimado=? WHERE idDiagnostico=?");
            $stmt->bind_param("isdi", $arreglo_id, $descripcion_problema, $valor_estimado, $id);
            $stmt->execute();
            $stmt->close();

            $stmt_delete = $conn->prepare("DELETE FROM Detalle_Diagnostico_Componente WHERE Diagnostico_idDiagnostico=?");
            $stmt_delete->bind_param("i", $id);
            $stmt_delete->execute();
            $stmt_delete->close();

            if (!empty($componentes)) {
                $stmtComp = $conn->prepare("INSERT INTO Detalle_Diagnostico_Componente (Diagnostico_idDiagnostico, Componente_idComponente) VALUES (?, ?)");
                foreach ($componentes as $comp_id) {
                    $comp_id = (int) $comp_id;
                    $stmtComp->bind_param("ii", $id, $comp_id);
                    $stmtComp->execute();
                }
                $stmtComp->close();
            }
            $conn->commit();
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Diagnostico actualizado con exito</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
    header("Location: diagnostico.php");
    exit();
}

// Eliminar diagnostico
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM Detalle_Diagnostico_Componente WHERE Diagnostico_idDiagnostico = $id");
        $conn->query("DELETE FROM Diagnostico WHERE idDiagnostico = $id");
        $conn->commit();
        $_SESSION['mensaje'] = "<div class='alert alert-success'>Diagnostico eliminado con exito</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
    header("Location: diagnostico.php");
    exit();
}

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

$diagnosticoPendiente = $_SESSION['diagnostico_pendiente_asignacion'] ?? null;
unset($_SESSION['diagnostico_pendiente_asignacion']);

$diagnosticos = obtenerDiagnosticos($conn, $isAdmin, $idTecnicoActual);
$arreglos = obtenerArreglosParaDiagnostico($conn, $isAdmin, $idTecnicoActual);
$componentes = obtenerComponentes($conn);
$tecnicos = obtenerTecnicosAsignables($conn);
$tiposDispositivo = obtenerTiposDispositivo($conn);
$marcas = obtenerMarcas($conn);
$clientes = obtenerClientes($conn);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnosticos - Sistema de Arreglo de Computadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
    <style>
        .modal-body .select2-container {
            width: 100% !important;
        }

        .select2-container--open {
            z-index: 9999 !important;
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(2.25rem + 4px);
        }

        .badge-pendiente {
            background-color: #ffc107;
            color: #000;
        }

        .badge-asignado {
            background-color: #198754;
            color: #fff;
        }

        .nuevo-arreglo-fields {
            display: none;
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <?php echo $mensaje; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-clipboard2-pulse"></i> Listar Diagnosticos</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#crearDiagnosticoModal">
                    <i class="bi bi-plus-lg"></i> Crear Diagnostico
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Arreglo</th>
                                <th>Tipo</th>
                                <th>Marca</th>
                                <th>Cliente</th>
                                <th>Telefono</th>
                                <th>Componentes</th>
                                <th>Valor Est.</th>
                                <th>Estado Asignacion</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diagnosticos as $diagnostico):
                                $comps = obtenerComponentesDiagnostico($conn, $diagnostico['idDiagnostico']); ?>
                                <tr>
                                    <td><?php echo $diagnostico['idDiagnostico']; ?></td>
                                    <td><?php echo htmlspecialchars($diagnostico['Nombre_Arreglo']); ?></td>
                                    <td><?php echo htmlspecialchars($diagnostico['Nombre_Tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($diagnostico['Nombre_Marca']); ?></td>
                                    <td><?php echo htmlspecialchars($diagnostico['Nombre_Cliente'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($diagnostico['Telefono_Cliente'] ?? '-'); ?></td>
                                    <td>
                                        <?php foreach ($comps as $c): ?>
                                            <span
                                                class="badge bg-secondary"><?php echo htmlspecialchars($c['Nombre_Componente']); ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>$ <?php echo number_format($diagnostico['Valor_Estimado'], 0); ?></td>
                                    <td>
                                        <?php if ($diagnostico['Asignado_Tecnico']): ?>
                                            <span class="badge badge-asignado">
                                                <i class="bi bi-person-check"></i>
                                                <?php echo htmlspecialchars($diagnostico['Tecnico_Asignado_Nombre'] ?? 'Asignado'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-pendiente">
                                                <i class="bi bi-clock"></i> Pendiente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$diagnostico['Asignado_Tecnico'] && $isAdmin): ?>
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                                data-bs-target="#asignarTecnicoModal<?php echo $diagnostico['idDiagnostico']; ?>">
                                                <i class="bi bi-person-plus"></i> Asignar
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#editarDiagnosticoModal<?php echo $diagnostico['idDiagnostico']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?eliminar=<?php echo $diagnostico['idDiagnostico']; ?>"
                                            class="btn btn-sm btn-danger" onclick="return confirm('¿Estas seguro?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Diagnostico -->
    <div class="modal fade" id="crearDiagnosticoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-clipboard2-plus"></i> Crear Nuevo Diagnostico</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <!-- Selector de modo -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Modo de creacion:</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="modo_creacion" id="modo_existente"
                                    value="existente" checked>
                                <label class="btn btn-outline-primary" for="modo_existente">
                                    <i class="bi bi-folder2-open"></i> Usar Arreglo Existente
                                </label>
                                <input type="radio" class="btn-check" name="modo_creacion" id="modo_nuevo"
                                    value="nuevo">
                                <label class="btn btn-outline-success" for="modo_nuevo">
                                    <i class="bi bi-plus-circle"></i> Crear Nuevo Arreglo
                                </label>
                            </div>
                        </div>

                        <!-- Campos para arreglo existente -->
                        <div id="campos_existente">
                            <div class="mb-3">
                                <label class="form-label">Seleccionar Arreglo (*)</label>
                                <select class="form-select" name="arreglo_id" id="arreglo_select_crear">
                                    <option value="">Seleccione un arreglo...</option>
                                    <?php foreach ($arreglos as $arr): ?>
                                        <option value="<?php echo $arr['idArreglo']; ?>"
                                            data-tipo="<?php echo htmlspecialchars($arr['Nombre_Tipo']); ?>"
                                            data-marca="<?php echo htmlspecialchars($arr['Nombre_Marca']); ?>"
                                            data-cliente="<?php echo htmlspecialchars($arr['Nombre_Cliente'] ?? '-'); ?>"
                                            data-telefono="<?php echo htmlspecialchars($arr['Telefono_Cliente'] ?? '-'); ?>">
                                            #<?php echo $arr['idArreglo']; ?> -
                                            <?php echo htmlspecialchars($arr['Nombre_Arreglo']); ?>
                                            (<?php echo htmlspecialchars($arr['Nombre_Tipo']); ?> -
                                            <?php echo htmlspecialchars($arr['Nombre_Marca']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row mb-3" id="info_arreglo_crear" style="display:none;">
                                <div class="col-md-3"><label class="form-label">Tipo Dispositivo</label><input
                                        type="text" class="form-control" id="tipo_disp_crear" readonly></div>
                                <div class="col-md-3"><label class="form-label">Marca</label><input type="text"
                                        class="form-control" id="marca_crear" readonly></div>
                                <div class="col-md-3"><label class="form-label">Cliente</label><input type="text"
                                        class="form-control" id="cliente_crear" readonly></div>
                                <div class="col-md-3"><label class="form-label">Telefono</label><input type="text"
                                        class="form-control" id="telefono_crear" readonly></div>
                            </div>
                        </div>

                        <!-- Campos para nuevo arreglo -->
                        <div id="campos_nuevo" class="nuevo-arreglo-fields">
                            <div class="alert alert-success small">
                                <i class="bi bi-lightbulb"></i> Se creara un nuevo arreglo automaticamente con los datos
                                del diagnostico.
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Cliente (*)</label>
                                    <select class="form-select" name="cliente_id" id="cliente_nuevo">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <option value="<?php echo $cliente['idCliente']; ?>">
                                                <?php echo htmlspecialchars($cliente['Primer_Nombre'] . ' ' . $cliente['Primer_Apellido'] . ' - Tel: ' . $cliente['Telefono']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fecha Recibido (*)</label>
                                    <input type="date" class="form-control" name="fecha_recibido" id="fecha_recibido"
                                        value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tipo de Dispositivo (*)</label>
                                    <select class="form-select" name="tipo_dispositivo_id" id="tipo_disp_nuevo">
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
                                    <select class="form-select" name="marca_id" id="marca_nuevo">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($marcas as $marca): ?>
                                            <option value="<?php echo $marca['idMarca']; ?>">
                                                <?php echo htmlspecialchars($marca['Nombre_Marca']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nombre del Arreglo (*)</label>
                                <input type="text" class="form-control" name="nombre_arreglo" id="nombre_arreglo"
                                    placeholder="Ej: Cambio de pantalla">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripcion del Cliente (*)</label>
                                <textarea class="form-control" name="descripcion_cliente" id="desc_cliente" rows="2"
                                    placeholder="Descripcion proporcionada por el cliente"></textarea>
                            </div>
                        </div>

                        <hr>

                        <!-- Campos comunes -->
                        <div class="mb-3">
                            <label class="form-label">Componentes Afectados (*)</label>
                            <select class="form-select" name="componentes[]" id="componentes_crear" multiple required
                                style="width: 100%">
                                <?php foreach ($componentes as $comp): ?>
                                    <option value="<?php echo $comp['idComponente']; ?>">
                                        <?php echo htmlspecialchars($comp['Nombre_Componente']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Puede seleccionar uno o varios componentes afectados.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion del Problema (Diagnostico) (*)</label>
                            <textarea class="form-control" name="descripcion_problema" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor Estimado / Mano de Obra ($) (*)</label>
                            <input type="number" step="0.01" class="form-control" name="valor_estimado" required>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="crear_diagnostico" class="btn btn-primary">
                                <i class="bi bi-save"></i> Crear Diagnostico
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales Asignar Tecnico -->
    <?php foreach ($diagnosticos as $diagnostico):
        if (!$diagnostico['Asignado_Tecnico']): ?>
            <div class="modal fade <?php echo ($diagnosticoPendiente == $diagnostico['idDiagnostico']) ? 'show-on-load' : ''; ?>"
                id="asignarTecnicoModal<?php echo $diagnostico['idDiagnostico']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><i class="bi bi-person-plus"></i> Asignar Tecnico</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info small">
                                <strong>Diagnostico #<?php echo $diagnostico['idDiagnostico']; ?>:</strong>
                                <?php echo htmlspecialchars($diagnostico['Nombre_Arreglo']); ?><br>
                                <strong>Cliente:</strong>
                                <?php echo htmlspecialchars($diagnostico['Nombre_Cliente'] ?? '-'); ?><br>
                                <strong>Valor:</strong> $ <?php echo number_format($diagnostico['Valor_Estimado'], 0); ?>
                            </div>
                            <form action="" method="post">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="id_diagnostico" value="<?php echo $diagnostico['idDiagnostico']; ?>">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Seleccionar Tecnico (*)</label>
                                    <select class="form-select" name="tecnico_id" required>
                                        <option value="">Seleccione un tecnico...</option>
                                        <?php foreach ($tecnicos as $tec): ?>
                                            <option value="<?php echo $tec['idTecnico']; ?>">
                                                <?php echo htmlspecialchars($tec['Nombre_Tecnico']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">El arreglo se movera a estado "En reparacion" y aparecera en la
                                        lista de arreglos del tecnico.</small>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" name="asignar_tecnico" class="btn btn-success">
                                        <i class="bi bi-check-lg"></i> Asignar Tecnico
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; endforeach; ?>

    <!-- Modales Editar -->
    <?php foreach ($diagnosticos as $diagnostico):
        $compsActuales = obtenerComponentesDiagnostico($conn, $diagnostico['idDiagnostico']);
        $compsIds = array_column($compsActuales, 'idComponente'); ?>
        <div class="modal fade" id="editarDiagnosticoModal<?php echo $diagnostico['idDiagnostico']; ?>" tabindex="-1"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Diagnostico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="id_diagnostico" value="<?php echo $diagnostico['idDiagnostico']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Seleccionar Arreglo (*)</label>
                                <select class="form-select" name="arreglo_id" required>
                                    <?php foreach ($arreglos as $arr): ?>
                                        <option value="<?php echo $arr['idArreglo']; ?>" <?php echo ($arr['idArreglo'] == $diagnostico['Arreglo_idArreglo']) ? 'selected' : ''; ?>>
                                            #<?php echo $arr['idArreglo']; ?> -
                                            <?php echo htmlspecialchars($arr['Nombre_Arreglo']); ?>
                                            (<?php echo htmlspecialchars($arr['Nombre_Tipo']); ?> -
                                            <?php echo htmlspecialchars($arr['Nombre_Marca']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><label class="form-label">Cliente</label><input type="text"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($diagnostico['Nombre_Cliente'] ?? '-'); ?>"
                                        readonly></div>
                                <div class="col-md-3"><label class="form-label">Telefono</label><input type="text"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($diagnostico['Telefono_Cliente'] ?? '-'); ?>"
                                        readonly></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Componentes Afectados (*)</label>
                                <select class="form-select" name="componentes[]" multiple required style="width: 100%">
                                    <?php foreach ($componentes as $comp): ?>
                                        <option value="<?php echo $comp['idComponente']; ?>" <?php echo in_array($comp['idComponente'], $compsIds) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($comp['Nombre_Componente']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripcion del Problema (*)</label>
                                <textarea class="form-control" name="descripcion_problema" rows="3"
                                    required><?php echo htmlspecialchars($diagnostico['Descripcion_Problema']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Valor Estimado ($) (*)</label>
                                <input type="number" step="0.01" class="form-control" name="valor_estimado"
                                    value="<?php echo $diagnostico['Valor_Estimado']; ?>" required>
                            </div>
                            <button type="submit" name="actualizar_diagnostico" class="btn btn-primary">Actualizar
                                Diagnostico</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Toggle modo de creacion
        document.querySelectorAll('input[name="modo_creacion"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var camposExistente = document.getElementById('campos_existente');
                var camposNuevo = document.getElementById('campos_nuevo');
                var arregloSelect = document.getElementById('arreglo_select_crear');

                if (this.value === 'nuevo') {
                    camposExistente.style.display = 'none';
                    camposNuevo.style.display = 'block';
                    arregloSelect.removeAttribute('required');
                    document.getElementById('cliente_nuevo').setAttribute('required', 'required');
                    document.getElementById('tipo_disp_nuevo').setAttribute('required', 'required');
                    document.getElementById('marca_nuevo').setAttribute('required', 'required');
                    document.getElementById('nombre_arreglo').setAttribute('required', 'required');
                    document.getElementById('desc_cliente').setAttribute('required', 'required');
                    document.getElementById('fecha_recibido').setAttribute('required', 'required');
                } else {
                    camposExistente.style.display = 'block';
                    camposNuevo.style.display = 'none';
                    arregloSelect.setAttribute('required', 'required');
                    document.getElementById('cliente_nuevo').removeAttribute('required');
                    document.getElementById('tipo_disp_nuevo').removeAttribute('required');
                    document.getElementById('marca_nuevo').removeAttribute('required');
                    document.getElementById('nombre_arreglo').removeAttribute('required');
                    document.getElementById('desc_cliente').removeAttribute('required');
                    document.getElementById('fecha_recibido').removeAttribute('required');
                }
            });
        });

        // Mostrar info del arreglo seleccionado al crear
        document.getElementById('arreglo_select_crear').addEventListener('change', function () {
            var selected = this.options[this.selectedIndex];
            var infoDiv = document.getElementById('info_arreglo_crear');
            if (this.value) {
                document.getElementById('tipo_disp_crear').value = selected.dataset.tipo;
                document.getElementById('marca_crear').value = selected.dataset.marca;
                document.getElementById('cliente_crear').value = selected.dataset.cliente;
                document.getElementById('telefono_crear').value = selected.dataset.telefono;
                infoDiv.style.display = 'flex';
            } else {
                infoDiv.style.display = 'none';
            }
        });

        // Inicializar Select2 al abrir cada modal y limpiar al cerrar
        $(document).ready(function () {
            $('.modal').on('shown.bs.modal', function () {
                var $modal = $(this);
                $modal.find('select[name="componentes[]"]').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                    $(this).select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Seleccione uno o varios componentes...',
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $modal,
                        language: {
                            noResults: function () { return "Sin resultados"; },
                            searching: function () { return "Buscando..."; }
                        }
                    });
                });
            });
            $('.modal').on('hidden.bs.modal', function () {
                $(this).find('select[name="componentes[]"]').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
            });

            // Abrir modal de asignacion si hay diagnostico pendiente
            <?php if ($diagnosticoPendiente): ?>
                var modal = new bootstrap.Modal(document.getElementById('asignarTecnicoModal<?php echo $diagnosticoPendiente; ?>'));
                modal.show();
            <?php endif; ?>
        });
    </script>
</body>

</html>