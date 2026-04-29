<?php
/**
 * Genera el PDF de una factura usando Dompdf.
 * Uso: factura_pdf.php?id=NN
 */

include('auth.php');
include('conexion.php');
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'Administrador') {
    header('Location: index.php');
    exit();
}

$idFactura = (int)($_GET['id'] ?? 0);
if ($idFactura <= 0) { die('Factura invalida.'); }

$stmt = $conn->prepare("
  SELECT f.*, a.Nombre_Arreglo, a.Descripcion_Cliente, a.Fecha_Recibido, a.Fecha_Entrega,
         td.Nombre_Tipo, m.Nombre_Marca, t.Nombre_Tecnico,
         c.Primer_Nombre, c.Primer_Apellido, c.Telefono, c.Direccion
  FROM Factura f
  JOIN Arreglo a            ON f.Arreglo_idArreglo = a.idArreglo
  JOIN Tipo_Dispositivo td  ON a.Tipo_Dispositivo_idTipo = td.idTipoDispositivo
  JOIN Marca m              ON a.Marca_idMarca = m.idMarca
  JOIN Tecnico t            ON f.Tecnico_Emisor = t.idTecnico
  JOIN Detalle_Arreglo da   ON da.Arreglo_idArreglo = a.idArreglo
  JOIN Cliente c            ON c.idCliente = da.Cliente_idCliente
  WHERE f.idFactura = ?");
$stmt->bind_param("i", $idFactura);
$stmt->execute();
$fac = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fac) { die('Factura no encontrada.'); }

$stmt = $conn->prepare("SELECT * FROM Detalle_Factura WHERE Factura_idFactura=? ORDER BY idDetalleFactura");
$stmt->bind_param("i", $idFactura);
$stmt->execute();
$detalle = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$money = function($v) { return '$ ' . number_format((float)$v, 0, ',', '.'); };

