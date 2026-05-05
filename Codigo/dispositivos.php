<?php
/**
 * Gestion de Tipos de Dispositivo (Solo Administrador)
 */

include('auth.php');
include("conexion.php");

if (!tienePermiso('dispositivos')) {
    header('Location: index.php');
    exit();
}
$isAdmin = isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Funcion para obtener todos los tipos de dispositivo
function obtenerTiposDispositivo($conn) {
    $sql = "SELECT * FROM Tipo_Dispositivo ORDER BY Nombre_Tipo";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Crear tipo de dispositivo
if (isset($_POST['crear_tipo'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $nombre_tipo = trim($_POST['nombre_tipo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (empty($nombre_tipo)) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>El nombre del tipo de dispositivo es obligatorio.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO Tipo_Dispositivo (Nombre_Tipo, Descripcion) VALUES (?, ?)");
            $stmt->bind_param("ss", $nombre_tipo, $descripcion);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Tipo de dispositivo creado con exito</div>";
            } else {
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error al crear tipo de dispositivo.</div>";
            }
            $stmt->close();
        }
    }
    header("Location: dispositivos.php");
    exit();
}

// Actualizar tipo de dispositivo
if (isset($_POST['actualizar_tipo'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int)$_POST['id_tipo'];
        $nombre_tipo = trim($_POST['nombre_tipo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (empty($nombre_tipo)) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>El nombre del tipo de dispositivo es obligatorio.</div>";
        } else {
            $stmt = $conn->prepare("UPDATE Tipo_Dispositivo SET Nombre_Tipo=?, Descripcion=? WHERE idTipoDispositivo=?");
            $stmt->bind_param("ssi", $nombre_tipo, $descripcion, $id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Tipo de dispositivo actualizado con exito</div>";
            } else {
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error al actualizar tipo de dispositivo.</div>";
            }
            $stmt->close();
        }
    }
    header("Location: dispositivos.php");
    exit();
}

// Eliminar tipo de dispositivo
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    
    // Verificar si hay arreglos usando este tipo
    $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM Arreglo WHERE Tipo_Dispositivo_idTipo = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();
    
    if ($result['total'] > 0) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>No se puede eliminar: hay arreglos asociados a este tipo de dispositivo.</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM Tipo_Dispositivo WHERE idTipoDispositivo=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Tipo de dispositivo eliminado con exito</div>";
        } else {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error al eliminar tipo de dispositivo.</div>";
        }
        $stmt->close();
    }
    header("Location: dispositivos.php");
    exit();
}

// Mostrar mensaje de sesion y limpiar
$mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

$tipos = obtenerTiposDispositivo($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipos de Dispositivo - Sistema de Arreglo de Computadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <?php echo $mensaje; ?>

    <div class="container-fluid px-4 mt-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h4 class="mb-0"><i class="bi bi-laptop"></i> Tipos de Dispositivo</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearTipoModal">
                    <i class="bi bi-plus-lg"></i> Crear Tipo de Dispositivo
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered align-middle mb-0" style="font-size:0.9rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width:50px;">ID</th>
                                <th>Nombre</th>
                                <th>Descripcion</th>
                                <th class="text-center" style="width:130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tipos as $tipo): ?>
                            <tr>
                                <td class="text-center fw-bold"><?php echo $tipo['idTipoDispositivo']; ?></td>
                                <td><?php echo htmlspecialchars($tipo['Nombre_Tipo']); ?></td>
                                <td><?php echo htmlspecialchars($tipo['Descripcion'] ?? ''); ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarTipoModal<?php echo $tipo['idTipoDispositivo']; ?>" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?eliminar=<?php echo $tipo['idTipoDispositivo']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estas seguro de que quieres eliminar este tipo de dispositivo?')" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </a>
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

    <!-- Modal para crear tipo -->
    <div class="modal fade" id="crearTipoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Tipo de Dispositivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label for="nombre_tipo" class="form-label">Nombre (*)</label>
                            <input type="text" class="form-control" id="nombre_tipo" name="nombre_tipo" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripcion</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                        </div>
                        <button type="submit" name="crear_tipo" class="btn btn-primary">Crear</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para editar tipo -->
    <?php foreach ($tipos as $tipo): ?>
    <div class="modal fade" id="editarTipoModal<?php echo $tipo['idTipoDispositivo']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Tipo de Dispositivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id_tipo" value="<?php echo $tipo['idTipoDispositivo']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Nombre (*)</label>
                            <input type="text" class="form-control" name="nombre_tipo" value="<?php echo htmlspecialchars($tipo['Nombre_Tipo']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea class="form-control" name="descripcion" rows="2"><?php echo htmlspecialchars($tipo['Descripcion'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="actualizar_tipo" class="btn btn-primary">Actualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>