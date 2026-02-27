<?php
// web/modules/admin/actualizar_denuncia.php
require_once '../../config/database.php';
require_once '../../includes/verificar_sesion.php';

// Verificar que sea administrador
verificarRol('admin');

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

// Obtener datos del formulario
$id_denuncia = $_POST['id_denuncia'] ?? 0;
$estado = $_POST['estado'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$dpi = trim($_POST['dpi'] ?? '');
$edad = intval($_POST['edad'] ?? 0);
$genero = $_POST['genero'] ?? '';
$celular = trim($_POST['celular'] ?? '');
$nombre_responsable = trim($_POST['nombre_responsable'] ?? '') ?: null;
$direccion = trim($_POST['direccion'] ?? '');
$departamento = $_POST['departamento'] ?? '';
$municipio = trim($_POST['municipio'] ?? '');
$color_casa = trim($_POST['color_casa'] ?? '') ?: null;
$color_puerta = trim($_POST['color_puerta'] ?? '') ?: null;
$latitud = floatval($_POST['latitud'] ?? 0) ?: null;
$longitud = floatval($_POST['longitud'] ?? 0) ?: null;
$especie = $_POST['especie'] ?? '';
$especie_otro = trim($_POST['especie_otro'] ?? '') ?: null;
$cantidad = intval($_POST['cantidad'] ?? 0);
$raza = trim($_POST['raza'] ?? '') ?: null;
$descripcion = trim($_POST['descripcion'] ?? '');
$infracciones = $_POST['infracciones'] ?? [];
$infraccion_otro = trim($_POST['infraccion_otro'] ?? '');

// Validaciones básicas
if ($id_denuncia <= 0) {
    $_SESSION['error'] = 'ID de denuncia inválido';
    header("Location: dashboard.php");
    exit;
}

if (empty($nombre) || empty($dpi) || $edad < 18 || empty($genero) || 
    empty($celular) || empty($direccion) || empty($departamento) || 
    empty($municipio) || empty($especie) || $cantidad < 1 || 
    empty($descripcion) || empty($infracciones)) {
    
    $_SESSION['error'] = 'Todos los campos obligatorios deben estar completos';
    header("Location: editar_denuncia.php?id=$id_denuncia");
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Actualizar denuncia principal
    $sqlUpdate = "UPDATE denuncias SET
                  estado_denuncia = :estado,
                  nombre_completo = :nombre,
                  dpi = :dpi,
                  edad = :edad,
                  genero = :genero,
                  celular = :celular,
                  nombre_responsable = :nombre_responsable,
                  direccion_infraccion = :direccion,
                  departamento = :departamento,
                  municipio = :municipio,
                  color_casa = :color_casa,
                  color_puerta = :color_puerta,
                  latitud = :latitud,
                  longitud = :longitud,
                  especie_animal = :especie,
                  especie_otro = :especie_otro,
                  cantidad = :cantidad,
                  raza = :raza,
                  descripcion_detallada = :descripcion
                  WHERE id_denuncia = :id";
    
    $stmtUpdate = $db->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':estado', $estado);
    $stmtUpdate->bindParam(':nombre', $nombre);
    $stmtUpdate->bindParam(':dpi', $dpi);
    $stmtUpdate->bindParam(':edad', $edad);
    $stmtUpdate->bindParam(':genero', $genero);
    $stmtUpdate->bindParam(':celular', $celular);
    $stmtUpdate->bindParam(':nombre_responsable', $nombre_responsable);
    $stmtUpdate->bindParam(':direccion', $direccion);
    $stmtUpdate->bindParam(':departamento', $departamento);
    $stmtUpdate->bindParam(':municipio', $municipio);
    $stmtUpdate->bindParam(':color_casa', $color_casa);
    $stmtUpdate->bindParam(':color_puerta', $color_puerta);
    $stmtUpdate->bindParam(':latitud', $latitud);
    $stmtUpdate->bindParam(':longitud', $longitud);
    $stmtUpdate->bindParam(':especie', $especie);
    $stmtUpdate->bindParam(':especie_otro', $especie_otro);
    $stmtUpdate->bindParam(':cantidad', $cantidad);
    $stmtUpdate->bindParam(':raza', $raza);
    $stmtUpdate->bindParam(':descripcion', $descripcion);
    $stmtUpdate->bindParam(':id', $id_denuncia);
    $stmtUpdate->execute();
    
    // Eliminar infracciones anteriores
    $sqlDeleteInfracciones = "DELETE FROM infracciones_denuncia WHERE id_denuncia = :id";
    $stmtDeleteInfracciones = $db->prepare($sqlDeleteInfracciones);
    $stmtDeleteInfracciones->bindParam(':id', $id_denuncia);
    $stmtDeleteInfracciones->execute();
    
    // Insertar nuevas infracciones
    $sqlInsertInfraccion = "INSERT INTO infracciones_denuncia 
                           (id_denuncia, tipo_infraccion, infraccion_otro) 
                           VALUES (:id, :tipo, :otro)";
    $stmtInsertInfraccion = $db->prepare($sqlInsertInfraccion);
    
    foreach ($infracciones as $tipo) {
        $otro = ($tipo === 'Otros' && !empty($infraccion_otro)) ? $infraccion_otro : null;
        $stmtInsertInfraccion->bindParam(':id', $id_denuncia);
        $stmtInsertInfraccion->bindParam(':tipo', $tipo);
        $stmtInsertInfraccion->bindParam(':otro', $otro);
        $stmtInsertInfraccion->execute();
    }
    
    // Confirmar transacción
    $db->commit();
    
    // Redirigir con mensaje de éxito
    $_SESSION['success'] = 'Denuncia actualizada exitosamente';
    header("Location: ver_denuncia.php?id=$id_denuncia");
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    $_SESSION['error'] = 'Error al actualizar la denuncia: ' . $e->getMessage();
    header("Location: editar_denuncia.php?id=$id_denuncia");
    exit;
}
?>