ob_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { font-family: DejaVu Sans, sans-serif; }
  body { font-size: 11px; color: #1f2937; margin: 0; }
  .header { background: #1e3a8a; color: #fff; padding: 18px 22px; }
  .header h1 { margin: 0; font-size: 20px; letter-spacing: 1px; }
  .header .sub { font-size: 10px; opacity: .85; margin-top: 4px; }
  .meta { padding: 14px 22px; border-bottom: 1px solid #e5e7eb; }
  .meta table { width: 100%; }
  .meta td { vertical-align: top; padding: 0; }
  .meta .label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
  .meta .value { font-size: 12px; color: #111827; font-weight: bold; }
  .section { padding: 12px 22px; }
  .section h3 { font-size: 11px; color: #1e3a8a; border-bottom: 2px solid #1e3a8a; padding-bottom: 4px; margin: 0 0 8px 0; text-transform: uppercase; }
  .info-grid td { padding: 3px 6px 3px 0; }
  .info-grid .l { color: #6b7280; width: 28%; }
  table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
  table.items th { background: #1e3a8a; color: #fff; padding: 8px 6px; font-size: 10px; text-align: left; }
  table.items td { padding: 7px 6px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
  table.items td.right, table.items th.right { text-align: right; }
  table.items td.center, table.items th.center { text-align: center; }
  .totales { width: 45%; margin-left: 55%; margin-top: 10px; }
  .totales td { padding: 5px 8px; font-size: 11px; }
  .totales td.label { color: #6b7280; text-align: right; }
  .totales td.value { text-align: right; font-weight: bold; }
  .totales tr.grand td { background: #1e3a8a; color: #fff; font-size: 14px; padding: 10px; }
  .footer { padding: 18px 22px; font-size: 9px; color: #6b7280; text-align: center; border-top: 1px solid #e5e7eb; margin-top: 20px; }
  .observ { background: #f9fafb; padding: 10px; border-left: 3px solid #1e3a8a; font-size: 10px; }
</style>
</head>
<body>

<div class="header">
  <table style="width:100%">
    <tr>
      <td style="width:65%">
        <h1>SISTEMA DE ARREGLO DE COMPUTADORES</h1>
        <div class="sub">Servicio tecnico especializado | NIT 900.000.000-0</div>
      </td>
      <td style="width:35%; text-align:right">
        <div style="font-size:10px; opacity:.8">FACTURA</div>
        <div style="font-size:18px; font-weight:bold"><?php echo htmlspecialchars($fac['Numero_Factura']); ?></div>
        <div style="font-size:9px; opacity:.85; margin-top:4px"><?php echo date('d/m/Y H:i', strtotime($fac['Fecha_Emision'])); ?></div>
      </td>
    </tr>
  </table>
</div>

<div class="meta">
  <table>
    <tr>
      <td style="width:50%">
        <div class="label">Facturado a</div>
        <div class="value"><?php echo htmlspecialchars($fac['Primer_Nombre'].' '.$fac['Primer_Apellido']); ?></div>
        <div style="font-size:10px; color:#374151">
          Tel: <?php echo htmlspecialchars($fac['Telefono']); ?><br>
          Dir: <?php echo htmlspecialchars($fac['Direccion']); ?>
        </div>
      </td>
      <td style="width:50%">
        <div class="label">Tecnico responsable</div>
        <div class="value"><?php echo htmlspecialchars($fac['Nombre_Tecnico']); ?></div>
        <div style="font-size:10px; color:#374151">
          Recibido: <?php echo $fac['Fecha_Recibido']; ?><br>
          Entrega: <?php echo $fac['Fecha_Entrega']; ?>
        </div>
      </td>
    </tr>
  </table>
</div>

<div class="section">
  <h3>Detalle del arreglo</h3>
  <table class="info-grid">
    <tr>
      <td class="l">Dispositivo:</td>
      <td><strong><?php echo htmlspecialchars($fac['Nombre_Tipo']); ?> - <?php echo htmlspecialchars($fac['Nombre_Marca']); ?></strong></td>
    </tr>
    <tr>
      <td class="l">Servicio:</td>
      <td><?php echo htmlspecialchars($fac['Nombre_Arreglo']); ?></td>
    </tr>
    <tr>
      <td class="l">Descripcion del cliente:</td>
      <td><?php echo htmlspecialchars($fac['Descripcion_Cliente']); ?></td>
    </tr>
  </table>
</div>

<div class="section">
  <h3>Componentes y servicios</h3>
  <table class="items">
    <thead>
      <tr>
        <th style="width:12%">Cod.</th>
        <th>Descripcion</th>
        <th class="center" style="width:10%">Cant.</th>
        <th class="right" style="width:18%">Precio Unit.</th>
        <th class="right" style="width:18%">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($detalle as $d): ?>
        <tr>
          <td><?php echo htmlspecialchars($d['Codigo_Producto'] ?? '-'); ?></td>
          <td><?php echo htmlspecialchars($d['Nombre_Componente']); ?></td>
          <td class="center"><?php echo (int)$d['Cantidad']; ?></td>
          <td class="right"><?php echo $money($d['Precio_Unitario']); ?></td>
          <td class="right"><?php echo $money($d['Subtotal']); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ((float)$fac['Mano_Obra'] > 0): ?>
        <tr>
          <td>-</td>
          <td><strong>Mano de obra</strong> - servicio tecnico</td>
          <td class="center">1</td>
          <td class="right"><?php echo $money($fac['Mano_Obra']); ?></td>
          <td class="right"><?php echo $money($fac['Mano_Obra']); ?></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <table class="totales">
    <tr><td class="label">Subtotal componentes:</td><td class="value"><?php echo $money($fac['Subtotal_Componentes']); ?></td></tr>
    <tr><td class="label">Mano de obra:</td><td class="value"><?php echo $money($fac['Mano_Obra']); ?></td></tr>
    <tr><td class="label">Subtotal:</td><td class="value"><?php echo $money($fac['Subtotal']); ?></td></tr>
    <tr><td class="label">IVA (<?php echo (float)$fac['Iva_Porcentaje']; ?>%):</td><td class="value"><?php echo $money($fac['Iva_Valor']); ?></td></tr>
    <tr class="grand"><td class="label" style="color:#fff">TOTAL A PAGAR:</td><td class="value"><?php echo $money($fac['Total']); ?></td></tr>
  </table>
</div>

<?php if (!empty($fac['Observaciones'])): ?>
<div class="section">
  <h3>Observaciones</h3>
  <div class="observ"><?php echo nl2br(htmlspecialchars($fac['Observaciones'])); ?></div>
</div>
<?php endif; ?>

<div class="footer">
  Documento generado electronicamente el <?php echo date('d/m/Y H:i'); ?>.<br>
  Gracias por confiar en nuestro servicio tecnico.
</div>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$dompdf->stream('Factura_' . $fac['Numero_Factura'] . '.pdf', ["Attachment" => false]);
exit();