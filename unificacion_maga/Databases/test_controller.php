<?php
require_once 'c:/xampp/htdocs/unificacion_maga/Backend/autoload.php';
$controller = new \App\Controllers\VISAR\VisarDashboardController();
$controller->getStats();
