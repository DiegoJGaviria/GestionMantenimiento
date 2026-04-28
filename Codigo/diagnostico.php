<?php
/**
 * HU05: Gestionar Diagnostico (Tecnico)
 */

include('auth.php');
include("conexion.php");

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = '';

// Funcion para obtener todos los diagnosticos
function obtenerDiagnosticos($conn) {
    $sql = "SELECT * FROM Diagnostico";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Funcion para obtener detalles de un diagnostico
function obtenerDetallesDiagnostico($conn, $idDiagnostico) {
    $sql = "SELECT dd.*, a.Nombre_Arreglo, m.Nombre_Marca, u.Nombre_Tecnico 
            FROM Detalle_Diagnostico dd 
            JOIN Arreglo a ON dd.Arreglo_idArreglo = a.idArreglo 
            JOIN Marca m ON a.Marca_idMarca = m.idMarca 
            JOIN Tecnico u ON a.Tecnico_idTecnico = u.idTecnico 
            WHERE dd.Diagnostico_idDiagnostico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idDiagnostico);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Funcion para obtener arreglos (preferiblemente en diagnostico segun HU05)
function obtenerArreglos($conn) {
    $sql = "SELECT a.*, m.Nombre_Marca, u.Nombre_Tecnico, e.Nombre_Estado
            FROM Arreglo a 
            JOIN Marca m ON a.Marca_idMarca = m.idMarca 
            JOIN Tecnico u ON a.Tecnico_idTecnico = u.idTecnico
            JOIN Estado e ON a.Estado_idEstado = e.idEstado
            ORDER BY a.Estado_idEstado ASC, a.idArreglo DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Crear diagnostico
if (isset($_POST['crear_diagnostico'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensaje = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $componente = trim($_POST['componente']);
        $valor = $_POST['valor'];
        $descripcion = trim($_POST['descripcion']);
        $detalles_json = $_POST['detalles_diagnostico'] ?? '[]';

        $conn->begin_transaction();
        try {
            // HU05: Almacena datos en Diagnostico
            $stmt = $conn->prepare("INSERT INTO Diagnostico (Componente, Valor, Descripcion) VALUES (?, ?, ?)");
            $stmt->bind_param("sds", $componente, $valor, $descripcion);
            $stmt->execute();
            $idDiagnostico = $conn->insert_id;
            $stmt->close();

            // HU05: Actualiza el vinculo en Detalle_Diagnostico
            $detalles = json_decode($detalles_json, true);
            if (!empty($detalles) && is_array($detalles)) {
                $stmtDetalle = $conn->prepare("INSERT INTO Detalle_Diagnostico (Arreglo_idArreglo, Diagnostico_idDiagnostico) VALUES (?, ?)");
                foreach ($detalles as $det) {
                    $arreglo_id = (int)$det['arreglo_id'];
                    $stmtDetalle->bind_param("ii", $arreglo_id, $idDiagnostico);
                    $stmtDetalle->execute();
                }
                $stmtDetalle->close();
            }
            
            $conn->commit();
            $mensaje = "<div class='alert alert-success'>Diagnostico creado correctamente.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Actualizar diagnostico
if (isset($_POST['actualizar_diagnostico'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensaje = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int)$_POST['id_diagnostico'];
        $componente = trim($_POST['componente']);
        $valor = $_POST['valor'];
        $descripcion = trim($_POST['descripcion']);
        $detalles_json = $_POST['detalles_diagnostico'] ?? '[]';

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE Diagnostico SET Componente=?, Valor=?, Descripcion=? WHERE idDiagnostico=?");
            $stmt->bind_param("sdsi", $componente, $valor, $descripcion, $id);
            $stmt->execute();
            $stmt->close();

            // Eliminar detalles existentes
            $stmt_delete = $conn->prepare("DELETE FROM Detalle_Diagnostico WHERE Diagnostico_idDiagnostico=?");
            $stmt_delete->bind_param("i", $id);
            $stmt_delete->execute();
            $stmt_delete->close();

            // Insertar nuevos detalles (solo columnas existentes)
            $detalles = json_decode($detalles_json, true);
            if (!empty($detalles) && is_array($detalles)) {
                $stmtDetalle = $conn->prepare("INSERT INTO Detalle_Diagnostico (Arreglo_idArreglo, Diagnostico_idDiagnostico) VALUES (?, ?)");
                foreach ($detalles as $detalle) {
                    $arreglo_id = (int)$detalle['arreglo_id'];
                    $stmtDetalle->bind_param("ii", $arreglo_id, $id);
                    $stmtDetalle->execute();
                }
                $stmtDetalle->close();
            }
            
            $conn->commit();
            $mensaje = "<div class='alert alert-success'>Diagnostico actualizado con exito</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Eliminar diagnostico
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    
    $conn->begin_transaction();
    try {
        $stmt_delete_detalles = $conn->prepare("DELETE FROM Detalle_Diagnostico WHERE Diagnostico_idDiagnostico=?");
        $stmt_delete_detalles->bind_param("i", $id);
        $stmt_delete_detalles->execute();
        $stmt_delete_detalles->close();
        
        $stmt_delete = $conn->prepare("DELETE FROM Diagnostico WHERE idDiagnostico=?");
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        $conn->commit();
        $mensaje = "<div class='alert alert-success'>Diagnostico eliminado con exito</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
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
    <title>Diagnosticos - Sistema de Arreglo de Computadores</title>
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
                                <th>ID</th>
                                <th>Componente</th>
                                <th>Valor</th>
                                <th>Descripcion</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diagnosticos as $diagnostico): ?>
                            <tr>
                                <td><?php echo $diagnostico['idDiagnostico']; ?></td>
                                <td><?php echo htmlspecialchars($diagnostico['Componente']); ?></td>
                                <td>$ <?php echo number_format($diagnostico['Valor'], 0); ?></td>
                                <td><?php echo htmlspecialchars($diagnostico['Descripcion']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarDiagnosticoModal<?php echo $diagnostico['idDiagnostico']; ?>">
                                        Editar
                                    </button>
                                    <a href="?eliminar=<?php echo $diagnostico['idDiagnostico']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estas seguro de que quieres eliminar este diagnostico?')">Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear diagnostico -->
    <div class="modal fade" id="crearDiagnosticoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Diagnostico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="crearDiagnosticoForm" action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label for="componente" class="form-label">Componente afectado (*)</label>
                            <input type="text" class="form-control" id="componente" name="componente" required>
                        </div>
                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor de la reparacion (*)</label>
                            <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripcion tecnica (*)</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                        </div>
                        <h5 class="mt-4">Arreglos Vinculados</h5>
                        <table class="table table-bordered" id="detallesTable">
                            <thead>
                                <tr>
                                    <th>Arreglo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <button type="button" class="btn btn-secondary" onclick="agregarDetalle()">Agregar Arreglo</button>
                        <input type="hidden" id="detalles_diagnostico" name="detalles_diagnostico" value="[]">
                        <div class="mt-3">
                            <button type="submit" name="crear_diagnostico" class="btn btn-primary">Crear Diagnostico</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para editar diagnostico -->
    <?php foreach ($diagnosticos as $diagnostico): ?>
    <div class="modal fade" id="editarDiagnosticoModal<?php echo $diagnostico['idDiagnostico']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Diagnostico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editarDiagnosticoForm<?php echo $diagnostico['idDiagnostico']; ?>" action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id_diagnostico" value="<?php echo $diagnostico['idDiagnostico']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Componente afectado (*)</label>
                            <input type="text" class="form-control" name="componente" value="<?php echo htmlspecialchars($diagnostico['Componente']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor de la reparacion (*)</label>
                            <input type="number" step="0.01" class="form-control" name="valor" value="<?php echo $diagnostico['Valor']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion tecnica (*)</label>
                            <textarea class="form-control" name="descripcion" rows="3" required><?php echo htmlspecialchars($diagnostico['Descripcion']); ?></textarea>
                        </div>
                        <h5 class="mt-4">Arreglos Vinculados</h5>
                        <table class="table table-bordered" id="detallesTable<?php echo $diagnostico['idDiagnostico']; ?>">
                            <thead>
                                <tr>
                                    <th>Arreglo</th>
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
                                            <option value="<?php echo $arreglo['idArreglo']; ?>" <?php echo ($arreglo['idArreglo'] == $detalle['Arreglo_idArreglo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($arreglo['Nombre_Arreglo'] . ' - ' . $arreglo['Nombre_Marca'] . ' (' . $arreglo['Nombre_Estado'] . ')'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarDetalle(this)">Eliminar</button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-secondary" onclick="agregarDetalle(<?php echo $diagnostico['idDiagnostico']; ?>)">Agregar Arreglo</button>
                        <input type="hidden" id="detalles_diagnostico<?php echo $diagnostico['idDiagnostico']; ?>" name="detalles_diagnostico" value="[]">
                        <div class="mt-3">
                            <button type="submit" name="actualizar_diagnostico" class="btn btn-primary">Actualizar Diagnostico</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script>
        function agregarDetalle(diagnosticoId = '') {
            const tableId = diagnosticoId ? `detallesTable${diagnosticoId}` : 'detallesTable';
            const table = document.getElementById(tableId);
            const tbody = table.getElementsByTagName('tbody')[0];
            const newRow = tbody.insertRow();
            newRow.innerHTML = `
                <td>
                    <select class="form-select arreglo-select" name="arreglo_id[]" required>
                        <?php foreach ($arreglos as $arreglo): ?>
                        <option value="<?php echo $arreglo['idArreglo']; ?>">
                            <?php echo htmlspecialchars($arreglo['Nombre_Arreglo'] . ' - ' . $arreglo['Nombre_Marca'] . ' (' . $arreglo['Nombre_Estado'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
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
            if (!table) return;
            
            const tbody = table.getElementsByTagName('tbody')[0];
            if (!tbody) return;
            
            const rows = tbody.rows;
            const detalles = [];

            for (let i = 0; i < rows.length; i++) {
                const arregloSelect = rows[i].querySelector('.arreglo-select');
                if (arregloSelect) {
                    detalles.push({ arreglo_id: arregloSelect.value });
                }
            }

            const inputId = diagnosticoId ? `detalles_diagnostico${diagnosticoId}` : 'detalles_diagnostico';
            const input = document.getElementById(inputId);
            if (input) {
                input.value = JSON.stringify(detalles);
            }
        }

        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('arreglo-select')) {
                const table = event.target.closest('table');
                const diagnosticoId = table.id.replace('detallesTable', '');
                actualizarDetallesJSON(diagnosticoId);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            actualizarDetallesJSON();
            <?php foreach ($diagnosticos as $diagnostico): ?>
            actualizarDetallesJSON('<?php echo $diagnostico['idDiagnostico']; ?>');
            <?php endforeach; ?>
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>