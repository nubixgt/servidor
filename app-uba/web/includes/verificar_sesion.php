<?php
// web/includes/verificar_sesion.php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si hay sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /app-uba/login.php");
    exit;
}

// Verificar que el rol coincida con el módulo actual
function verificarRol($rolRequerido) {
    // Permitir múltiples roles
    if (is_array($rolRequerido)) {
        if (!in_array($_SESSION['usuario_rol'], $rolRequerido)) {
            header("Location: /app-uba/login.php");
            exit;
        }
    } else {
        if ($_SESSION['usuario_rol'] !== $rolRequerido) {
            header("Location: /app-uba/login.php");
            exit;
        }
    }
}

// Función para obtener el nombre del usuario actual
function obtenerNombreUsuario() {
    return $_SESSION['usuario_nombre'] ?? 'Usuario';
}

// Función para obtener el rol actual
function obtenerRolUsuario() {
    return $_SESSION['usuario_rol'] ?? '';
}

// Función para obtener el ID del usuario
function obtenerIdUsuario() {
    return $_SESSION['usuario_id'] ?? 0;
}
?>