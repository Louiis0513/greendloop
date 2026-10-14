<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "127.0.0.1";
$usuario = "root";
$password = "";
$baseDatos = "green_loop";
$puerto = 3306;

try {
    $mysqli = new mysqli($host, $usuario, $password, $baseDatos, $puerto);
    $mysqli->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit("Error de conexión. Importa bd/green_loop.sql en phpMyAdmin.");
}
