<?php
include('auth.php');
include("conexion.php");

$isAdmin = isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador';

// Contadores para el dashboard
$total_clientes = $conn->query("SELECT COUNT(*) as total FROM Cliente")->fetch_assoc()['total'];
$total_arreglos = $conn->query("SELECT COUNT(*) as total FROM Arreglo")->fetch_assoc()['total'];
$total_tecnicos = $conn->query("SELECT COUNT(*) as total FROM Tecnico")->fetch_assoc()['total'];
$total_marcas = $conn->query("SELECT COUNT(*) as total FROM Marca")->fetch_assoc()['total'];

// Ultimos 5 arreglos
$ultimos_arreglos = $conn->query("SELECT a.*, m.Nombre_Marca, u.Nombre_Tecnico 
    FROM Arreglo a 
    JOIN Marca m ON a.Marca_idMarca = m.idMarca 
    JOIN Tecnico u ON a.Tecnico_idTecnico = u.idTecnico 
    ORDER BY a.idArreglo DESC LIMIT 5");

// Valor total de arreglos
$valor_total = $conn->query("SELECT COALESCE(SUM(Valor_Pago), 0) as total FROM Arreglo")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TechRepair System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="estils.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-container">
        <!-- Encabezado -->
        <div class="page-header">
            <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
            <span style="color: var(--color-text-muted); font-size: 0.85rem; font-weight: 600;">
                <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y'); ?>
            </span>
        </div>

        <!-- Tarjetas de estadisticas -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-4 col-xl">
                <div class="stat-card bg-clientes">
                    <i class="bi bi-person-lines-fill stat-icon"></i>
                    <div class="stat-number"><?php echo $total_clientes; ?></div>
                    <div class="stat-label">Clientes</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-xl">
                <div class="stat-card bg-arreglos">
                    <i class="bi bi-tools stat-icon"></i>
                    <div class="stat-number"><?php echo $total_arreglos; ?></div>
                    <div class="stat-label">Arreglos</div>
                </div>
            </div>
            <?php if ($isAdmin): ?>
            <div class="col-12 col-sm-6 col-md-4 col-xl">
                <div class="stat-card bg-tecnicos">
                    <i class="bi bi-people-fill stat-icon"></i>
                    <div class="stat-number"><?php echo $total_tecnicos; ?></div>
                    <div class="stat-label">Tecnicos</div>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-12 col-sm-6 col-md-4 col-xl">
                <div class="stat-card bg-marcas">
                    <i class="bi bi-tags-fill stat-icon"></i>
                    <div class="stat-number"><?php echo $total_marcas; ?></div>
                    <div class="stat-label">Marcas</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Ultimos arreglos -->
            <div class="col-12 col-lg-8">
                <div class="card-custom">
                    <div class="card-header">
                        <h2><i class="bi bi-clock-history" style="color: var(--color-accent);"></i> Ultimos Arreglos</h2>
                        <a href="arreglo.php" class="btn btn-sm" style="background: var(--color-accent-light); color: #0891b2; border-radius: var(--radius); font-size: 0.8rem; font-weight: 600; padding: 0.35rem 0.75rem; border: 1.5px solid var(--color-accent); text-decoration: none;">
                            Ver todos <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table-custom table mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Tipo Arreglo</th>
                                        <th>Marca</th>
                                        <th>Valor</th>
                                        <th>Tecnico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($ultimos_arreglos && $ultimos_arreglos->num_rows > 0): ?>
                                        <?php while ($arreglo = $ultimos_arreglos->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $arreglo['idArreglo']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($arreglo['Nombre_Arreglo']); ?></td>
                                            <td>
                                                <?php
                                                    $tipo = strtolower($arreglo['Nombre_Arreglo']);
                                                    $badge_class = 'badge-general';
                                                    if (strpos($tipo, 'hardware') !== false) $badge_class = 'badge-hardware';
                                                    elseif (strpos($tipo, 'software') !== false) $badge_class = 'badge-software';
                                                ?>
                                                <span class="badge-rol badge-tipo <?php echo $badge_class; ?>"><?php echo htmlspecialchars($arreglo['Nombre_Arreglo']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($arreglo['Nombre_Marca']); ?></td>
                                            <td><span class="valor-moneda">$<?php echo number_format($arreglo['Valor_Pago'], 0, ',', '.'); ?></span></td>
                                            <td><?php echo htmlspecialchars($arreglo['Nombre_Tecnico']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">
                                                <div class="empty-state">
                                                    <i class="bi bi-inbox"></i>
                                                    <p>No hay arreglos registrados aun</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar derecho -->
            <div class="col-12 col-lg-4">
                <?php if ($isAdmin): ?>
                <!-- Resumen financiero -->
                <div class="card-custom mb-3">
                    <div class="card-header">
                        <h2><i class="bi bi-cash-stack" style="color: var(--color-success);"></i> Ingresos</h2>
                    </div>
                    <div class="card-body text-center" style="padding: 1.5rem;">
                        <div style="font-size: 1.75rem; font-weight: 900; color: var(--color-success); background: var(--color-success-light); display: inline-block; padding: 0.5rem 1.25rem; border-radius: var(--radius-lg);">
                            $ <?php echo number_format($valor_total, 0, ',', '.'); ?>
                        </div>
                        <div style="color: var(--color-text-muted); font-size: 0.82rem; margin-top: 0.5rem; font-weight: 500;">
                            Valor total en arreglos
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Accesos rapidos -->
                <div class="card-custom">
                    <div class="card-header">
                        <h2><i class="bi bi-lightning-fill" style="color: var(--color-warning);"></i> Acceso Rapido</h2>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <a href="arreglo.php" class="quick-access-card">
                                    <i class="bi bi-plus-circle-fill"></i>
                                    <span>Nuevo Arreglo</span>
                                </a>
                            </div>
                            <?php if ($isAdmin): ?>
                            <div class="col-12 col-sm-6">
                                <a href="cliente.php" class="quick-access-card">
                                    <i class="bi bi-person-plus-fill"></i>
                                    <span>Nuevo Cliente</span>
                                </a>
                            </div>
                            <div class="col-12 col-sm-6">
                                <a href="marca.php" class="quick-access-card">
                                    <i class="bi bi-tag-fill"></i>
                                    <span>Nueva Marca</span>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-custom">
        TechRepair System &copy; <?php echo date('Y'); ?> - Sistema de Gestion de Reparaciones
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
