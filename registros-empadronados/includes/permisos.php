<?php
/**
 * includes/permisos.php
 * Sistema de permisos y control de acceso por roles
 */

// Definición de roles del sistema
define('ROL_ADMINISTRADOR', 'Administrador');
define('ROL_PRESIDENTE', 'Presidente');
define('ROL_DIPUTADO', 'Diputado');
define('ROL_ALCALDE', 'Alcalde');

/**
 * Verifica si el usuario tiene acceso a la página
 */
function verificarAcceso($rolesPermitidos = []) {
    if (!estaAutenticado()) {
        redirigir('../vistas/login.php');
    }
    
    if (!usuarioActivo()) {
        cerrarSesion();
        redirigir('../vistas/login.php?error=cuenta_inactiva');
    }
    
    if (!empty($rolesPermitidos)) {
        $rolActual = obtenerRolUsuario();
        if (!in_array($rolActual, $rolesPermitidos)) {
            redirigir('../vistas/dashboard.php?error=sin_permisos');
        }
    }
}

/**
 * Verifica si el usuario es administrador
 */
function esAdministrador() {
    return obtenerRolUsuario() === ROL_ADMINISTRADOR;
}

/**
 * Verifica si el usuario es presidente
 */
function esPresidente() {
    return obtenerRolUsuario() === ROL_PRESIDENTE;
}

/**
 * Verifica si el usuario es diputado
 */
function esDiputado() {
    return obtenerRolUsuario() === ROL_DIPUTADO;
}

/**
 * Verifica si el usuario es alcalde
 */
function esAlcalde() {
    return obtenerRolUsuario() === ROL_ALCALDE;
}

/**
 * Obtiene los permisos según el rol
 */
function obtenerPermisos() {
    $rol = obtenerRolUsuario();
    
    $permisos = [
        'ver_dashboard' => true,
        'ver_departamentos' => false,
        'ver_municipios' => false,
        'ver_todos_datos' => false,
        'gestionar_usuarios' => false,
        'ver_logs' => false,
        'exportar_datos' => true,
        'ver_graficas' => true,
        'ver_resultados_electorales' => false,
        'exportar_resultados_electorales' => false
    ];
    
    switch ($rol) {
        case ROL_ADMINISTRADOR:
            $permisos['ver_departamentos'] = true;
            $permisos['ver_municipios'] = true;
            $permisos['ver_todos_datos'] = true;
            $permisos['gestionar_usuarios'] = true;
            $permisos['ver_logs'] = true;
            $permisos['ver_resultados_electorales'] = true;
            $permisos['exportar_resultados_electorales'] = true;
            break;
            
        case ROL_PRESIDENTE:
            $permisos['ver_departamentos'] = true;
            $permisos['ver_municipios'] = true;
            $permisos['ver_todos_datos'] = true;
            $permisos['ver_resultados_electorales'] = true;
            $permisos['exportar_resultados_electorales'] = true;
            break;
            
        case ROL_DIPUTADO:
            $permisos['ver_departamentos'] = true;
            $permisos['ver_municipios'] = true;
            $permisos['ver_resultados_electorales'] = true;
            $permisos['exportar_resultados_electorales'] = true;
            break;
            
        case ROL_ALCALDE:
            $permisos['ver_municipios'] = true;
            $permisos['ver_resultados_electorales'] = true;
            break;
    }
    
    return $permisos;
}

/**
 * Verifica si el usuario tiene un permiso específico
 */
function tienePermiso($permiso) {
    $permisos = obtenerPermisos();
    return isset($permisos[$permiso]) && $permisos[$permiso] === true;
}

/**
 * Construye la consulta SQL según el rol del usuario
 */
function construirConsultaSegunRol($pdo, $sqlBase) {
    $rol = obtenerRolUsuario();
    $parametros = [];
    
    switch ($rol) {
        case ROL_ADMINISTRADOR:
        case ROL_PRESIDENTE:
            // Pueden ver todo, no se agrega filtro
            break;
            
        case ROL_DIPUTADO:
            $departamento = obtenerDepartamentoUsuario();
            if ($departamento) {
                $sqlBase .= " AND departamento = :departamento";
                $parametros[':departamento'] = $departamento;
            }
            break;
            
        case ROL_ALCALDE:
            $municipio = obtenerMunicipioUsuario();
            $departamento = obtenerDepartamentoUsuario();
            if ($municipio && $departamento) {
                $sqlBase .= " AND municipio = :municipio AND departamento = :departamento";
                $parametros[':municipio'] = $municipio;
                $parametros[':departamento'] = $departamento;
            }
            break;
    }
    
    return ['sql' => $sqlBase, 'parametros' => $parametros];
}

/**
 * Obtiene el título según el rol del usuario
 */
function obtenerTituloSegunRol() {
    $rol = obtenerRolUsuario();
    $departamento = obtenerDepartamentoUsuario();
    $municipio = obtenerMunicipioUsuario();
    
    switch ($rol) {
        case ROL_ADMINISTRADOR:
            return 'Panel de Administración';
        case ROL_PRESIDENTE:
            return 'Vista Nacional - República de Guatemala';
        case ROL_DIPUTADO:
            return 'Departamento de ' . ($departamento ?? 'Sin asignar');
        case ROL_ALCALDE:
            return 'Municipio de ' . ($municipio ?? 'Sin asignar');
        default:
            return 'Dashboard';
    }
}

/**
 * Cierra la sesión del usuario
 */
function cerrarSesion() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}
?>