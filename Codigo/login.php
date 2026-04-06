<?php
session_start();
include("conexion.php");

// Si ya hay sesión activa, redirige directamente a la página principal
if (isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$correo = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($correo === '' || $password === '') {
        $error = 'Ingrese correo y contraseña.';
    } else {
        $stmt = $conn->prepare("SELECT idUsuario, Primer_Nombre, Primer_Apellido, `Contraseña` FROM Usuario WHERE Correo = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $correo);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($fila = $resultado->fetch_assoc()) {
                $storedPassword = $fila['Contraseña'];

                if (password_verify($password, $storedPassword) || $storedPassword === $password) {
                    $_SESSION['usuario'] = $fila['Primer_Nombre'] . ' ' . $fila['Primer_Apellido'];
                    $_SESSION['idUsuario'] = $fila['idUsuario'];
                    header("Location: index.php");
                    exit();
                } else {
                    $error = 'Contraseña incorrecta.';
                }
            } else {
                $error = 'Correo no registrado.';
            }

            $stmt->close();
        } else {
            $error = 'Error en la consulta de login: ' . $conn->error;
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
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Iniciar Sesión</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo</label>
                                <input type="email" class="form-control" id="correo" name="correo" placeholder="correo@ejemplo.com" required value="<?php echo htmlspecialchars($correo); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
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
