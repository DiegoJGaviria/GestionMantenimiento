<?php
/**
 * Modulo de Facturacion (Solo Administrador).
 * Lista arreglos finalizados/entregados sin facturar y permite generar factura PDF.
 * Al generar la factura: descuenta stock, guarda Factura + Detalle_Factura.
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

function obtenerArreglosFacturables($conn) {
    $sql = "SELECT a.*, e.Nombre_Estado, m.Nombre_Marca, td.Nombre_Tipo,
                   c.idCliente, c.Primer_Nombre, c.Primer_Apellido, c.Telefono, c.Direccion
            FROM Arreglo a
            JOIN Estado e ON a.Estado_idEstado = e.idEstado
            JOIN Marca m ON a.Marca_idMarca = m.idMarca
            JOIN Tipo_Dispositivo td ON a.Tipo_Dispositivo_idTipo = td.idTipoDispositivo
            JOIN Detalle_Arreglo da ON da.Arreglo_idArreglo = a.idArreglo
            JOIN Cliente c ON c.idCliente = da.Cliente_idCliente
            WHERE a.Estado_idEstado IN (3,4)
              AND NOT EXISTS (SELECT 1 FROM Factura f WHERE f.Arreglo_idArreglo = a.idArreglo)
            ORDER BY a.idArreglo DESC";
    $r = $conn->query($sql);
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerFacturasEmitidas($conn) {
    $sql = "SELECT f.*, a.Nombre_Arreglo, c.Primer_Nombre, c.Primer_Apellido
            FROM Factura f
            JOIN Arreglo a ON f.Arreglo_idArreglo = a.idArreglo
            JOIN Detalle_Arreglo da ON da.Arreglo_idArreglo = a.idArreglo
            JOIN Cliente c ON c.idCliente = da.Cliente_idCliente
            ORDER BY f.idFactura DESC";
    $r = $conn->query($sql);
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerComponentesArreglo($conn, $idArreglo) {
    $sql = "SELECT DISTINCT c.idComponente, c.Nombre_Componente, c.Codigo_Producto,
                   c.Cantidad AS Stock, c.Precio, c.Activo
            FROM Diagnostico d
            JOIN Detalle_Diagnostico_Componente ddc ON ddc.Diagnostico_idDiagnostico = d.idDiagnostico
            JOIN Componente c ON c.idComponente = ddc.Componente_idComponente
            WHERE d.Arreglo_idArreglo = ?
            ORDER BY c.Nombre_Componente";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idArreglo);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $r;
}

function generarNumeroFactura($conn) {
    $r = $conn->query("SELECT IFNULL(MAX(idFactura),0)+1 AS sig FROM Factura");
    $sig = (int)$r->fetch_assoc()['sig'];
    return 'F-' . date('Y') . '-' . str_pad($sig, 6, '0', STR_PAD_LEFT);
}

// === GENERAR FACTURA ===
if (isset($_POST['generar_factura'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error de seguridad.</div>";
        header("Location: facturacion.php"); exit();
    }
    $idArreglo  = (int)$_POST['id_arreglo'];
    $manoObra   = (float)($_POST['mano_obra'] ?? 0);
    $ivaPct     = (float)($_POST['iva_pct'] ?? 0);
    $observ     = trim($_POST['observaciones'] ?? '');
    $items      = $_POST['items'] ?? [];

    if ($idArreglo <= 0) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Arreglo invalido.</div>";
        header("Location: facturacion.php"); exit();
    }

    $conn->begin_transaction();
    try {
        $subComp = 0.0;
        $detalles = [];
        foreach ($items as $idComp => $row) {
            $idComp = (int)$idComp;
            $cant   = (int)($row['cantidad'] ?? 0);
            $prec   = (float)($row['precio'] ?? 0);
            if ($cant <= 0) continue;

            $stmt = $conn->prepare("SELECT Nombre_Componente, Codigo_Producto, Cantidad, Activo FROM Componente WHERE idComponente=? FOR UPDATE");
            $stmt->bind_param("i", $idComp);
            $stmt->execute();
            $info = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$info)               throw new Exception("Componente $idComp no existe.");
            if (!$info['Activo'])     throw new Exception("El componente '{$info['Nombre_Componente']}' esta inactivo.");
            if ($info['Cantidad'] < $cant) throw new Exception("Stock insuficiente para '{$info['Nombre_Componente']}'. Disponible: {$info['Cantidad']}.");

            $sub = $cant * $prec;
            $subComp += $sub;
            $detalles[] = [
                'idComponente'    => $idComp,
                'nombre'          => $info['Nombre_Componente'],
                'codigo_producto' => $info['Codigo_Producto'],
                'cantidad'        => $cant,
                'precio'          => $prec,
                'subtotal'        => $sub,
            ];
        }

        $subtotal = $subComp + $manoObra;
        $ivaVal   = round($subtotal * ($ivaPct / 100), 2);
        $total    = $subtotal + $ivaVal;

        $numero = generarNumeroFactura($conn);
        $tecEmisor = (int)$_SESSION['idTecnico'];
        $stmt = $conn->prepare("INSERT INTO Factura
            (Numero_Factura, Arreglo_idArreglo, Subtotal_Componentes, Mano_Obra, Subtotal,
             Iva_Porcentaje, Iva_Valor, Total, Tecnico_Emisor, Observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siddddddss", $numero, $idArreglo, $subComp, $manoObra, $subtotal, $ivaPct, $ivaVal, $total, $tecEmisor, $observ);
        $stmt->execute();
        $idFactura = $conn->insert_id;
        $stmt->close();

        $stmtDet = $conn->prepare("INSERT INTO Detalle_Factura
            (Factura_idFactura, Componente_idComponente, Nombre_Componente, Codigo_Producto, Cantidad, Precio_Unitario, Subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtStock = $conn->prepare("UPDATE Componente SET Cantidad = Cantidad - ? WHERE idComponente=?");
        foreach ($detalles as $d) {
            $stmtDet->bind_param("iissidd", $idFactura, $d['idComponente'], $d['nombre'], $d['codigo_producto'], $d['cantidad'], $d['precio'], $d['subtotal']);
            $stmtDet->execute();
            $stmtStock->bind_param("ii", $d['cantidad'], $d['idComponente']);
            $stmtStock->execute();
        }
        $stmtDet->close();
        $stmtStock->close();

        $conn->commit();
        header("Location: factura_pdf.php?id=" . $idFactura);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error al generar la factura: " . htmlspecialchars($e->getMessage()) . "</div>";
        header("Location: facturacion.php"); exit();
    }
}

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

$facturables = obtenerArreglosFacturables($conn);
$emitidas    = obtenerFacturasEmitidas($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Facturacion - Sistema de Arreglo de Computadores</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="estils.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<?php echo $mensaje; ?>

<div class="container mt-4">
  <div class="card mb-4">
    <div class="card-header">
      <h2><i class="bi bi-receipt"></i> Arreglos pendientes de facturar</h2>
    </div>
    <div class="card-body">
      <?php if (empty($facturables)): ?>
        <p class="text-muted text-center py-3">No hay arreglos finalizados pendientes de facturar.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th>ID</th><th>Cliente</th><th>Dispositivo</th><th>Descripcion</th>
                <th>Estado</th><th class="text-end">Mano de Obra</th><th class="text-end">Accion</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($facturables as $a): ?>
              <tr>
                <td><?php echo (int)$a['idArreglo']; ?></td>
                <td><?php echo htmlspecialchars($a['Primer_Nombre'].' '.$a['Primer_Apellido']); ?></td>
                <td><?php echo htmlspecialchars($a['Nombre_Tipo'].' '.$a['Nombre_Marca']); ?></td>
                <td><?php echo htmlspecialchars($a['Nombre_Arreglo']); ?></td>
                <td><span class="badge bg-success"><?php echo htmlspecialchars($a['Nombre_Estado']); ?></span></td>
                <td class="text-end">$ <?php echo number_format((float)$a['Valor_Pago'], 0, ',', '.'); ?></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#facturarModal<?php echo $a['idArreglo']; ?>">
                    <i class="bi bi-file-earmark-pdf"></i> Generar Factura
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2><i class="bi bi-archive"></i> Facturas emitidas</h2></div>
    <div class="card-body">
      <?php if (empty($emitidas)): ?>
        <p class="text-muted text-center py-3">Aun no se han emitido facturas.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th>N°</th><th>Cliente</th><th>Arreglo</th><th>Fecha</th>
                <th class="text-end">Total</th><th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($emitidas as $f): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($f['Numero_Factura']); ?></strong></td>
                <td><?php echo htmlspecialchars($f['Primer_Nombre'].' '.$f['Primer_Apellido']); ?></td>
                <td><?php echo htmlspecialchars($f['Nombre_Arreglo']); ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($f['Fecha_Emision'])); ?></td>
                <td class="text-end"><strong>$ <?php echo number_format((float)$f['Total'], 0, ',', '.'); ?></strong></td>
                <td class="text-end">
                  <a href="factura_pdf.php?id=<?php echo $f['idFactura']; ?>" target="_blank" class="btn btn-sm btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Ver PDF
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php foreach ($facturables as $a):
    $componentesArr = obtenerComponentesArreglo($conn, $a['idArreglo']);
?>
<div class="modal fade" id="facturarModal<?php echo $a['idArreglo']; ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Generar Factura - Arreglo #<?php echo (int)$a['idArreglo']; ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <input type="hidden" name="id_arreglo" value="<?php echo $a['idArreglo']; ?>">

          <div class="row mb-3">
            <div class="col-md-6">
              <h6>Cliente</h6>
              <div class="bg-light p-2 rounded small">
                <strong><?php echo htmlspecialchars($a['Primer_Nombre'].' '.$a['Primer_Apellido']); ?></strong><br>
                Tel: <?php echo htmlspecialchars($a['Telefono']); ?><br>
                Dir: <?php echo htmlspecialchars($a['Direccion']); ?>
              </div>
            </div>
            <div class="col-md-6">
              <h6>Dispositivo</h6>
              <div class="bg-light p-2 rounded small">
                <strong><?php echo htmlspecialchars($a['Nombre_Tipo']); ?></strong>
                - <?php echo htmlspecialchars($a['Nombre_Marca']); ?><br>
                Arreglo: <?php echo htmlspecialchars($a['Nombre_Arreglo']); ?><br>
                Detalle: <?php echo htmlspecialchars($a['Descripcion_Cliente']); ?>
              </div>
            </div>
          </div>

          <h6>Componentes utilizados (detectados en diagnosticos)</h6>
          <?php if (empty($componentesArr)): ?>
            <p class="text-muted small">No se asignaron componentes en los diagnosticos. Solo se facturara mano de obra.</p>
          <?php else: ?>
            <div class="table-responsive mb-3">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>Componente</th><th>Stock</th>
                    <th style="width:120px">Cantidad</th>
                    <th style="width:140px">Precio Unit.</th>
                    <th class="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($componentesArr as $c): ?>
                  <tr <?php if (!$c['Activo'] || $c['Stock']<=0) echo 'class="table-warning"'; ?>>
                    <td>
                      <?php echo htmlspecialchars($c['Nombre_Componente']); ?>
                      <?php if (!$c['Activo']): ?><span class="badge bg-secondary">Inactivo</span><?php endif; ?>
                      <?php if ($c['Stock']<=0): ?><span class="badge bg-danger">Sin stock</span><?php endif; ?>
                    </td>
                    <td><?php echo (int)$c['Stock']; ?></td>
                    <td>
                      <input type="number" class="form-control form-control-sm cantidad-input"
                             name="items[<?php echo $c['idComponente']; ?>][cantidad]"
                             min="0" max="<?php echo (int)$c['Stock']; ?>"
                             value="<?php echo $c['Stock']>0 && $c['Activo'] ? 1 : 0; ?>">
                    </td>
                    <td>
                      <input type="number" class="form-control form-control-sm precio-input"
                             name="items[<?php echo $c['idComponente']; ?>][precio]"
                             min="0" step="0.01" value="<?php echo (float)$c['Precio']; ?>">
                    </td>
                    <td class="text-end subtotal-cell">$ 0</td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Mano de Obra ($)</label>
              <input type="number" class="form-control" name="mano_obra"
                     min="0" step="0.01" value="<?php echo (float)$a['Valor_Pago']; ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">IVA (%)</label>
              <input type="number" class="form-control" name="iva_pct"
                     min="0" max="100" step="0.01" value="0">
            </div>
            <div class="col-md-3">
              <label class="form-label">Total</label>
              <input type="text" class="form-control fw-bold total-show" value="$ 0" readonly>
            </div>
            <div class="col-12 mt-3">
              <label class="form-label">Observaciones</label>
              <textarea class="form-control" name="observaciones" rows="2" placeholder="Observaciones adicionales..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" name="generar_factura" class="btn btn-primary">
            <i class="bi bi-file-earmark-pdf"></i> Generar Factura PDF
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.modal').forEach(modal => {
  const recalcular = () => {
    let subComp = 0;
    modal.querySelectorAll('tbody tr').forEach(tr => {
      const cant   = parseFloat(tr.querySelector('.cantidad-input')?.value || 0);
      const precio = parseFloat(tr.querySelector('.precio-input')?.value || 0);
      const sub = cant * precio;
      const cell = tr.querySelector('.subtotal-cell');
      if (cell) cell.textContent = '$ ' + sub.toLocaleString('es-CO');
      subComp += sub;
    });
    const mo  = parseFloat(modal.querySelector('input[name="mano_obra"]')?.value || 0);
    const iva = parseFloat(modal.querySelector('input[name="iva_pct"]')?.value || 0);
    const subtotal = subComp + mo;
    const total = subtotal + (subtotal * iva / 100);
    const out = modal.querySelector('.total-show');
    if (out) out.value = '$ ' + total.toLocaleString('es-CO', {maximumFractionDigits:0});
  };
  modal.querySelectorAll('.cantidad-input, .precio-input, input[name="mano_obra"], input[name="iva_pct"]')
       .forEach(inp => inp.addEventListener('input', recalcular));
  modal.addEventListener('shown.bs.modal', recalcular);
});
</script>
</body>
</html>