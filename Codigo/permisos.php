<?php
include('auth.php');
include('conexion.php');

$isAdmin = isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador';

if (!$isAdmin) {
    header('Location: index.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Crear tabla si no existe (primera vez)
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

// Guardar permisos
if (isset($_POST['guardar_permisos'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $tecnico_id = (int) $_POST['tecnico_id'];
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
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
    header('Location: permisos.php');
    exit();
}

// Cargar técnicos (no admins)
$tecnicos = [];
$result = $conn->query("SELECT idTecnico, Nombre_Tecnico, Correo FROM Tecnico WHERE Rol_idRol = 2 ORDER BY Nombre_Tecnico");
if ($result) {
    $tecnicos = $result->fetch_all(MYSQLI_ASSOC);
}

// Cargar todos los permisos actuales agrupados por técnico
$permisosActuales = [];
$result = $conn->query("SELECT Tecnico_idTecnico, modulo FROM Permiso_Modulo");
if ($result) {
    foreach ($result->fetch_all(MYSQLI_ASSOC) as $p) {
        $permisosActuales[$p['Tecnico_idTecnico']][] = $p['modulo'];
    }
}

$mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Permisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <?php echo $mensaje; ?>

    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header py-3">
                <h4 class="mb-0"><i class="bi bi-shield-lock"></i> Gestión de Permisos por Módulo</h4>
            </div>
            <div class="card-body">
                <?php if (empty($tecnicos)): ?>
                    <div class="alert alert-info">No hay técnicos registrados en el sistema.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Técnico</th>
                                <th>Correo</th>
                                <?php foreach ($modulosDisponibles as $clave => $info): ?>
                                    <th class="text-center"><?php echo htmlspecialchars($info['nombre']); ?></th>
                                <?php endforeach; ?>
                                <th class="text-center" style="width:110px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tecnicos as $tecnico):
                                $permisosDelTecnico = $permisosActuales[$tecnico['idTecnico']] ?? [];
                            ?>
                            <tr>
                                <form method="post" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="tecnico_id" value="<?php echo $tecnico['idTecnico']; ?>">
                                    <td class="fw-semibold"><i class="bi bi-person-badge me-1"></i><?php echo htmlspecialchars($tecnico['Nombre_Tecnico']); ?></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($tecnico['Correo']); ?></td>
                                    <?php foreach ($modulosDisponibles as $clave => $info): ?>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox"
                                                    name="modulos[]"
                                                    value="<?php echo $clave; ?>"
                                                    <?php echo in_array($clave, $permisosDelTecnico) ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="text-center">
                                        <button type="submit" name="guardar_permisos" class="btn btn-sm btn-primary" title="Guardar permisos">
                                            <i class="bi bi-floppy"></i> Guardar
                                        </button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle"></i>
                    Marque los módulos a los que desea dar acceso a cada técnico y haga clic en <strong>Guardar</strong>.
                    Los técnicos sin permisos adicionales solo pueden ver sus arreglos asignados.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
