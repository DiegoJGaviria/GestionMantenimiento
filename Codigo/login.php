<?php
/**
 * Login page for the computer repair management system
 * Implements secure authentication with prepared statements and CSRF protection
 */

session_start();
include("conexion.php");

// Constants for error messages
const ERROR_MISSING_CREDENTIALS = 'Por favor, ingrese correo y contraseña.';
const ERROR_INVALID_EMAIL = 'Formato de correo electrónico inválido.';
const ERROR_INVALID_CREDENTIALS = 'Credenciales incorrectas.';
const ERROR_DATABASE = 'Error interno del servidor. Intente nuevamente.';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if already logged in
if (isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$correo = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        // Log CSRF attempt
        include_once 'logger.php';
        logSecurityEvent('CSRF_ATTEMPT', 'Intento de ataque CSRF detectado');

        $error = ERROR_INVALID_CREDENTIALS;
    } else {
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';

        // Input validation
        if (empty($correo) || empty($password)) {
            $error = ERROR_MISSING_CREDENTIALS;
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = ERROR_INVALID_EMAIL;
        } else {
            // Database query with prepared statement
            $stmt = $conn->prepare("SELECT u.idUsuario, u.Primer_Nombre, u.Primer_Apellido, u.`Contraseña`, u.Rol_idRol, r.Nombre_Rol FROM Usuario u LEFT JOIN Rol r ON u.Rol_idRol = r.idRol WHERE u.Correo = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $correo);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($fila = $resultado->fetch_assoc()) {
                    $storedPassword = $fila['Contraseña'];

                    // Verify password using secure hashing
                    if (password_verify($password, $storedPassword)) {
                        // Set session variables
                        $_SESSION['usuario'] = $fila['Primer_Nombre'] . ' ' . $fila['Primer_Apellido'];
                        $_SESSION['idUsuario'] = $fila['idUsuario'];
                        $_SESSION['idRol'] = $fila['Rol_idRol'];
                        $_SESSION['rol_nombre'] = $fila['Nombre_Rol'] ?: 'Usuario';

                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        // Log successful login
                        include_once 'logger.php';
                        logAuthEvent('LOGIN_SUCCESS', 'Usuario autenticado exitosamente', $correo);

                        header("Location: index.php");
                        exit();
                    } else {
                        // Log failed login attempt
                        include_once 'logger.php';
                        logAuthEvent('LOGIN_FAILED', 'Intento de login fallido - contraseña incorrecta', $correo);

                        $error = ERROR_INVALID_CREDENTIALS;
                    }
                } else {
                    $error = ERROR_INVALID_CREDENTIALS;
                }

                $stmt->close();
            } else {
                $error = ERROR_DATABASE;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">


</head>

<body class="bg-light" style="background: linear-gradient(135deg, #1e3c72, #2a5298);min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;">

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4 p-md-5" style="background-color: #ffffff
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2)" ;>
                        <div class="text-center mb-3" style="font-size: 8rem;">
                            <i class="bi bi-person-circle me-3"></i>
                        </div>
                        <h2 class="card-title text-center mb-4">
                            Iniciar Sesión
                        </h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo</label>
                                <input type="email" class="form-control" id="correo" name="correo"
                                    placeholder="correo@ejemplo.com" required
                                    value="<?php echo htmlspecialchars($correo); ?>"
                                    style="background-color: #f8f9fa; border-color: #2a5298; font-weight: bold;">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Contraseña" required
                                    style="background-color: #f8f9fa; border-color: #2a5298; font-weight: bold;">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Ingresar</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>

</html>