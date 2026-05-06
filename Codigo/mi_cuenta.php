<?php
/**
 * HU07: Gestionar Cuenta
 * Permite a cualquier usuario actualizar sus datos personales y contraseña
 */

include('auth.php');
include("conexion.php");

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = '';
$idTecnico = $_SESSION['idTecnico'];

// Obtener datos del usuario actual
$stmt = $conn->prepare("SELECT * FROM Tecnico WHERE idTecnico = ?");
$stmt->bind_param("i", $idTecnico);
$stmt->execute();
$misDatos = $stmt->get_result()->fetch_assoc();
$stmt->close();

// HU07 Escenario 1: Actualizacion Exitosa de Datos Personales
if (isset($_POST['actualizar_datos'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensaje = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $nombre_tecnico = trim($_POST['nombre_tecnico'] ?? '');
        $correo = trim($_POST['correo'] ?? '');

        $errors = [];
        if (empty($nombre_tecnico)) $errors[] = "Nombre es obligatorio.";
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "Correo electronico invalido.";

        // Verificar que el correo no este en uso por otro usuario
        $stmt_check = $conn->prepare("SELECT idTecnico FROM Tecnico WHERE Correo = ? AND idTecnico != ?");
        $stmt_check->bind_param("si", $correo, $idTecnico);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errors[] = "El correo electronico ya esta en uso por otro usuario.";
        }
        $stmt_check->close();

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE Tecnico SET Nombre_Tecnico=?, Correo=? WHERE idTecnico=?");
            $stmt->bind_param("ssi", $nombre_tecnico, $correo, $idTecnico);
            if ($stmt->execute()) {
                $_SESSION['tecnico'] = $nombre_tecnico;
                $misDatos['Nombre_Tecnico'] = $nombre_tecnico;
                $misDatos['Correo'] = $correo;
                $mensaje = "<div class='alert alert-success'>Datos actualizados correctamente</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al actualizar datos.</div>";
            }
            $stmt->close();
        } else {
            $mensaje = "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
        }
    }
}

// HU07 Escenario 2 y 3: Cambio de Contraseña
if (isset($_POST['cambiar_contrasena'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensaje = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $contrasena_actual = $_POST['contrasena_actual'] ?? '';
        $contrasena_nueva = $_POST['contrasena_nueva'] ?? '';
        $contrasena_confirmar = $_POST['contrasena_confirmar'] ?? '';

        // Obtener contraseña actual de la BD
        $stmt = $conn->prepare("SELECT `Contraseña` FROM Tecnico WHERE idTecnico = ?");
        $stmt->bind_param("i", $idTecnico);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $storedPassword = $result['Contraseña'];
        $stmt->close();

        // HU07 Escenario 3: Verificar contraseña actual
        if ($storedPassword !== $contrasena_actual && !password_verify($contrasena_actual, $storedPassword)) {
            $mensaje = "<div class='alert alert-danger'>La contraseña actual es incorrecta</div>";
        } elseif (empty($contrasena_nueva)) {
            $mensaje = "<div class='alert alert-danger'>La nueva contraseña no puede estar vacia</div>";
        } elseif (strlen($contrasena_nueva) < 8) {
            $mensaje = "<div class='alert alert-danger'>La nueva contraseña debe tener un minimo de 8 caracteres</div>";
        } elseif ($contrasena_nueva !== $contrasena_confirmar) {
            $mensaje = "<div class='alert alert-danger'>Las contraseñas nuevas no coinciden</div>";
        } else {
            // HU07 Escenario 2: Cambio exitoso
            $stmt = $conn->prepare("UPDATE Tecnico SET `Contraseña`=? WHERE idTecnico=?");
            $stmt->bind_param("si", $contrasena_nueva, $idTecnico);
            if ($stmt->execute()) {
                $mensaje = "<div class='alert alert-success'>Contraseña actualizada exitosamente</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al actualizar contraseña.</div>";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - Sistema de Arreglo de Computadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <?php echo $mensaje; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="bi bi-person-gear"></i> Actualizar Datos Personales</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="mb-3">
                                <label for="nombre_tecnico" class="form-label">Nombre (*)</label>
                                <input type="text" class="form-control" id="nombre_tecnico" name="nombre_tecnico" value="<?php echo htmlspecialchars($misDatos['Nombre_Tecnico']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo Electronico (*)</label>
                                <input type="email" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($misDatos['Correo']); ?>" required>
                            </div>
                            <button type="submit" name="actualizar_datos" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Guardar cambios
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="bi bi-key"></i> Cambiar Contraseña</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="mb-3">
                                <label for="contrasena_actual" class="form-label">Contraseña Actual (*)</label>
                                <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual" required>
                            </div>
                            <div class="mb-3">
                                <label for="contrasena_nueva" class="form-label">Nueva Contraseña (*) <small class="text-muted">(Mínimo 8 caracteres)</small></label>
                                <input type="password" class="form-control" id="contrasena_nueva" name="contrasena_nueva" minlength="8" required>
                            </div>
                            <div class="mb-3">
                                <label for="contrasena_confirmar" class="form-label">Confirmar Nueva Contraseña (*)</label>
                                <input type="password" class="form-control" id="contrasena_confirmar" name="contrasena_confirmar" required>
                            </div>
                            <button type="submit" name="cambiar_contrasena" class="btn btn-warning">
                                <i class="bi bi-shield-lock"></i> Cambiar Contraseña
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>