<?php
/**
 * Modulo de Inventario (Solo Administrador).
 * Extiende la tabla Componente con: codigo_producto, codigo_barras,
 * marca, cantidad, precio, activo, fechas.
 * Los componentes activos siguen apareciendo en el multiselect de Diagnostico.
 */

include('auth.php');
include('conexion.php');

if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'Administrador') {
    header('Location: index.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

const MAX_NOMBRE = 100;
const MAX_CODIGO_PRODUCTO = 50;
const MAX_CODIGO_BARRAS = 100;

function obtenerInventario($conn) {
    $sql = "SELECT c.*, m.Nombre_Marca,
                   (SELECT COUNT(*) FROM Detalle_Factura df WHERE df.Componente_idComponente = c.idComponente) AS Veces_Facturado
            FROM Componente c
            LEFT JOIN Marca m ON c.Marca_idMarca = m.idMarca
            ORDER BY c.Activo DESC, c.Nombre_Componente";
    $r = $conn->query($sql);
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerMarcas($conn) {
    $r = $conn->query("SELECT * FROM Marca ORDER BY Nombre_Marca");
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function validarProducto($d) {
    if (empty(trim($d['nombre']))) return "El nombre es obligatorio.";
    if (strlen($d['nombre']) > MAX_NOMBRE) return "Nombre demasiado largo.";
    if (!empty($d['codigo_producto']) && strlen($d['codigo_producto']) > MAX_CODIGO_PRODUCTO) return "Codigo de producto demasiado largo.";
    if (!empty($d['codigo_barras']) && strlen($d['codigo_barras']) > MAX_CODIGO_BARRAS) return "Codigo de barras demasiado largo.";
    if (!is_numeric($d['cantidad']) || (int)$d['cantidad'] < 0) return "La cantidad debe ser un entero >= 0.";
    if (!is_numeric($d['precio']) || (float)$d['precio'] < 0) return "El precio debe ser >= 0.";
    return "";
}

// CREAR
if (isset($_POST['crear_producto'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad.</div>";
    } else {
        $d = [
            'nombre'          => trim($_POST['nombre_componente'] ?? ''),
            'codigo_producto' => trim($_POST['codigo_producto'] ?? ''),
            'codigo_barras'   => trim($_POST['codigo_barras'] ?? ''),
            'marca_id'        => (int)($_POST['marca_id'] ?? 0),
            'cantidad'        => $_POST['cantidad'] ?? 0,
            'precio'          => $_POST['precio'] ?? 0,
        ];
        $err = validarProducto($d);
        if ($err) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>$err</div>";
        } else {
            $marca = $d['marca_id'] > 0 ? $d['marca_id'] : null;
            $cp = $d['codigo_producto'] !== '' ? $d['codigo_producto'] : null;
            $cb = $d['codigo_barras']   !== '' ? $d['codigo_barras']   : null;
            $cant = (int)$d['cantidad'];
            $prec = (float)$d['precio'];
            $stmt = $conn->prepare("INSERT INTO Componente
                (Nombre_Componente, Codigo_Producto, Codigo_Barras, Marca_idMarca, Cantidad, Precio, Activo)
                VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssiid", $d['nombre'], $cp, $cb, $marca, $cant, $prec);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Producto agregado al inventario.</div>";
            } else {
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
            }
            $stmt->close();
        }
    }
    header("Location: inventario.php"); exit();
}

// ACTUALIZAR
if (isset($_POST['actualizar_producto'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad.</div>";
    } else {
        $id = (int)($_POST['id_componente'] ?? 0);
        $d = [
            'nombre'          => trim($_POST['nombre_componente'] ?? ''),
            'codigo_producto' => trim($_POST['codigo_producto'] ?? ''),
            'codigo_barras'   => trim($_POST['codigo_barras'] ?? ''),
            'marca_id'        => (int)($_POST['marca_id'] ?? 0),
            'cantidad'        => $_POST['cantidad'] ?? 0,
            'precio'          => $_POST['precio'] ?? 0,
        ];
        $err = validarProducto($d);
        if ($id <= 0) $err = "Producto invalido.";
        if ($err) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>$err</div>";
        } else {
            $marca = $d['marca_id'] > 0 ? $d['marca_id'] : null;
            $cp = $d['codigo_producto'] !== '' ? $d['codigo_producto'] : null;
            $cb = $d['codigo_barras']   !== '' ? $d['codigo_barras']   : null;
            $cant = (int)$d['cantidad'];
            $prec = (float)$d['precio'];
            $activo = isset($_POST['activo']) ? 1 : 0;
            $stmt = $conn->prepare("UPDATE Componente SET
                Nombre_Componente=?, Codigo_Producto=?, Codigo_Barras=?, Marca_idMarca=?,
                Cantidad=?, Precio=?, Activo=?
                WHERE idComponente=?");
            $stmt->bind_param("sssiidii", $d['nombre'], $cp, $cb, $marca, $cant, $prec, $activo, $id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "<div class='alert alert-success'>Producto actualizado.</div>";
            } else {
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
            }
            $stmt->close();
        }
    }
    header("Location: inventario.php"); exit();
}

// CAMBIO RAPIDO ACTIVO/INACTIVO
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE Componente SET Activo = 1 - Activo WHERE idComponente=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['mensaje'] = "<div class='alert alert-success'>Estado del producto actualizado.</div>";
    header("Location: inventario.php"); exit();
}

// ELIMINAR (solo si nunca se uso)
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $r = $conn->query("SELECT
            (SELECT COUNT(*) FROM Detalle_Diagnostico_Componente WHERE Componente_idComponente=$id) +
            (SELECT COUNT(*) FROM Detalle_Factura WHERE Componente_idComponente=$id) AS usos");
    $usos = (int)$r->fetch_assoc()['usos'];
    if ($usos > 0) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>No se puede eliminar: el producto esta asociado a diagnosticos o facturas. Puede inactivarlo.</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM Componente WHERE idComponente=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['mensaje'] = "<div class='alert alert-success'>Producto eliminado.</div>";
    }
    header("Location: inventario.php"); exit();
}

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

$inventario = obtenerInventario($conn);
$marcas = obtenerMarcas($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventario - Sistema de Arreglo de Computadores</title>
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
      <h2><i class="bi bi-box-seam"></i> Inventario de Componentes</h2>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearProductoModal">
        <i class="bi bi-plus-lg"></i> Agregar Producto
      </button>
    </div>
    <div class="card-body">
      <p class="text-muted small">Solo los productos <strong>Activos</strong> aparecen en el multiselect de Diagnostico y se pueden facturar.</p>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Cod. Producto</th>
              <th>Cod. Barras</th>
              <th>Marca</th>
              <th class="text-center">Stock</th>
              <th class="text-end">Precio</th>
              <th class="text-center">Estado</th>
              <th>Ingreso</th>
              <th>Modificacion</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($inventario)): ?>
              <tr><td colspan="11" class="text-center text-muted py-4">No hay productos en inventario.</td></tr>
            <?php else: foreach ($inventario as $p): ?>
              <tr class="<?php echo $p['Activo'] ? '' : 'table-secondary text-muted'; ?>">
                <td><?php echo (int)$p['idComponente']; ?></td>
                <td><?php echo htmlspecialchars($p['Nombre_Componente']); ?></td>
                <td><?php echo htmlspecialchars($p['Codigo_Producto'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($p['Codigo_Barras'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($p['Nombre_Marca'] ?? '-'); ?></td>
                <td class="text-center">
                  <?php
                    $cant = (int)$p['Cantidad'];
                    $cls = $cant === 0 ? 'bg-danger' : ($cant <= 5 ? 'bg-warning text-dark' : 'bg-success');
                  ?>
                  <span class="badge <?php echo $cls; ?>"><?php echo $cant; ?></span>
                </td>
                <td class="text-end">$ <?php echo number_format((float)$p['Precio'], 0, ',', '.'); ?></td>
                <td class="text-center">
                  <?php if ($p['Activo']): ?>
                    <span class="badge bg-success">Activo</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Inactivo</span>
                  <?php endif; ?>
                </td>
                <td><small><?php echo date('Y-m-d', strtotime($p['Fecha_Ingreso'])); ?></small></td>
                <td><small><?php echo date('Y-m-d H:i', strtotime($p['Fecha_Modificacion'])); ?></small></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarProductoModal<?php echo $p['idComponente']; ?>">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <a href="?toggle=<?php echo $p['idComponente']; ?>"
                     class="btn btn-sm <?php echo $p['Activo'] ? 'btn-secondary' : 'btn-success'; ?>"
                     onclick="return confirm('¿Cambiar estado del producto?')"
                     title="<?php echo $p['Activo'] ? 'Inactivar' : 'Activar'; ?>">
                    <i class="bi <?php echo $p['Activo'] ? 'bi-pause-circle' : 'bi-play-circle'; ?>"></i>
                  </a>
                  <?php if ((int)$p['Veces_Facturado'] === 0): ?>
                    <a href="?eliminar=<?php echo $p['idComponente']; ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('¿Eliminar definitivamente?')">
                      <i class="bi bi-trash"></i>
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="crearProductoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Agregar Producto al Inventario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Nombre del Componente (*)</label>
              <input type="text" class="form-control" name="nombre_componente" maxlength="<?php echo MAX_NOMBRE; ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Marca</label>
              <select class="form-select" name="marca_id">
                <option value="0">-- Sin marca --</option>
                <?php foreach ($marcas as $m): ?>
                  <option value="<?php echo $m['idMarca']; ?>"><?php echo htmlspecialchars($m['Nombre_Marca']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Codigo de Producto</label>
              <input type="text" class="form-control" name="codigo_producto" maxlength="<?php echo MAX_CODIGO_PRODUCTO; ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Codigo de Barras</label>
              <input type="text" class="form-control" name="codigo_barras" maxlength="<?php echo MAX_CODIGO_BARRAS; ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Cantidad en Stock (*)</label>
              <input type="number" class="form-control" name="cantidad" min="0" value="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Precio Unitario ($) (*)</label>
              <input type="number" class="form-control" name="precio" min="0" step="0.01" value="0" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" name="crear_producto" class="btn btn-primary">Agregar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modales Editar -->
<?php foreach ($inventario as $p): ?>
<div class="modal fade" id="editarProductoModal<?php echo $p['idComponente']; ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Editar Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <input type="hidden" name="id_componente" value="<?php echo $p['idComponente']; ?>">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Nombre del Componente (*)</label>
              <input type="text" class="form-control" name="nombre_componente"
                     value="<?php echo htmlspecialchars($p['Nombre_Componente']); ?>"
                     maxlength="<?php echo MAX_NOMBRE; ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Marca</label>
              <select class="form-select" name="marca_id">
                <option value="0">-- Sin marca --</option>
                <?php foreach ($marcas as $m): ?>
                  <option value="<?php echo $m['idMarca']; ?>" <?php echo $m['idMarca']==$p['Marca_idMarca']?'selected':''; ?>>
                    <?php echo htmlspecialchars($m['Nombre_Marca']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Codigo de Producto</label>
              <input type="text" class="form-control" name="codigo_producto"
                     value="<?php echo htmlspecialchars($p['Codigo_Producto'] ?? ''); ?>" maxlength="<?php echo MAX_CODIGO_PRODUCTO; ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Codigo de Barras</label>
              <input type="text" class="form-control" name="codigo_barras"
                     value="<?php echo htmlspecialchars($p['Codigo_Barras'] ?? ''); ?>" maxlength="<?php echo MAX_CODIGO_BARRAS; ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Cantidad (*)</label>
              <input type="number" class="form-control" name="cantidad" min="0" value="<?php echo (int)$p['Cantidad']; ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Precio ($) (*)</label>
              <input type="number" class="form-control" name="precio" min="0" step="0.01" value="<?php echo (float)$p['Precio']; ?>" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="activo" id="activo<?php echo $p['idComponente']; ?>" <?php echo $p['Activo']?'checked':''; ?>>
                <label class="form-check-label" for="activo<?php echo $p['idComponente']; ?>">Producto activo</label>
              </div>
            </div>
          </div>
          <small class="text-muted d-block mt-3">
            Ingreso: <?php echo $p['Fecha_Ingreso']; ?> &nbsp;|&nbsp;
            Ultima modificacion: <?php echo $p['Fecha_Modificacion']; ?>
          </small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" name="actualizar_producto" class="btn btn-primary">Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>