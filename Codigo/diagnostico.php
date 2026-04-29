<?php
/**
 * Gestionar Diagnostico Avanzado
 * - Selectores dinamicos: Arreglo, Tipo Dispositivo, Marca, Cliente
 * - Multiselect Select2 para componentes afectados (solo activos del inventario)
 * - Telefono del cliente visible
 */

include('auth.php');
include("conexion.php");

$isAdmin = isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador';
$idTecnicoActual = $_SESSION['idTecnico'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Solo componentes activos del inventario
function obtenerComponentes($conn) {
    $result = $conn->query("SELECT * FROM Componente WHERE Activo = 1 ORDER BY Nombre_Componente");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerArreglosParaDiagnostico($conn, $isAdmin, $idTecnico) {
    $sql = "SELECT a.*, td.Nombre_Tipo, m.Nombre_Marca, e.Nombre_Estado,
                   CONCAT(c.Primer_Nombre, ' ', c.Primer_Apellido) AS Nombre_Cliente,
                   c.Telefono AS Telefono_Cliente
            FROM Arreglo a
            JOIN Tipo_Dispositivo td ON a.Tipo_Dispositivo_idTipo = td.idTipoDispositivo
            JOIN Marca m ON a.Marca_idMarca = m.idMarca
            JOIN Estado e ON a.Estado_idEstado = e.idEstado
            LEFT JOIN Detalle_Arreglo da ON da.Arreglo_idArreglo = a.idArreglo
            LEFT JOIN Cliente c ON da.Cliente_idCliente = c.idCliente";
    if (!$isAdmin) $sql .= " WHERE a.Tecnico_idTecnico = " . (int)$idTecnico;
    $sql .= " ORDER BY a.idArreglo DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerDiagnosticos($conn, $isAdmin, $idTecnico) {
    $sql = "SELECT d.*, a.Nombre_Arreglo, td.Nombre_Tipo, m.Nombre_Marca,
                   CONCAT(c.Primer_Nombre, ' ', c.Primer_Apellido) AS Nombre_Cliente,
                   c.Telefono AS Telefono_Cliente
            FROM Diagnostico d
            JOIN Arreglo a ON d.Arreglo_idArreglo = a.idArreglo
            JOIN Tipo_Dispositivo td ON a.Tipo_Dispositivo_idTipo = td.idTipoDispositivo
            JOIN Marca m ON a.Marca_idMarca = m.idMarca
            LEFT JOIN Detalle_Arreglo da ON da.Arreglo_idArreglo = a.idArreglo
            LEFT JOIN Cliente c ON da.Cliente_idCliente = c.idCliente";
    if (!$isAdmin) $sql .= " WHERE a.Tecnico_idTecnico = " . (int)$idTecnico;
    $sql .= " ORDER BY d.idDiagnostico DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerComponentesDiagnostico($conn, $idDiagnostico) {
    $sql = "SELECT c.* FROM Detalle_Diagnostico_Componente ddc
            JOIN Componente c ON ddc.Componente_idComponente = c.idComponente
            WHERE ddc.Diagnostico_idDiagnostico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idDiagnostico);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Crear diagnostico
if (isset($_POST['crear_diagnostico'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $arreglo_id = (int)$_POST['arreglo_id'];
        $descripcion_problema = trim($_POST['descripcion_problema']);
        $valor_estimado = $_POST['valor_estimado'];
        $componentes = $_POST['componentes'] ?? [];

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO Diagnostico (Arreglo_idArreglo, Descripcion_Problema, Valor_Estimado) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $arreglo_id, $descripcion_problema, $valor_estimado);
            $stmt->execute();
            $idDiagnostico = $conn->insert_id;
            $stmt->close();

            if (!empty($componentes)) {
                $stmtComp = $conn->prepare("INSERT INTO Detalle_Diagnostico_Componente (Diagnostico_idDiagnostico, Componente_idComponente) VALUES (?, ?)");
                foreach ($componentes as $comp_id) {
                    $comp_id = (int)$comp_id;
                    $stmtComp->bind_param("ii", $idDiagnostico, $comp_id);
                    $stmtComp->execute();
                }
                $stmtComp->close();
            }
            $conn->commit();
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Diagnostico creado correctamente.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
    header("Location: diagnostico.php"); exit();
}

// Actualizar diagnostico
if (isset($_POST['actualizar_diagnostico'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int)$_POST['id_diagnostico'];
        $arreglo_id = (int)$_POST['arreglo_id'];
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
                    $comp_id = (int)$comp_id;
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
    header("Location: diagnostico.php"); exit();
}

// Eliminar diagnostico
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
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
    header("Location: diagnostico.php"); exit();
}

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

$diagnosticos = obtenerDiagnosticos($conn, $isAdmin, $idTecnicoActual);
$arreglos     = obtenerArreglosParaDiagnostico($conn, $isAdmin, $idTecnicoActual);
$componentes  = obtenerComponentes($conn);
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
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
    <style>
      .modal-body .select2-container { width: 100% !important; }
      .select2-container--open { z-index: 9999 !important; }
      .select2-container--bootstrap-5 .select2-selection { min-height: calc(2.25rem + 4px); }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <?php echo $mensaje; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Listar Diagnosticos</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearDiagnosticoModal">
                    Crear Diagnostico
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th><th>Arreglo</th><th>Tipo Dispositivo</th><th>Marca</th>
                                <th>Cliente</th><th>Telefono</th><th>Componentes</th><th>Valor Est.</th><th>Acciones</th>
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
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($c['Nombre_Componente']); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>$ <?php echo number_format($diagnostico['Valor_Estimado'], 0); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarDiagnosticoModal<?php echo $diagnostico['idDiagnostico']; ?>">Editar</button>
                                    <a href="?eliminar=<?php echo $diagnostico['idDiagnostico']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estas seguro?')">Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear -->
    <div class="modal fade" id="crearDiagnosticoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Diagnostico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Arreglo (*)</label>
                            <select class="form-select" name="arreglo_id" id="arreglo_select_crear" required>
                                <option value="">Seleccione un arreglo...</option>
                                <?php foreach ($arreglos as $arr): ?>
                                <option value="<?php echo $arr['idArreglo']; ?>"
                                        data-tipo="<?php echo htmlspecialchars($arr['Nombre_Tipo']); ?>"
                                        data-marca="<?php echo htmlspecialchars($arr['Nombre_Marca']); ?>"
                                        data-cliente="<?php echo htmlspecialchars($arr['Nombre_Cliente'] ?? '-'); ?>"
                                        data-telefono="<?php echo htmlspecialchars($arr['Telefono_Cliente'] ?? '-'); ?>">
                                    #<?php echo $arr['idArreglo']; ?> - <?php echo htmlspecialchars($arr['Nombre_Arreglo']); ?>
                                    (<?php echo htmlspecialchars($arr['Nombre_Tipo']); ?> - <?php echo htmlspecialchars($arr['Nombre_Marca']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row mb-3" id="info_arreglo_crear" style="display:none;">
                            <div class="col-md-3"><label class="form-label">Tipo Dispositivo</label><input type="text" class="form-control" id="tipo_disp_crear" readonly></div>
                            <div class="col-md-3"><label class="form-label">Marca</label><input type="text" class="form-control" id="marca_crear" readonly></div>
                            <div class="col-md-3"><label class="form-label">Cliente</label><input type="text" class="form-control" id="cliente_crear" readonly></div>
                            <div class="col-md-3"><label class="form-label">Telefono</label><input type="text" class="form-control" id="telefono_crear" readonly></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Componentes Afectados (*)</label>
                            <select class="form-select" name="componentes[]" id="componentes_crear" multiple required style="width: 100%">
                                <?php foreach ($componentes as $comp): ?>
                                <option value="<?php echo $comp['idComponente']; ?>">
                                    <?php echo htmlspecialchars($comp['Nombre_Componente']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Puede seleccionar uno o varios componentes. Use el campo de busqueda para filtrar.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion del Problema (*)</label>
                            <textarea class="form-control" name="descripcion_problema" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor Estimado ($) (*)</label>
                            <input type="number" step="0.01" class="form-control" name="valor_estimado" required>
                        </div>
                        <button type="submit" name="crear_diagnostico" class="btn btn-primary">Crear Diagnostico</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales Editar -->
    <?php foreach ($diagnosticos as $diagnostico):
        $compsActuales = obtenerComponentesDiagnostico($conn, $diagnostico['idDiagnostico']);
        $compsIds = array_column($compsActuales, 'idComponente'); ?>
    <div class="modal fade" id="editarDiagnosticoModal<?php echo $diagnostico['idDiagnostico']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Diagnostico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id_diagnostico" value="<?php echo $diagnostico['idDiagnostico']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Arreglo (*)</label>
                            <select class="form-select" name="arreglo_id" required>
                                <?php foreach ($arreglos as $arr): ?>
                                <option value="<?php echo $arr['idArreglo']; ?>" <?php echo ($arr['idArreglo'] == $diagnostico['Arreglo_idArreglo']) ? 'selected' : ''; ?>>
                                    #<?php echo $arr['idArreglo']; ?> - <?php echo htmlspecialchars($arr['Nombre_Arreglo']); ?>
                                    (<?php echo htmlspecialchars($arr['Nombre_Tipo']); ?> - <?php echo htmlspecialchars($arr['Nombre_Marca']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3"><label class="form-label">Cliente</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($diagnostico['Nombre_Cliente'] ?? '-'); ?>" readonly></div>
                            <div class="col-md-3"><label class="form-label">Telefono</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($diagnostico['Telefono_Cliente'] ?? '-'); ?>" readonly></div>
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
                            <textarea class="form-control" name="descripcion_problema" rows="3" required><?php echo htmlspecialchars($diagnostico['Descripcion_Problema']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor Estimado ($) (*)</label>
                            <input type="number" step="0.01" class="form-control" name="valor_estimado" value="<?php echo $diagnostico['Valor_Estimado']; ?>" required>
                        </div>
                        <button type="submit" name="actualizar_diagnostico" class="btn btn-primary">Actualizar Diagnostico</button>
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
        // Mostrar info del arreglo seleccionado al crear
        document.getElementById('arreglo_select_crear').addEventListener('change', function() {
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
        $(document).ready(function() {
            $('.modal').on('shown.bs.modal', function() {
                var $modal = $(this);
                $modal.find('select[name="componentes[]"]').each(function() {
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
                            noResults: function() { return "Sin resultados"; },
                            searching: function() { return "Buscando..."; }
                        }
                    });
                });
            });
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('select[name="componentes[]"]').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
            });
        });
    </script>
</body>
</html>