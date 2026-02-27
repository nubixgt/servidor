<?php
// web/includes/detectar_rol_navbar.php
// Helper para detectar el rol del usuario y determinar navbar y URL de retorno

$rol = $_SESSION['usuario_rol'];
$navbarFile = __DIR__ . '/navbar_admin.php';
$urlRetorno = 'index.php';

// Mapeo de roles a sus respectivos navbars y dashboards
$rolesConfig = [
    'tecnico_1' => [
        'navbar' => __DIR__ . '/navbar_tecnico1.php',
        'dashboard' => '../../tecnico_1/dashboard.php'
    ],
    'tecnico_2' => [
        'navbar' => __DIR__ . '/navbar_tecnico2.php',
        'dashboard' => '../../tecnico_2/dashboard.php'
    ],
    'tecnico_3' => [
        'navbar' => __DIR__ . '/navbar_tecnico3.php',
        'dashboard' => '../../tecnico_3/dashboard.php'
    ],
    'tecnico_4' => [
        'navbar' => __DIR__ . '/navbar_tecnico4.php',
        'dashboard' => '../../tecnico_4/dashboard.php'
    ],
    'tecnico_5' => [
        'navbar' => __DIR__ . '/navbar_tecnico5.php',
        'dashboard' => '../../tecnico_5/dashboard.php'
    ]
];

// Aplicar configuración si el rol existe en el mapeo
if (isset($rolesConfig[$rol])) {
    $navbarFile = $rolesConfig[$rol]['navbar'];
    $urlRetorno = $rolesConfig[$rol]['dashboard'];
}
?>