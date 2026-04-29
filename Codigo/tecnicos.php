<?php
/**
 * Gestionar Tecnicos (Solo Administrador)
 * - Admin puede resetear/cambiar contrasenas de cualquier tecnico
 */

include('auth.php');
include("conexion.php");

if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'Administrador') {
    header('Location: index.php');
    exit();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function obtenerTecnicos($conn) {
    $sql = "SELECT u.*, r.Nombre_Rol FROM Tecnico u LEFT JOIN Rol r ON u.Rol_idRol = r.idRol";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerRoles($conn) {
    $sql = "SELECT * FROM Rol";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Crear tecnico
if (isset($_POST['crear_tecnico'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $nombre_tecnico = trim($_POST['nombre_tecnico'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $rol_id = (int)($_POST['rol_id'] ?? 0);

        $errors = [];
        if (empty($nombre_tecnico)) $errors[] = "Nombre del tecnico es obligatorio.";
        if (empty($correo)) $errors[] = "Correo electronico es obligatorio.";
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "Correo electronico invalido.";
        if (empty($contrasena)) $errors[] = "Contrasena es obligatoria.";
        if ($rol_id < 1) $errors[] = "Rol es obligatorio.";

        if (empty($errors)) {
            $stmt_check = $conn->prepare("SELECT idTecnico FROM Tecnico WHERE Correo = ?");
            $stmt_check->bind_param("s", $correo);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $errors[] = "El correo electronico ya esta registrado.";
            }
            $stmt_check->close();
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO Tecnico (Nombre_Tecnico, Correo, `Contraseña`, Rol_idRol) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $nombre_tecnico, $correo, $contrasena, $rol_id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Tecnico creado con exito</div>";
            } else {
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error al crear tecnico.</div>";
            }
            $stmt->close();
        } else {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>" . implode("<br>", array_map('htmlspecialchars', $errors)) . "</div>";
        }
    }
    header("Location: tecnicos.php");
    exit();
}

// Actualizar tecnico
if (isset($_POST['actualizar_tecnico'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int)($_POST['id_tecnico'] ?? 0);
        $nombre_tecnico = trim($_POST['nombre_tecnico'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $rol_id = (int)($_POST['rol_id'] ?? 0);

        $errors = [];
        if (empty($nombre_tecnico)) $errors[] = "Nombre del tecnico es obligatorio.";
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "Correo electronico invalido.";
        if ($rol_id < 1) $errors[] = "Rol invalido.";

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE Tecnico SET Nombre_Tecnico=?, Correo=?, Rol_idRol=? WHERE idTecnico=?");
            $stmt->bind_param("ssii", $nombre_tecnico, $correo, $rol_id, $id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Tecnico actualizado con exito</div>";
            } else {
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error al actualizar tecnico.</div>";
            }
            $stmt->close();
        } else {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>" . implode("<br>", array_map('htmlspecialchars', $errors)) . "</div>";
        }
    }
    header("Location: tecnicos.php");
    exit();
}

// Restablecer contrasena (Admin puede cambiar contrasena de cualquier tecnico)
if (isset($_POST['restablecer_contrasena'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int)$_POST['id_tecnico'];
        $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
        $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

        if (empty($nueva_contrasena)) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>La nueva contrasena es obligatoria.</div>";
        } elseif ($nueva_contrasena !== $confirmar_contrasena) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Las contrasenas no coinciden.</div>";
        } else {
            $stmt = $conn->prepare("UPDATE Tecnico SET `Contraseña`=? WHERE idTecnico=?");
            $stmt->bind_param("si", $nueva_contrasena, $id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Contrasena restablecida con exito</div>";
            } else {
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error al restablecer contrasena.</div>";
            }
            $stmt->close();
        }
    }
    header("Location: tecnicos.php");
    exit();
}

// Eliminar tecnico
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    $conn->begin_transaction();
    $error = false;

    $stmts = [
        "DELETE FROM Detalle_Diagnostico_Componente WHERE Diagnostico_idDiagnostico IN (SELECT idDiagnostico FROM Diagnostico WHERE Arreglo_idArreglo IN (SELECT idArreglo FROM Arreglo WHERE Tecnico_idTecnico = ?))",
        "DELETE FROM Diagnostico WHERE Arreglo_idArreglo IN (SELECT idArreglo FROM Arreglo WHERE Tecnico_idTecnico = ?)",
        "DELETE FROM Detalle_Arreglo WHERE Arreglo_idArreglo IN (SELECT idArreglo FROM Arreglo WHERE Tecnico_idTecnico = ?)",
        "DELETE FROM Arreglo WHERE Tecnico_idTecnico = ?",
        "DELETE FROM Tecnico WHERE idTecnico = ?"
    ];

    foreach ($stmts as $query) {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            $error = true;
            break;
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            $error = true;
            $stmt->close();
            break;
        }
        $stmt->close();
    }

    if ($error) {
        $conn->rollback();
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>No se pudo eliminar el tecnico.</div>";
    } else {
        $conn->commit();
        $_SESSION['mensaje'] = "<div class='alert alert-success'>Tecnico eliminado correctamente</div>";
    }
    header("Location: tecnicos.php");
    exit();
}

// Mostrar mensaje
$mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

$tecnicos = obtenerTecnicos($conn);
$roles = obtenerRoles($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tecnicos - Sistema de Arreglo de Computadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <?php echo $mensaje; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Listar Tecnicos</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearTecnicoModal">
                    Crear Tecnico
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tecnicos as $tecnico): ?>
                            <tr>
                                <td><?php echo $tecnico['idTecnico']; ?></td>
                                <td><?php echo htmlspecialchars($tecnico['Nombre_Tecnico']); ?></td>
                                <td><?php echo htmlspecialchars($tecnico['Correo']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($tecnico['Nombre_Rol'] === 'Administrador') ? 'danger' : 'primary'; ?>">
                                        <?php echo htmlspecialchars($tecnico['Nombre_Rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarTecnicoModal<?php echo $tecnico['idTecnico']; ?>">
                                        Editar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#resetPassModal<?php echo $tecnico['idTecnico']; ?>">
                                        Cambiar Contrasena
                                    </button>
                                    <a href="?eliminar=<?php echo $tecnico['idTecnico']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estas seguro?')">Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear tecnico -->
    <div class="modal fade" id="crearTecnicoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Tecnico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label class="form-label">Nombre Tecnico (*)</label>
                            <input type="text" class="form-control" name="nombre_tecnico" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo Electronico (*)</label>
                            <input type="email" class="form-control" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contrasena (*)</label>
                            <input type="password" class="form-control" name="contrasena" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol (*)</label>
                            <select class="form-select" name="rol_id" required>
                                <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['idRol']; ?>"><?php echo htmlspecialchars($rol['Nombre_Rol']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="crear_tecnico" class="btn btn-primary">Crear</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para editar y resetear contrasena -->
    <?php foreach ($tecnicos as $tecnico): ?>
    <!-- Modal editar -->
    <div class="modal fade" id="editarTecnicoModal<?php echo $tecnico['idTecnico']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Tecnico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id_tecnico" value="<?php echo $tecnico['idTecnico']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Nombre Tecnico (*)</label>
                            <input type="text" class="form-control" name="nombre_tecnico" value="<?php echo htmlspecialchars($tecnico['Nombre_Tecnico']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo (*)</label>
                            <input type="email" class="form-control" name="correo" value="<?php echo htmlspecialchars($tecnico['Correo']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol (*)</label>
                            <select class="form-select" name="rol_id" required>
                                <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['idRol']; ?>" <?php echo ($rol['idRol'] == $tecnico['Rol_idRol']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rol['Nombre_Rol']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="actualizar_tecnico" class="btn btn-primary">Actualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal restablecer contrasena -->
    <div class="modal fade" id="resetPassModal<?php echo $tecnico['idTecnico']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Restablecer Contrasena</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Tecnico:</strong> <?php echo htmlspecialchars($tecnico['Nombre_Tecnico']); ?></p>
                    <p><strong>Correo:</strong> <?php echo htmlspecialchars($tecnico['Correo']); ?></p>
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id_tecnico" value="<?php echo $tecnico['idTecnico']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Nueva Contrasena (*)</label>
                            <input type="password" class="form-control" name="nueva_contrasena" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmar Contrasena (*)</label>
                            <input type="password" class="form-control" name="confirmar_contrasena" required>
                        </div>
                        <button type="submit" name="restablecer_contrasena" class="btn btn-warning">Restablecer Contrasena</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>