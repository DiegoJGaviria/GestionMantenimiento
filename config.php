<?php
session_start();

$host = "localhost";
$username = "diego@gmail.com";
$password = "123456";
$dbname = "tu_base_de_datos";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}
?>

