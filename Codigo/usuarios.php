<?php
/**
 * User management page - Admin only
 * Implements secure CRUD operations with prepared statements and CSRF protection
 */

include('auth.php');
include("conexion.php");

if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'Administrador') {
    header('Location: index.php');
    exit();
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Función para obtener todos los usuarios
function obtenerUsuarios($conn)
{
    $sql = "SELECT u.*, r.Nombre_Rol FROM Usuario u LEFT JOIN Rol r ON u.Rol_idRol = r.idRol";
    $result = $conn->query($sql);
    if (!$result) {
        die("Error en la consulta Usuarios: " . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener todos los roles
function obtenerRoles($conn)
{
    $sql = "SELECT * FROM Rol";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Crear usuario
if (isset($_POST['crear_usuario'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $primer_nombre = trim($_POST['primer_nombre'] ?? '');
        $segundo_nombre = trim($_POST['segundo_nombre'] ?? '');
        $primer_apellido = trim($_POST['primer_apellido'] ?? '');
        $segundo_apellido = trim($_POST['segundo_apellido'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $edad = (int)($_POST['edad'] ?? 0);
        $rol_id = (int)($_POST['rol_id'] ?? 0);

        // Validation
        $errors = [];
        if (empty($primer_nombre)) $errors[] = "Primer nombre es obligatorio.";
        if (empty($primer_apellido)) $errors[] = "Primer apellido es obligatorio.";
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "Correo electrónico inválido.";
        if (strlen($contrasena) < PASSWORD_MIN_LENGTH) $errors[] = "La contraseña debe tener al menos " . PASSWORD_MIN_LENGTH . " caracteres.";
        if ($edad < 18 || $edad > 100) $errors[] = "Edad debe estar entre 18 y 100 años.";
        if ($rol_id < 1) $errors[] = "Rol inválido.";

        // Check if email already exists
        if (empty($errors)) {
            $stmt_check = $conn->prepare("SELECT idUsuario FROM Usuario WHERE Correo = ?");
            $stmt_check->bind_param("s", $correo);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $errors[] = "El correo electrónico ya está registrado.";
            }
            $stmt_check->close();
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO Usuario (Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Correo, `Contraseña`, Edad, Rol_idRol) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('ssssssii', $primer_nombre, $segundo_nombre, $primer_apellido, $segundo_apellido, $correo, $contrasena, $edad, $rol_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Usuario creado con éxito</div>";
                } else {
                    error_log("Error creating user: " . $stmt->error);
                    echo "<div class='alert alert-danger'>Error al crear usuario.</div>";
                }
                $stmt->close();
            } else {
                error_log("Error preparing create statement: " . $conn->error);
                echo "<div class='alert alert-danger'>Error interno del servidor.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>" . implode("<br>", array_map('htmlspecialchars', $errors)) . "</div>";
        }
    }
}

// Actualizar usuario
if (isset($_POST['actualizar_usuario'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int)($_POST['id_usuario'] ?? 0);
        $primer_nombre = trim($_POST['primer_nombre'] ?? '');
        $segundo_nombre = trim($_POST['segundo_nombre'] ?? '');
        $primer_apellido = trim($_POST['primer_apellido'] ?? '');
        $segundo_apellido = trim($_POST['segundo_apellido'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $edad = (int)($_POST['edad'] ?? 0);
        $rol_id = (int)($_POST['rol_id'] ?? 0);

        // Validation
        $errors = [];
        if (empty($primer_nombre)) $errors[] = "Primer nombre es obligatorio.";
        if (empty($primer_apellido)) $errors[] = "Primer apellido es obligatorio.";
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "Correo electrónico inválido.";
        if ($edad < 18 || $edad > 100) $errors[] = "Edad debe estar entre 18 y 100 años.";
        if ($rol_id < 1) $errors[] = "Rol inválido.";

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE Usuario SET
                    Primer_Nombre=?, Segundo_Nombre=?, Primer_Apellido=?, Segundo_Apellido=?,
                    Correo=?, Edad=?, Rol_idRol=? WHERE idUsuario=?");
            if ($stmt) {
                $stmt->bind_param("sssssiii", $primer_nombre, $segundo_nombre, $primer_apellido,
                    $segundo_apellido, $correo, $edad, $rol_id, $id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Usuario actualizado con éxito</div>";
                } else {
                    error_log("Error updating user: " . $stmt->error);
                    echo "<div class='alert alert-danger'>Error al actualizar usuario.</div>";
                }
                $stmt->close();
            } else {
                error_log("Error preparing update statement: " . $conn->error);
                echo "<div class='alert alert-danger'>Error interno del servidor.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>" . implode("<br>", array_map('htmlspecialchars', $errors)) . "</div>";
        }
    }
}

// Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    $conn->begin_transaction();
    $error = false;

    $stmts = [
        "DELETE FROM Detalle_Diagnostico WHERE Arreglo_Usuario_idUsuario = ?",
        "DELETE FROM Detalle_Arreglo WHERE Arreglo_Usuario_idUsuario = ?",
        "DELETE FROM Arreglo WHERE Usuario_idUsuario = ?",
        "DELETE FROM Usuario WHERE idUsuario = ?"
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
        echo "<div class='alert alert-danger'>No se pudo eliminar el usuario: " . $conn->error . "</div>";
    } else {
        $conn->commit();
        echo "<div class='alert alert-success'>Usuario eliminado con éxito</div>";
    }
}

$usuarios = obtenerUsuarios($conn);
$roles = obtenerRoles($conn);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Listar Usuarios</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#crearUsuarioModal">
                    Crear Usuario
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Correo</th>
                                <th>Edad</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['idUsuario']; ?></td>
                                    <td><?php echo $usuario['Primer_Nombre'] . ' ' . $usuario['Segundo_Nombre']; ?></td>
                                    <td><?php echo $usuario['Primer_Apellido'] . ' ' . $usuario['Segundo_Apellido']; ?></td>
                                    <td><?php echo $usuario['Correo']; ?></td>
                                    <td><?php echo $usuario['Edad']; ?></td>
                                    <td><?php echo $usuario['Nombre_Rol']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#editarUsuarioModal<?php echo $usuario['idUsuario']; ?>">
                                            Editar
                                        </button>
                                        <a href="?eliminar=<?php echo $usuario['idUsuario']; ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('¿Estás seguro de que quieres eliminar este usuario?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear usuario -->
    <div class="modal fade" id="crearUsuarioModal" tabindex="-1" aria-labelledby="crearUsuarioModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearUsuarioModalLabel">Crear Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label for="primer_nombre" class="form-label">Primer Nombre</label>
                            <input type="text" class="form-control" id="primer_nombre" name="primer_nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="segundo_nombre" class="form-label">Segundo Nombre</label>
                            <input type="text" class="form-control" id="segundo_nombre" name="segundo_nombre">
                        </div>
                        <div class="mb-3">
                            <label for="primer_apellido" class="form-label">Primer Apellido</label>
                            <input type="text" class="form-control" id="primer_apellido" name="primer_apellido" required>
                        </div>
                        <div class="mb-3">
                            <label for="segundo_apellido" class="form-label">Segundo Apellido</label>
                            <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido">
                        </div>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo</label>
                            <input type="email" class="form-control" id="correo" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label for="contrasena" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="contrasena" name="contrasena" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="edad" class="form-label">Edad</label>
                            <input type="number" class="form-control" id="edad" name="edad" required min="18" max="100">
                        </div>
                        <div class="mb-3">
                            <label for="rol_id" class="form-label">Rol</label>
                            <select class="form-select" id="rol_id" name="rol_id" required>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?php echo htmlspecialchars($rol['idRol']); ?>"><?php echo htmlspecialchars($rol['Nombre_Rol']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="crear_usuario" class="btn btn-primary">Crear</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para editar usuario -->
    <?php foreach ($usuarios as $usuario): ?>
        <div class="modal fade" id="editarUsuarioModal<?php echo $usuario['idUsuario']; ?>" tabindex="-1"
            aria-labelledby="editarUsuarioModalLabel<?php echo $usuario['idUsuario']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarUsuarioModalLabel<?php echo $usuario['idUsuario']; ?>">Editar
                            Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="id_usuario" value="<?php echo $usuario['idUsuario']; ?>">
                            <div class="mb-3">
                                <label for="primer_nombre<?php echo $usuario['idUsuario']; ?>" class="form-label">Primer
                                    Nombre</label>
                                <input type="text" class="form-control"
                                    id="primer_nombre<?php echo $usuario['idUsuario']; ?>" name="primer_nombre"
                                    value="<?php echo htmlspecialchars($usuario['Primer_Nombre']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="segundo_nombre<?php echo $usuario['idUsuario']; ?>" class="form-label">Segundo
                                    Nombre</label>
                                <input type="text" class="form-control"
                                    id="segundo_nombre<?php echo $usuario['idUsuario']; ?>" name="segundo_nombre"
                                    value="<?php echo htmlspecialchars($usuario['Segundo_Nombre']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="primer_apellido<?php echo $usuario['idUsuario']; ?>" class="form-label">Primer
                                    Apellido</label>
                                <input type="text" class="form-control"
                                    id="primer_apellido<?php echo $usuario['idUsuario']; ?>" name="primer_apellido"
                                    value="<?php echo htmlspecialchars($usuario['Primer_Apellido']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="segundo_apellido<?php echo $usuario['idUsuario']; ?>" class="form-label">Segundo
                                    Apellido</label>
                                <input type="text" class="form-control"
                                    id="segundo_apellido<?php echo $usuario['idUsuario']; ?>" name="segundo_apellido"
                                    value="<?php echo htmlspecialchars($usuario['Segundo_Apellido']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="correo<?php echo $usuario['idUsuario']; ?>" class="form-label">Correo</label>
                                <input type="email" class="form-control" id="correo<?php echo $usuario['idUsuario']; ?>"
                                    name="correo" value="<?php echo htmlspecialchars($usuario['Correo']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="edad<?php echo $usuario['idUsuario']; ?>" class="form-label">Edad</label>
                                <input type="number" class="form-control" id="edad<?php echo $usuario['idUsuario']; ?>"
                                    name="edad" value="<?php echo $usuario['Edad']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="rol_id<?php echo $usuario['idUsuario']; ?>" class="form-label">Rol</label>
                                <select class="form-select" id="rol_id<?php echo $usuario['idUsuario']; ?>" name="rol_id"
                                    required>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?php echo $rol['idRol']; ?>" <?php echo ($rol['idRol'] == $usuario['Rol_idRol']) ? 'selected' : ''; ?>>
                                            <?php echo $rol['Nombre_Rol']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="actualizar_usuario" class="btn btn-primary">Actualizar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>