<?php
/**
 * Gestionar Tecnicos (Solo Administrador)
 * - Admin puede resetear/cambiar contrasenas de cualquier tecnico
 */

include('auth.php');
include("conexion.php");

$isAdmin = isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador';
if (!$isAdmin) {
    header('Location: index.php');
    exit();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auto-crear tabla de permisos si no existe
$conn->query("CREATE TABLE IF NOT EXISTS Permiso_Modulo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Tecnico_idTecnico INT NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_tecnico_modulo (Tecnico_idTecnico, modulo),
    FOREIGN KEY (Tecnico_idTecnico) REFERENCES Tecnico(idTecnico) ON DELETE CASCADE
)");

$modulosDisponibles = [
    'clientes'     => ['nombre' => 'Clientes',     'icono' => 'bi-people',      'descripcion' => 'Ver, crear, editar y eliminar clientes'],
    'dispositivos' => ['nombre' => 'Dispositivos', 'icono' => 'bi-laptop',      'descripcion' => 'Ver, crear, editar y eliminar tipos de dispositivo'],
    'marcas'       => ['nombre' => 'Marcas',       'icono' => 'bi-tags',        'descripcion' => 'Ver, crear, editar y eliminar marcas'],
    'arreglos'     => ['nombre' => 'Arreglos',     'icono' => 'bi-tools',       'descripcion' => 'Crear nuevos arreglos y ver todos los arreglos del sistema'],
];

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
    if (!$isAdmin) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Solo el administrador puede crear tecnicos.</div>";
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
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
        } elseif (strlen($nueva_contrasena) < 8) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>La nueva contrasena debe tener un minimo de 8 caracteres.</div>";
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

