<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['action']) ? $_GET['action'] : '';

if ($method === 'GET' && $path === 'municipios') {
    // Return all municipalities
    $stmt = $pdo->query("SELECT * FROM municipios ORDER BY departamento, municipio");
    $data = $stmt->fetchAll();
    echo json_encode($data);
    exit;
}

if ($method === 'GET' && $path === 'departamentos') {
    // Return unique departments with their global data
    // Since diputado and gpc are at department level, we just group by department
    $stmt = $pdo->query("SELECT departamento, MAX(diputado_asignado) as diputado_asignado, MAX(gpc) as gpc, MAX(notas) as notas, COUNT(*) as total_municipios FROM municipios GROUP BY departamento ORDER BY departamento");
    $data = $stmt->fetchAll();
    echo json_encode($data);
    exit;
}

if ($method === 'PUT' && $path === 'departamento') {
    // Update data for an entire department
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['departamento'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Departamento is required']);
        exit;
    }
    
    $dept = $input['departamento'];
    $diputado = isset($input['diputado_asignado']) ? $input['diputado_asignado'] : null;
    $gpc = isset($input['gpc']) ? $input['gpc'] : null;
    $notas = isset($input['notas']) ? $input['notas'] : null;
    
    $stmt = $pdo->prepare("UPDATE municipios SET diputado_asignado = ?, gpc = ?, notas = ? WHERE departamento = ?");
    $stmt->execute([$diputado, $gpc, $notas, $dept]);
    
    echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
