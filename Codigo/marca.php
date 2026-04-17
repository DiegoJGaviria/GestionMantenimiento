<?php
/**
 * Marcas management page
 * Implements secure CRUD operations with prepared statements and CSRF protection
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("conexion.php");
include("auth.php"); // Include authentication

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Constants for validation
const MAX_NOMBRE_LENGTH = 100;

// Functions
/**
 * Get all brands from database
 * @param mysqli $conn Database connection
 * @return array Array of brands
 */
function obtenerMarcas($conn) {
    $sql = "SELECT * FROM Marca ORDER BY Nombre_Marca";
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Error getting brands: " . $conn->error);
        return [];
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Validate brand name
 * @param string $nombre Brand name
 * @return string Error message or empty string if valid
 */
function validarNombreMarca($nombre) {
    $nombre = trim($nombre);
    if (empty($nombre)) {
        return "El nombre de la marca es obligatorio.";
    }
    if (strlen($nombre) > MAX_NOMBRE_LENGTH) {
        return "El nombre de la marca no puede exceder " . MAX_NOMBRE_LENGTH . " caracteres.";
    }
    if (!preg_match('/^[a-zA-Z0-9\s\-&]+$/', $nombre)) {
        return "El nombre de la marca contiene caracteres inválidos.";
    }
    return "";
}

// Handle POST requests
$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensaje = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        // Create brand
        if (isset($_POST['crear_marca'])) {
            $nombre = trim($_POST['nombre_marca'] ?? '');
            $error = validarNombreMarca($nombre);

            if (empty($error)) {
                $stmt = $conn->prepare("INSERT INTO Marca (Nombre_Marca) VALUES (?)");
                if ($stmt) {
                    $stmt->bind_param("s", $nombre);
                    if ($stmt->execute()) {
                        $mensaje = "<div class='alert alert-success'>Marca creada con éxito.</div>";
                    } else {
                        error_log("Error creating brand: " . $stmt->error);
                        $mensaje = "<div class='alert alert-danger'>Error al crear la marca.</div>";
                    }
                    $stmt->close();
                } else {
                    error_log("Error preparing create statement: " . $conn->error);
                    $mensaje = "<div class='alert alert-danger'>Error interno del servidor.</div>";
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
            }
        }

        // Update brand
        elseif (isset($_POST['actualizar_marca'])) {
            $id = (int)($_POST['id_marca'] ?? 0);
            $nombre = trim($_POST['nombre_marca'] ?? '');
            $error = validarNombreMarca($nombre);

            if (empty($error) && $id > 0) {
                $stmt = $conn->prepare("UPDATE Marca SET Nombre_Marca = ? WHERE idMarca = ?");
                if ($stmt) {
                    $stmt->bind_param("si", $nombre, $id);
                    if ($stmt->execute()) {
                        $mensaje = "<div class='alert alert-success'>Marca actualizada con éxito.</div>";
                    } else {
                        error_log("Error updating brand: " . $stmt->error);
                        $mensaje = "<div class='alert alert-danger'>Error al actualizar la marca.</div>";
                    }
                    $stmt->close();
                } else {
                    error_log("Error preparing update statement: " . $conn->error);
                    $mensaje = "<div class='alert alert-danger'>Error interno del servidor.</div>";
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>Datos inválidos para actualizar.</div>";
            }
        }
    }
}

// Handle GET requests (DELETE)
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    if ($id > 0) {
        // Check if brand is being used
        $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM Arreglo WHERE Marca_idMarca = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $count = $result_check->fetch_assoc()['count'];
        $stmt_check->close();

        if ($count > 0) {
            $mensaje = "<div class='alert alert-danger'>No se puede eliminar la marca porque está siendo utilizada en arreglos.</div>";
        } else {
            $stmt = $conn->prepare("DELETE FROM Marca WHERE idMarca = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $mensaje = "<div class='alert alert-success'>Marca eliminada con éxito.</div>";
                } else {
                    error_log("Error deleting brand: " . $stmt->error);
                    $mensaje = "<div class='alert alert-danger'>Error al eliminar la marca.</div>";
                }
                $stmt->close();
            } else {
                error_log("Error preparing delete statement: " . $conn->error);
                $mensaje = "<div class='alert alert-danger'>Error interno del servidor.</div>";
            }
        }
    }
}

$marcas = obtenerMarcas($conn);     
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <?php if ($mensaje): echo $mensaje; endif; ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Listar Marcas</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearMarcaModal">
                    Crear Marca
                </button>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($marcas as $marca): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($marca['idMarca']); ?></td>
                                <td><?php echo htmlspecialchars($marca['Nombre_Marca']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarMarcaModal<?php echo $marca['idMarca']; ?>">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <a href="?eliminar=<?php echo $marca['idMarca']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar esta marca?')">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para crear marca -->
    <div class="modal fade" id="crearMarcaModal" tabindex="-1" aria-labelledby="crearMarcaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearMarcaModalLabel">Crear Nueva Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label for="nombre_marca" class="form-label">Nombre de la Marca</label>
                            <input type="text" class="form-control" id="nombre_marca" name="nombre_marca" required maxlength="<?php echo MAX_NOMBRE_LENGTH; ?>">
                        </div>
                        <button type="submit" name="crear_marca" class="btn btn-primary">Crear</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para editar marca -->
    <?php foreach ($marcas as $marca): ?>
        <div class="modal fade" id="editarMarcaModal<?php echo $marca['idMarca']; ?>" tabindex="-1" aria-labelledby="editarMarcaModalLabel<?php echo $marca['idMarca']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarMarcaModalLabel<?php echo $marca['idMarca']; ?>">Editar Marca</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="id_marca" value="<?php echo $marca['idMarca']; ?>">
                            <div class="mb-3">
                                <label for="nombre_marca<?php echo $marca['idMarca']; ?>" class="form-label">Nombre de la Marca</label>
                                <input type="text" class="form-control" id="nombre_marca<?php echo $marca['idMarca']; ?>" name="nombre_marca" value="<?php echo htmlspecialchars($marca['Nombre_Marca']); ?>" required maxlength="<?php echo MAX_NOMBRE_LENGTH; ?>">
                            </div>
                            <button type="submit" name="actualizar_marca" class="btn btn-primary">Actualizar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>