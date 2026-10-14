<?php
// Antes todo el proyecto estaba en este archivo con ?page=...
// Se conserva para que los enlaces viejos sigan funcionando.
$paginas = [
    "inicio" => "index.php",
    "catalogo" => "catalogo.php",
    "registro" => "registro.php",
    "login" => "login.php",
    "publicar" => "publicar.php",
    "mis_solicitudes" => "mis_solicitudes.php",
];

$pagina = $_GET["page"] ?? "inicio";
header("Location: " . ($paginas[$pagina] ?? "index.php"));
exit;
