<?php
/**
 * HU03: Gestionar Clientes (Solo Administrador)
 */

include('auth.php');
include("conexion.php");

if (!tienePermiso('clientes')) {
    header('Location: index.php');
    exit();
}
$isAdmin = isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = '';

// Funcion para obtener todos los clientes
function obtenerClientes($conn) {
    $sql = "SELECT * FROM Cliente";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// HU03 Escenario 1: Creacion Exitosa de Cliente
if (isset($_POST['crear_cliente'])) {
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
    $response = ['success' => false, 'errors' => []];

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $response['errors'][] = 'Error de seguridad. Intente nuevamente.';
    } else {
        $primer_nombre = trim($_POST['primer_nombre'] ?? '');
        $primer_apellido = trim($_POST['primer_apellido'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        // HU03 Escenario 2: Validacion de campos obligatorios
        if (empty($primer_nombre)) $response['errors'][] = 'Nombres es obligatorio.';
        if (empty($primer_apellido)) $response['errors'][] = 'Apellidos es obligatorio.';
        if (empty($telefono)) $response['errors'][] = 'Telefono es obligatorio.';
        if (empty($direccion)) $response['errors'][] = 'Direccion es obligatorio.';

        if (empty($response['errors'])) {
            $stmt = $conn->prepare("INSERT INTO Cliente (Primer_Nombre, Primer_Apellido, Telefono, Direccion) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $primer_nombre, $primer_apellido, $telefono, $direccion);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['cliente'] = [
                    'idCliente' => $stmt->insert_id,
                    'primer_nombre' => $primer_nombre,
                    'primer_apellido' => $primer_apellido,
                    'telefono' => $telefono,
                    'direccion' => $direccion
                ];
            } else {
                $response['errors'][] = 'Error: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    if ($response['success']) {
        $mensaje = "<div class='alert alert-success'>Cliente creado con exito</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>" . implode("<br>", $response['errors']) . "</div>";
    }
}

// Actualizar cliente
if (isset($_POST['actualizar_cliente'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensaje = "<div class='alert alert-danger'>Error de seguridad. Intente nuevamente.</div>";
    } else {
        $id = (int)$_POST['id_cliente'];
        $primer_nombre = trim($_POST['primer_nombre'] ?? '');
        $primer_apellido = trim($_POST['primer_apellido'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        $errors = [];
        if (empty($primer_nombre)) $errors[] = "Nombres es obligatorio.";
        if (empty($primer_apellido)) $errors[] = "Apellidos es obligatorio.";
        if (empty($telefono)) $errors[] = "Telefono es obligatorio.";
        if (empty($direccion)) $errors[] = "Direccion es obligatorio.";

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE Cliente SET Primer_Nombre=?, Primer_Apellido=?, Telefono=?, Direccion=? WHERE idCliente=?");
            $stmt->bind_param("ssssi", $primer_nombre, $primer_apellido, $telefono, $direccion, $id);
            
            if ($stmt->execute()) {
                $mensaje = "<div class='alert alert-success'>Cliente actualizado con exito</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $mensaje = "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
        }
    }
}

// Eliminar cliente
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    
    // Verificar si el cliente tiene arreglos asociados
    $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM Detalle_Arreglo WHERE Cliente_idCliente = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();
    
    if ($result['total'] > 0) {
        $mensaje = "<div class='alert alert-danger'>No se puede eliminar el cliente porque tiene arreglos asociados.</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM Cliente WHERE idCliente=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $mensaje = "<div class='alert alert-success'>Cliente eliminado con exito</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

$clientes = obtenerClientes($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema de Arreglo de Computadores</title>
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
                <h4 class="mb-0"><i class="bi bi-people"></i> Listar Clientes</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearClienteModal">
                    <i class="bi bi-plus-lg"></i> Crear Cliente
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered align-middle mb-0" style="font-size:0.9rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width:50px;">ID</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>Telefono</th>
                                <th>Direccion</th>
                                <th class="text-center" style="width:130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="clientesTablaBody">
                            <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td class="text-center fw-bold"><?php echo $cliente['idCliente']; ?></td>
                                <td><?php echo htmlspecialchars($cliente['Primer_Nombre']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['Primer_Apellido']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['Telefono']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['Direccion']); ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarClienteModal<?php echo $cliente['idCliente']; ?>" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?eliminar=<?php echo $cliente['idCliente']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estas seguro de que quieres eliminar este cliente?')" title="Eliminar">
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

    <!-- Modal para crear cliente -->
    <div class="modal fade" id="crearClienteModal" tabindex="-1" aria-labelledby="crearClienteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearClienteModalLabel">Crear Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post" id="formCrearCliente">
                        <div id="crearClienteAlert"></div>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label for="primer_nombre" class="form-label">Nombres (*)</label>
                            <input type="text" class="form-control" id="primer_nombre" name="primer_nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="primer_apellido" class="form-label">Apellidos (*)</label>
                            <input type="text" class="form-control" id="primer_apellido" name="primer_apellido" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Telefono (*)</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" required>
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Direccion (*)</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" required>
                        </div>
                        <button type="submit" name="crear_cliente" class="btn btn-primary">Crear</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para editar cliente -->
    <?php foreach ($clientes as $cliente): ?>
    <div class="modal fade" id="editarClienteModal<?php echo $cliente['idCliente']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id_cliente" value="<?php echo $cliente['idCliente']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Nombres (*)</label>
                            <input type="text" class="form-control" name="primer_nombre" value="<?php echo htmlspecialchars($cliente['Primer_Nombre']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apellidos (*)</label>
                            <input type="text" class="form-control" name="primer_apellido" value="<?php echo htmlspecialchars($cliente['Primer_Apellido']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefono (*)</label>
                            <input type="tel" class="form-control" name="telefono" value="<?php echo htmlspecialchars($cliente['Telefono']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Direccion (*)</label>
                            <input type="text" class="form-control" name="direccion" value="<?php echo htmlspecialchars($cliente['Direccion']); ?>" required>
                        </div>
                        <button type="submit" name="actualizar_cliente" class="btn btn-primary">Actualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const crearClienteForm = document.getElementById('formCrearCliente');
        const crearClienteAlert = document.getElementById('crearClienteAlert');
        const clientesTablaBody = document.getElementById('clientesTablaBody');

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function mostrarAlerta(html, type) {
            if (!crearClienteAlert) return;
            crearClienteAlert.innerHTML = `<div class="alert alert-${type}">${html}</div>`;
        }

        if (crearClienteForm) {
            crearClienteForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                const formData = new FormData(crearClienteForm);
                formData.set('ajax', '1');

                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        const newRow = document.createElement('tr');
                        newRow.innerHTML = `
                            <td>${escapeHtml(data.cliente.idCliente)}</td>
                            <td>${escapeHtml(data.cliente.primer_nombre)}</td>
                            <td>${escapeHtml(data.cliente.primer_apellido)}</td>
                            <td>${escapeHtml(data.cliente.telefono)}</td>
                            <td>${escapeHtml(data.cliente.direccion)}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" disabled>Editar</button>
                                <button type="button" class="btn btn-sm btn-danger" disabled>Eliminar</button>
                            </td>
                        `;
                        if (clientesTablaBody) {
                            clientesTablaBody.prepend(newRow);
                        }
                        mostrarAlerta('Cliente creado con exito', 'success');
                        const modalEl = document.getElementById('crearClienteModal');
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();
                        crearClienteForm.reset();
                    } else {
                        mostrarAlerta(data.errors.join('<br>'), 'danger');
                    }
                } catch (error) {
                    mostrarAlerta('Error de comunicacion. Intente nuevamente.', 'danger');
                }
            });
        }
    </script>
</body>
</html>