<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['tecnico'])) {
    header('Location: login.php');
    exit();
}

function tienePermiso(string $modulo): bool
{
    global $conn;
    if (isset($_SESSION['rol_nombre']) && $_SESSION['rol_nombre'] === 'Administrador') {
        return true;
    }
    if (empty($_SESSION['idTecnico']) || !isset($conn)) {
        return false;
    }
    try {
        $stmt = $conn->prepare("SELECT 1 FROM Permiso_Modulo WHERE Tecnico_idTecnico = ? AND modulo = ?");
        if (!$stmt) return false;
        $stmt->bind_param("is", $_SESSION['idTecnico'], $modulo);
        $stmt->execute();
        $tiene = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $tiene;
    } catch (\mysqli_sql_exception $e) {
        return false;
    }
}
?>
