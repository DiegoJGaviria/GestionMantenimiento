<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand"><h3>Sistema de Arreglo de Computadores</h3></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="index.php">Inicio</a>
        </li>
        <?php if (isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador'): ?>
        <li class="nav-item">
          <a class="nav-link" href="tecnicos.php">Tecnicos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="dispositivos.php">Dispositivos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="marca.php">Marcas</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="cliente.php">Clientes</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="componentes.php">Componentes</a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link" href="arreglo.php">Arreglos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="diagnostico.php">Diagnostico</a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION['tecnico'])): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-1"></i>
            <?php echo htmlspecialchars($_SESSION['tecnico']); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
            <li><span class="dropdown-item-text">Rol: <?php echo htmlspecialchars($_SESSION['rol_nombre'] ?? 'Tecnico'); ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="mi_cuenta.php"><i class="bi bi-gear me-2"></i>Mi Cuenta</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesion</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>