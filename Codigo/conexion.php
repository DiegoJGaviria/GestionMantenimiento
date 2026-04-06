<?php
$servername = "localhost";
$username = "root";
$password = "DjGr1999*";
$dbname = "sistema_arreglo_computadores";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
