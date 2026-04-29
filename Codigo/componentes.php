<?php
/**
 * Gestion de Componentes (Solo Administrador)
 * Los componentes se usan en el modulo de diagnostico (multiselect).
 */

include('auth.php');
include("conexion.php");

// Solo administradores pueden acceder
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'Administrador') {
    header('Location: index.php');
    exit();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

const MAX_NOMBRE_COMPONENTE = 100;

// Obtener todos los componentes con conteo de uso en diagnosticos
function obtenerComponentes($conn) {
    $sql = "SELECT c.*, 
                   (SELECT COUNT(*) FROM Detalle_Diagnostico_Componente ddc 
                    WHERE ddc.Componente_idComponente = c.idComponente) AS Usos
            FROM Componente c
            ORDER BY c.Nombre_Componente";
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Error obteniendo componentes: " . $conn->error);
        return [];
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Validar nombre del componente
function validarNombreComponente($nombre) {
    $nombre = trim($nombre);
    if (empty($nombre)) {
        return "El nombre del componente es obligatorio.";
    }
    if (strlen($nombre) > MAX_NOMBRE_COMPONENTE) {
        return "El nombre no puede exceder " . MAX_NOMBRE_COMPONENTE . " caracteres.";
    }
    if (!preg_match('/^[a-zA-Z0-9\sáéíóúÁÉÍÓÚñÑ\-\/\.&]+$/u', $nombre)) {
        return "El nombre del componente contiene caracteres invalidos.";
    }
    return "";
}

// Verificar si ya existe un componente con el mismo nombre (case-insensitive)
function existeComponente($conn, $nombre, $idExcluir = 0) {
    $stmt = $conn->prepare("SELECT idComponente FROM Componente WHERE LOWER(Nombre_Componente) = LOWER(?) AND idComponente <> ?");
    $stmt->bind_param("si", $nombre, $idExcluir);
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;
    $stmt->close();
    return $existe;
}

// Crear componente
if (isset($_POST['crear_componente'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $nombre = trim($_POST['nombre_componente'] ?? '');
        $error = validarNombreComponente($nombre);

        if (!empty($error)) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
        } elseif (existeComponente($conn, $nombre)) {
            $_SESSION['mensaje'] = "<div class='alert alert-warning'>Ya existe un componente con ese nombre.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO Componente (Nombre_Componente) VALUES (?)");
            $stmt->bind_param("s", $nombre);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Componente creado con exito.</div>";
            } else {
                error_log("Error creando componente: " . $stmt->error);
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error al crear el componente.</div>";
            }
            $stmt->close();
        }
    }
    header("Location: componentes.php");
    exit();
}

// Actualizar componente
if (isset($_POST['actualizar_componente'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int)($_POST['id_componente'] ?? 0);
        $nombre = trim($_POST['nombre_componente'] ?? '');
        $error = validarNombreComponente($nombre);

        if ($id <= 0) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Componente invalido.</div>";
        } elseif (!empty($error)) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
        } elseif (existeComponente($conn, $nombre, $id)) {
            $_SESSION['mensaje'] = "<div class='alert alert-warning'>Ya existe otro componente con ese nombre.</div>";
        } else {
            $stmt = $conn->prepare("UPDATE Componente SET Nombre_Componente = ? WHERE idComponente = ?");
            $stmt->bind_param("si", $nombre, $id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Componente actualizado con exito.</div>";
            } else {
                error_log("Error actualizando componente: " . $stmt->error);
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error al actualizar el componente.</div>";
            }
            $stmt->close();
        }
    }
    header("Location: componentes.php");
    exit();
}

// Eliminar componente
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];

    if ($id > 0) {
        // Verificar si esta siendo usado en algun diagnostico
        $stmt_check = $conn->prepare("SELECT COUNT(*) AS total FROM Detalle_Diagnostico_Componente WHERE Componente_idComponente = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $total = $stmt_check->get_result()->fetch_assoc()['total'];
        $stmt_check->close();

        if ($total > 0) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>No se puede eliminar: este componente esta asociado a " . $total . " diagnostico(s).</div>";
        } else {
            $stmt = $conn->prepare("DELETE FROM Componente WHERE idComponente = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Componente eliminado con exito.</div>";
            } else {
                error_log("Error eliminando componente: " . $stmt->error);
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error al eliminar el componente.</div>";
            }
            $stmt->close();
        }
    }
    header("Location: componentes.php");
    exit();
}

// Mostrar y limpiar mensaje
$mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

$componentes = obtenerComponentes($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Componentes - Sistema de Arreglo de Computadores</title>
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
                <h2><i class="bi bi-cpu"></i> Componentes</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearComponenteModal">
                    <i class="bi bi-plus-lg"></i> Crear Componente
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Los componentes definidos aqui apareceran disponibles en el multiselect del modulo de Diagnostico.
                </p>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Componente</th>
                                <th class="text-center">Usos en Diagnosticos</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($componentes)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No hay componentes registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($componentes as $comp): ?>
                                <tr>
                                    <td><?php echo (int)$comp['idComponente']; ?></td>
                                    <td><?php echo htmlspecialchars($comp['Nombre_Componente']); ?></td>
                                    <td class="text-center">
                                        <?php if ((int)$comp['Usos'] > 0): ?>
                                            <span class="badge bg-info text-dark"><?php echo (int)$comp['Usos']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editarComponenteModal<?php echo $comp['idComponente']; ?>">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                        <?php if ((int)$comp['Usos'] === 0): ?>
                                            <a href="?eliminar=<?php echo $comp['idComponente']; ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Estas seguro de que quieres eliminar este componente?')">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    disabled
                                                    title="No se puede eliminar: esta en uso">
                                                <i class="bi bi-lock"></i> Eliminar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Componente -->
    <div class="modal fade" id="crearComponenteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Componente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label for="nombre_componente" class="form-label">Nombre del Componente (*)</label>
                            <input type="text" class="form-control" id="nombre_componente"
                                   name="nombre_componente" required
                                   maxlength="<?php echo MAX_NOMBRE_COMPONENTE; ?>"
                                   placeholder="Ej: Disco Duro / SSD">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_componente" class="btn btn-primary">Crear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modales Editar Componente -->
    <?php foreach ($componentes as $comp): ?>
    <div class="modal fade" id="editarComponenteModal<?php echo $comp['idComponente']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Componente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id_componente" value="<?php echo $comp['idComponente']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Componente (*)</label>
                            <input type="text" class="form-control" name="nombre_componente"
                                   value="<?php echo htmlspecialchars($comp['Nombre_Componente']); ?>"
                                   required maxlength="<?php echo MAX_NOMBRE_COMPONENTE; ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="actualizar_componente" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>