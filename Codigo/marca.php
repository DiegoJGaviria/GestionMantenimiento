<?php
// Conexión a la base de datos
include("conexion.php");

// Función para obtener todas las marcas
function obtenerMarcas($conn)
{
    $sql = "SELECT * FROM Marca";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Crear marca
if (isset($_POST['crear_marca'])) {
    $nombre = $_POST['nombre_marca'];
    $sql = "INSERT INTO Marca (Nombre_Marca) VALUES ('$nombre')";
    if ($conn->query($sql) === TRUE) {
        echo "Marca creada con éxito";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Actualizar marca
if (isset($_POST['actualizar_marca'])) {
    $id = $_POST['id_marca'];
    $nombre = $_POST['nombre_marca'];
    $sql = "UPDATE Marca SET Nombre_Marca='$nombre' WHERE idMarca=$id";
    if ($conn->query($sql) === TRUE) {
        echo "Marca actualizada con éxito";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Eliminar marca
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $sql = "DELETE FROM Marca WHERE idMarca=$id";
    if ($conn->query($sql) === TRUE) {
        echo "Marca eliminada con éxito";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
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
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Listar Marcas</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearMarcaModal">
                    Crear Marca
                </button>
            </div>
            <div class="card-body">
                <table class="table">
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
                                <td><?php echo $marca['idMarca']; ?></td>
                                <td><?php echo $marca['Nombre_Marca']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarMarcaModal<?php echo $marca['idMarca']; ?>">
                                        Editar
                                    </button>
                                    <a href="?eliminar=<?php echo $marca['idMarca']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar esta marca?')">Eliminar</a>
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
                        <div class="mb-3">
                            <label for="nombre_marca" class="form-label">Nombre de la Marca</label>
                            <input type="text" class="form-control" id="nombre_marca" name="nombre_marca" required>
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
                            <input type="hidden" name="id_marca" value="<?php echo $marca['idMarca']; ?>">
                            <div class="mb-3">
                                <label for="nombre_marca<?php echo $marca['idMarca']; ?>" class="form-label">Nombre de la Marca</label>
                                <input type="text" class="form-control" id="nombre_marca<?php echo $marca['idMarca']; ?>" name="nombre_marca" value="<?php echo $marca['Nombre_Marca']; ?>" required>
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