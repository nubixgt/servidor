<?php
// web/logout.php
session_start();
session_destroy();

// Redirigir al login con parámetro de logout exitoso
header("Location: login.php?logout=success");
exit;
?>