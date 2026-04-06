<?php
// cliente_crud.php

// Conexión a la base de datos
include("conexion.php");

// Función para obtener todos los clientes
function obtenerClientes($conn) {
    $sql = "SELECT * FROM Cliente";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Crear cliente
if (isset($_POST['crear_cliente'])) {
    $primer_nombre = $_POST['primer_nombre'];
    $segundo_nombre = $_POST['segundo_nombre'];
    $primer_apellido = $_POST['primer_apellido'];
    $segundo_apellido = $_POST['segundo_apellido'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];

    $sql = "INSERT INTO Cliente (Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Telefono, Direccion) 
            VALUES ('$primer_nombre', '$segundo_nombre', '$primer_apellido', '$segundo_apellido', '$telefono', '$direccion')";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success'>Cliente creado con éxito</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

// Actualizar cliente
if (isset($_POST['actualizar_cliente'])) {
    $id = $_POST['id_cliente'];
    $primer_nombre = $_POST['primer_nombre'];
    $segundo_nombre = $_POST['segundo_nombre'];
    $primer_apellido = $_POST['primer_apellido'];
    $segundo_apellido = $_POST['segundo_apellido'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];

    $sql = "UPDATE Cliente SET 
            Primer_Nombre='$primer_nombre', 
            Segundo_Nombre='$segundo_nombre', 
            Primer_Apellido='$primer_apellido', 
            Segundo_Apellido='$segundo_apellido', 
            Telefono='$telefono', 
            Direccion='$direccion' 
            WHERE idCliente=$id";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success'>Cliente actualizado con éxito</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

// Eliminar cliente
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $sql = "DELETE FROM Cliente WHERE idCliente=$id";
    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success'>Cliente eliminado con éxito</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

$clientes = obtenerClientes($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Listar Clientes</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearClienteModal">
                    Crear Cliente
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
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?php echo $cliente['idCliente']; ?></td>
                                <td><?php echo $cliente['Primer_Nombre'] . ' ' . $cliente['Segundo_Nombre']; ?></td>
                                <td><?php echo $cliente['Primer_Apellido'] . ' ' . $cliente['Segundo_Apellido']; ?></td>
                                <td><?php echo $cliente['Telefono']; ?></td>
                                <td><?php echo $cliente['Direccion']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarClienteModal<?php echo $cliente['idCliente']; ?>">
                                        Editar
                                    </button>
                                    <a href="?eliminar=<?php echo $cliente['idCliente']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este cliente?')">Eliminar</a>
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
                    <form action="" method="post">
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
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" required>
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
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
    <div class="modal fade" id="editarClienteModal<?php echo $cliente['idCliente']; ?>" tabindex="-1" aria-labelledby="editarClienteModalLabel<?php echo $cliente['idCliente']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarClienteModalLabel<?php echo $cliente['idCliente']; ?>">Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="id_cliente" value="<?php echo $cliente['idCliente']; ?>">
                        <div class="mb-3">
                            <label for="primer_nombre<?php echo $cliente['idCliente']; ?>" class="form-label">Primer Nombre</label>
                            <input type="text" class="form-control" id="primer_nombre<?php echo $cliente['idCliente']; ?>" name="primer_nombre" value="<?php echo $cliente['Primer_Nombre']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="segundo_nombre<?php echo $cliente['idCliente']; ?>" class="form-label">Segundo Nombre</label>
                            <input type="text" class="form-control" id="segundo_nombre<?php echo $cliente['idCliente']; ?>" name="segundo_nombre" value="<?php echo $cliente['Segundo_Nombre']; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="primer_apellido<?php echo $cliente['idCliente']; ?>" class="form-label">Primer Apellido</label>
                            <input type="text" class="form-control" id="primer_apellido<?php echo $cliente['idCliente']; ?>" name="primer_apellido" value="<?php echo $cliente['Primer_Apellido']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="segundo_apellido<?php echo $cliente['idCliente']; ?>" class="form-label">Segundo Apellido</label>
                            <input type="text" class="form-control" id="segundo_apellido<?php echo $cliente['idCliente']; ?>" name="segundo_apellido" value="<?php echo $cliente['Segundo_Apellido']; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="telefono<?php echo $cliente['idCliente']; ?>" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono<?php echo $cliente['idCliente']; ?>" name="telefono" value="<?php echo $cliente['Telefono']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="direccion<?php echo $cliente['idCliente']; ?>" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion<?php echo $cliente['idCliente']; ?>" name="direccion" value="<?php echo $cliente['Direccion']; ?>" required>
                        </div>
                        <button type="submit" name="actualizar_cliente" class="btn btn-primary">Actualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>