// Guardar permisos (solo admin)
if (isset($_POST['guardar_permisos'])) {
    if (!$isAdmin) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Solo el administrador puede gestionar permisos.</div>";
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $tecnico_id = (int)($_POST['tecnico_id'] ?? 0);
        $modulosSeleccionados = $_POST['modulos'] ?? [];
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM Permiso_Modulo WHERE Tecnico_idTecnico = ?");
            $stmt->bind_param("i", $tecnico_id);
            $stmt->execute();
            $stmt->close();
            if (!empty($modulosSeleccionados)) {
                $stmt = $conn->prepare("INSERT INTO Permiso_Modulo (Tecnico_idTecnico, modulo) VALUES (?, ?)");
                foreach ($modulosSeleccionados as $modulo) {
                    if (array_key_exists($modulo, $modulosDisponibles)) {
                        $stmt->bind_param("is", $tecnico_id, $modulo);
                        $stmt->execute();
                    }
                }
                $stmt->close();
            }
            $conn->commit();
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Permisos actualizados correctamente.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
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

// Cargar permisos actuales agrupados por tecnico (solo admin los necesita)
$permisosActuales = [];
if ($isAdmin) {
    $result = $conn->query("SELECT Tecnico_idTecnico, modulo FROM Permiso_Modulo");
    if ($result) {
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $p) {
            $permisosActuales[$p['Tecnico_idTecnico']][] = $p['modulo'];
        }
    }
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

    <div class="container-fluid px-4 mt-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h4 class="mb-0"><i class="bi bi-people"></i> Listar Tecnicos</h4>
                <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearTecnicoModal">
                    <i class="bi bi-plus-lg"></i> Crear Tecnico
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered align-middle mb-0" style="font-size:0.9rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width:50px;">ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th class="text-center">Rol</th>
                                <th class="text-center" style="width:130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tecnicos as $tecnico): ?>
                            <tr>
                                <td class="text-center fw-bold"><?php echo $tecnico['idTecnico']; ?></td>
                                <td><?php echo htmlspecialchars($tecnico['Nombre_Tecnico']); ?></td>
                                <td><?php echo htmlspecialchars($tecnico['Correo']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo ($tecnico['Nombre_Rol'] === 'Administrador') ? 'danger' : 'primary'; ?>">
                                        <?php echo htmlspecialchars($tecnico['Nombre_Rol']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarTecnicoModal<?php echo $tecnico['idTecnico']; ?>" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?eliminar=<?php echo $tecnico['idTecnico']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estas seguro?')" title="Eliminar">
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
                            <label class="form-label">Nombre (*)</label>
                            <input type="text" class="form-control" name="nombre_tecnico" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo (*)</label>
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

    <!-- Modales para editar tecnico (con pestana de cambio de contrasena) -->
    <?php foreach ($tecnicos as $tecnico): ?>
    <div class="modal fade" id="editarTecnicoModal<?php echo $tecnico['idTecnico']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Tecnico — <?php echo htmlspecialchars($tecnico['Nombre_Tecnico']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#datosTecnicoTab<?php echo $tecnico['idTecnico']; ?>" type="button">
                                <i class="bi bi-person"></i> Datos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#passTab<?php echo $tecnico['idTecnico']; ?>" type="button">
                                <i class="bi bi-key"></i> Contrasena
                            </button>
                        </li>
                        <?php if ($isAdmin && $tecnico['Rol_idRol'] != 1): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#permisosTab<?php echo $tecnico['idTecnico']; ?>" type="button">
                                <i class="bi bi-shield-lock"></i> Permisos
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <div class="tab-content">
                        <!-- Tab datos -->
                        <div class="tab-pane fade show active" id="datosTecnicoTab<?php echo $tecnico['idTecnico']; ?>">
                            <form action="" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="id_tecnico" value="<?php echo $tecnico['idTecnico']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Nombre (*)</label>
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
                        <!-- Tab cambiar contrasena -->
                        <div class="tab-pane fade" id="passTab<?php echo $tecnico['idTecnico']; ?>">
                            <form action="" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="id_tecnico" value="<?php echo $tecnico['idTecnico']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Nueva Contrasena (*) <small class="text-muted">(Minimo 8 caracteres)</small></label>
                                    <input type="password" class="form-control" name="nueva_contrasena" minlength="8" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirmar Contrasena (*)</label>
                                    <input type="password" class="form-control" name="confirmar_contrasena" required>
                                </div>
                                <button type="submit" name="restablecer_contrasena" class="btn btn-warning"><i class="bi bi-key"></i> Cambiar Contrasena</button>
                            </form>
                        </div>
                        <!-- Tab permisos (solo admin, solo para tecnicos no-admin) -->
                        <?php if ($isAdmin && $tecnico['Rol_idRol'] != 1):
                            $permisosDelTecnico = $permisosActuales[$tecnico['idTecnico']] ?? [];
                        ?>
                        <div class="tab-pane fade" id="permisosTab<?php echo $tecnico['idTecnico']; ?>">
                            <form action="" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="tecnico_id" value="<?php echo $tecnico['idTecnico']; ?>">
                                <table class="table table-bordered table-hover align-middle mb-3" style="font-size:0.9rem;">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width:40px;" class="text-center">Acceso</th>
                                            <th>Modulo</th>
                                            <th>Descripcion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modulosDisponibles as $clave => $info):
                                            $activo = in_array($clave, $permisosDelTecnico);
                                        ?>
                                        <tr class="<?php echo $activo ? 'table-success' : ''; ?>">
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center m-0">
                                                    <input class="form-check-input perm-check-<?php echo $tecnico['idTecnico']; ?>" type="checkbox"
                                                        name="modulos[]"
                                                        value="<?php echo $clave; ?>"
                                                        id="mod_<?php echo $clave . '_' . $tecnico['idTecnico']; ?>"
                                                        <?php echo $activo ? 'checked' : ''; ?>
                                                        onchange="this.closest('tr').className = this.checked ? 'table-success' : ''">
                                                </div>
                                            </td>
                                            <td class="fw-semibold">
                                                <i class="bi <?php echo $info['icono']; ?> me-1"></i>
                                                <?php echo htmlspecialchars($info['nombre']); ?>
                                            </td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($info['descripcion']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="guardar_permisos" class="btn btn-primary">
                                        <i class="bi bi-floppy"></i> Guardar Permisos
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary"
                                        onclick="document.querySelectorAll('.perm-check-<?php echo $tecnico['idTecnico']; ?>').forEach(c=>{c.checked=false;c.closest('tr').className=''})">
                                        Quitar todos
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>