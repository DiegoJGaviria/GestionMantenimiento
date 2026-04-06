<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!-- navbar.php -->
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
        <li class="nav-item">
          <a class="nav-link" href="usuarios.php">Usuarios</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="marca.php">Marcas</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="cliente.php">Clientes</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="arreglo.php">Arreglos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="diagnostico.php">Diagnostico</a>
        </li>
        <?php if (isset($_SESSION['usuario'])): ?>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Cerrar sesión</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>