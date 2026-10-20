<?php
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

function usuarioLogueado() {
    return isset($_SESSION["usuario"]);
}

function requiereLogin() {
    if (!usuarioLogueado()) {
        guardarMensaje("Inicia sesión para continuar.");
        header("Location: login.php");
        exit;
    }
}

// Para login y registro: quien ya inició sesión no tiene nada que hacer ahí.
function soloVisitantes() {
    if (usuarioLogueado()) {
        header("Location: catalogo.php");
        exit;
    }
}

// Etiqueta visible del tipo: en BD se guarda "intercambio", en pantalla "Trueque".
function tipoEtiqueta($tipo) {
    $tipo = strtolower(trim((string) $tipo));
    return ($tipo === "intercambio" || $tipo === "trueque") ? "Trueque" : "Regalo";
}

function esTrueque($tipo) {
    $tipo = strtolower(trim((string) $tipo));
    return $tipo === "intercambio" || $tipo === "trueque";
}

function claseActiva($archivo) {
    return basename($_SERVER["PHP_SELF"]) === $archivo ? "activo" : "";
}

// Mensaje "flash": se guarda antes de redirigir y se muestra una sola vez.
function guardarMensaje($texto) {
    $_SESSION["mensaje"] = $texto;
}

function obtenerMensaje() {
    $mensaje = $_SESSION["mensaje"] ?? "";
    unset($_SESSION["mensaje"]);
    return $mensaje;
}
