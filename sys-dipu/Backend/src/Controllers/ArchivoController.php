<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\ArchivoModel;
use Exception;

class ArchivoController extends Controller
{
    #[Route('/archivo', 'GET')]
    public function index()
    {
        $model = new ArchivoModel();
        try {
            $filters = [
                'search' => $_GET['search'] ?? '',
                'tipo' => $_GET['tipo'] ?? '',
                'year' => $_GET['year'] ?? ''
            ];
            $data = $model->getAll($filters);
            $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            error_log("Error en ArchivoController::index - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error al obtener los documentos del archivo'], 500);
        }
    }

    #[Route('/archivo', 'POST')]
    public function store()
    {
        $expedienteId = $_POST['expediente_id'] ?? '';
        $titulo = $_POST['titulo'] ?? '';
        $tipo = $_POST['tipo'] ?? '';
        $fecha = $_POST['fecha'] ?? '';
        $modulo = $_POST['modulo'] ?? '';
        $estado = $_POST['estado'] ?? '';

        if (empty($expedienteId) || empty($titulo) || empty($tipo) || empty($fecha) || empty($modulo) || empty($estado)) {
            $this->json(['success' => false, 'error' => 'Faltan campos obligatorios (expediente_id, titulo, tipo, fecha, modulo, estado)'], 400);
            return;
        }

        $model = new ArchivoModel();

        try {
            // Check for uniqueness of expediente_id
            $existing = $model->getByExpedienteId($expedienteId);
            if ($existing) {
                $this->json(['success' => false, 'error' => 'El código de expediente ya está registrado en el sistema'], 409);
                return;
            }

            $fileUrl = null;
            if (!empty($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['archivo'];
                
                // Max size: 50MB
                if ($file['size'] > 50 * 1024 * 1024) {
                    $this->json(['success' => false, 'error' => 'El archivo supera el límite de 50MB permitido'], 400);
                    return;
                }

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'txt', 'png', 'jpg', 'jpeg'];
                if (!in_array($ext, $allowedExtensions)) {
                    $this->json(['success' => false, 'error' => 'Extensión de archivo no permitida.'], 400);
                    return;
                }

                $targetDir = __DIR__ . '/../../uploads/documentos/';
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $cleanBaseName = preg_replace('/[^a-zA-Z0-9.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                $fileName = time() . "_" . $cleanBaseName . "." . $ext;
                $targetFile = $targetDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $fileUrl = "/uploads/documentos/" . $fileName;
                } else {
                    $this->json(['success' => false, 'error' => 'Error al mover el archivo al almacenamiento del servidor'], 500);
                    return;
                }
            }

            $data = [
                'expediente_id' => $expedienteId,
                'titulo' => $titulo,
                'tipo' => $tipo,
                'fecha' => $fecha,
                'modulo' => $modulo,
                'estado' => $estado,
                'file_url' => $fileUrl
            ];

            $insertId = $model->create($data);
            if ($insertId) {
                $this->json([
                    'success' => true,
                    'message' => 'Documento archivado exitosamente',
                    'data' => array_merge(['id' => $insertId], $data)
                ]);
            } else {
                if ($fileUrl) {
                    @unlink(__DIR__ . '/../..' . $fileUrl);
                }
                $this->json(['success' => false, 'error' => 'No se pudo guardar el documento en la base de datos'], 500);
            }

        } catch (Exception $e) {
            error_log("Error en ArchivoController::store - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en el servidor al guardar el expediente'], 500);
        }
    }

    #[Route('/archivo/{id}', 'POST')]
    public function update($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'ID de registro inválido'], 400);
            return;
        }

        $expedienteId = $_POST['expediente_id'] ?? '';
        $titulo = $_POST['titulo'] ?? '';
        $tipo = $_POST['tipo'] ?? '';
        $fecha = $_POST['fecha'] ?? '';
        $modulo = $_POST['modulo'] ?? '';
        $estado = $_POST['estado'] ?? '';

        if (empty($expedienteId) || empty($titulo) || empty($tipo) || empty($fecha) || empty($modulo) || empty($estado)) {
            $this->json(['success' => false, 'error' => 'Faltan campos obligatorios para actualizar'], 400);
            return;
        }

        $model = new ArchivoModel();

        try {
            $existingRecord = $model->getById((int)$id);
            if (!$existingRecord) {
                $this->json(['success' => false, 'error' => 'El expediente no existe'], 404);
                return;
            }

            // Verify unique expediente_id (excluding current record)
            $existingExp = $model->getByExpedienteId($expedienteId);
            if ($existingExp && (int)$existingExp['id'] !== (int)$id) {
                $this->json(['success' => false, 'error' => 'El código de expediente ya está en uso por otro registro'], 409);
                return;
            }

            $fileUrl = $existingRecord['file_url'];

            // Handle file upload replacement
            if (!empty($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['archivo'];

                if ($file['size'] > 50 * 1024 * 1024) {
                    $this->json(['success' => false, 'error' => 'El archivo supera el límite de 50MB permitido'], 400);
                    return;
                }

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'txt', 'png', 'jpg', 'jpeg'];
                if (!in_array($ext, $allowedExtensions)) {
                    $this->json(['success' => false, 'error' => 'Extensión de archivo no permitida.'], 400);
                    return;
                }

                $targetDir = __DIR__ . '/../../uploads/documentos/';
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $cleanBaseName = preg_replace('/[^a-zA-Z0-9.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                $fileName = time() . "_" . $cleanBaseName . "." . $ext;
                $targetFile = $targetDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    // Delete the old file if exists
                    if (!empty($existingRecord['file_url'])) {
                        $oldFilePath = __DIR__ . '/../..' . $existingRecord['file_url'];
                        if (file_exists($oldFilePath)) {
                            @unlink($oldFilePath);
                        }
                    }
                    $fileUrl = "/uploads/documentos/" . $fileName;
                } else {
                    $this->json(['success' => false, 'error' => 'Error al mover el archivo de reemplazo al servidor'], 500);
                    return;
                }
            }

            $data = [
                'expediente_id' => $expedienteId,
                'titulo' => $titulo,
                'tipo' => $tipo,
                'fecha' => $fecha,
                'modulo' => $modulo,
                'estado' => $estado,
                'file_url' => $fileUrl
            ];

            $success = $model->update((int)$id, $data);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Expediente actualizado exitosamente',
                    'data' => $data
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se realizó ningún cambio o falló la actualización'], 500);
            }

        } catch (Exception $e) {
            error_log("Error en ArchivoController::update - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en el servidor al actualizar el expediente'], 500);
        }
    }

    #[Route('/archivo/{id}', 'DELETE')]
    public function destroy($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'ID de registro inválido'], 400);
            return;
        }

        $model = new ArchivoModel();
        try {
            $record = $model->getById((int)$id);
            if (!$record) {
                $this->json(['success' => false, 'error' => 'El expediente no existe'], 404);
                return;
            }

            // Unlink physical file
            if (!empty($record['file_url'])) {
                $filePath = __DIR__ . '/../..' . $record['file_url'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $success = $model->delete((int)$id);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Expediente eliminado del archivo permanentemente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo eliminar el expediente de la base de datos'], 500);
            }
        } catch (Exception $e) {
            error_log("Error en ArchivoController::destroy - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en el servidor al eliminar el expediente'], 500);
        }
    }

    #[Route('/archivo/export/excel', 'GET')]
    public function exportExcel()
    {
        try {
            $model = new ArchivoModel();
            
            $filters = [
                'search' => $_GET['search'] ?? '',
                'tipo' => $_GET['tipo'] ?? '',
                'year' => $_GET['year'] ?? ''
            ];
            
            $data = $model->getAll($filters);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="reporte_archivo_central.csv"');
            
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($output, ['REPORTE GENERAL DEL ARCHIVO CENTRAL - SYSDIPU']);
            fputcsv($output, ['Fecha de Generación', date('Y-m-d H:i:s')]);
            fputcsv($output, ['Filtro de búsqueda', $filters['search'] ?: 'Ninguno']);
            fputcsv($output, ['Filtro de tipo', $filters['tipo'] ?: 'Todos']);
            fputcsv($output, ['Filtro de año', $filters['year'] ?: 'Todos']);
            fputcsv($output, []);
            
            fputcsv($output, ['ID Interno', 'Código Expediente', 'Título / Asunto', 'Tipo', 'Fecha Documento', 'Módulo Origen', 'Estado', 'Tiene Adjunto']);
            
            foreach ($data as $item) {
                fputcsv($output, [
                    $item['id'],
                    $item['expediente_id'],
                    $item['titulo'],
                    $item['tipo'],
                    $item['fecha_formateada'] ?? $item['fecha'],
                    $item['modulo'],
                    $item['estado'],
                    !empty($item['file_url']) ? 'SÍ' : 'NO'
                ]);
            }
            fclose($output);
            exit;
        } catch (Exception $e) {
            error_log("Error en ArchivoController::exportExcel - " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/archivo/export/pdf', 'GET')]
    public function exportPdf()
    {
        try {
            header('Content-Type: text/html; charset=utf-8');

            $model = new ArchivoModel();
            
            $filters = [
                'search' => $_GET['search'] ?? '',
                'tipo' => $_GET['tipo'] ?? '',
                'year' => $_GET['year'] ?? ''
            ];
            
            $data = $model->getAll($filters);
            $fechaGen = date('d/m/Y H:i:s');

            echo "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Reporte de Archivo Central - SYSDIPU</title>
                <style>
                    body {
                        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                        color: #333;
                        margin: 40px;
                        line-height: 1.5;
                    }
                    header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-bottom: 2px solid #216170;
                        padding-bottom: 20px;
                        margin-bottom: 30px;
                    }
                    h1 {
                        color: #184e5b;
                        margin: 0;
                        font-size: 24px;
                        font-weight: 800;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                    }
                    .meta {
                        font-size: 12px;
                        color: #666;
                        text-align: right;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
                    th, td {
                        padding: 12px 15px;
                        text-align: left;
                        border-bottom: 1px solid #ddd;
                        font-size: 13px;
                    }
                    th {
                        background-color: #f4f8f9;
                        color: #184e5b;
                        font-weight: bold;
                        text-transform: uppercase;
                        font-size: 11px;
                        letter-spacing: 0.5px;
                    }
                    tr:hover {
                        background-color: #f9f9f9;
                    }
                    .badge {
                        padding: 4px 8px;
                        border-radius: 12px;
                        font-size: 10px;
                        font-weight: bold;
                        text-transform: uppercase;
                        display: inline-block;
                    }
                    .badge-aprobado { background-color: #dcfce7; color: #166534; }
                    .badge-historico { background-color: #dbeafe; color: #1e40af; }
                    .badge-abrogado { background-color: #f3f4f6; color: #374151; }
                    .badge-rechazado { background-color: #fee2e2; color: #991b1b; }
                    
                    .badge-ley { background-color: #fef3c7; color: #92400e; }
                    .badge-resolucion { background-color: #e0f2fe; color: #0369a1; }
                    .badge-decreto { background-color: #f3e8ff; color: #6b21a8; }
                    .badge-acta { background-color: #f1f5f9; color: #475569; }
                    .badge-iniciativa { background-color: #ffedd5; color: #c2410c; }
                </style>
            </head>
            <body>
                <header>
                    <div>
                        <h1>Índice de Archivo Central</h1>
                        <p style='margin: 5px 0 0 0; font-size: 13px; color: #555;'>Repositorio Documental Ecosistema SYSDIPU</p>
                    </div>
                    <div class='meta'>
                        <p><strong>Fecha Generación:</strong> {$fechaGen}</p>
                        <p><strong>Total Expedientes:</strong> " . count($data) . "</p>
                    </div>
                </header>
                
                <div style='margin-bottom: 20px; font-size: 12px; color: #666; background: #f9f9f9; padding: 10px 15px; border-radius: 6px; border: 1px solid #eee;'>
                    <strong>Filtros activos:</strong> 
                    Búsqueda: \"" . htmlspecialchars($filters['search']) . "\" | 
                    Tipo: \"" . htmlspecialchars($filters['tipo'] ?: 'Todos') . "\" | 
                    Año: \"" . htmlspecialchars($filters['year'] ?: 'Todos') . "\"
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style='width: 15%;'>Expediente</th>
                            <th style='width: 35%;'>Título / Asunto</th>
                            <th style='width: 15%;'>Tipo</th>
                            <th style='width: 15%;'>Fecha Archivo</th>
                            <th style='width: 10%;'>Estado</th>
                            <th style='width: 10%;'>Adjunto</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($data as $item) {
                $statusClass = strtolower(str_replace('ó', 'o', $item['estado']));
                $tipoClass = strtolower(str_replace('ó', 'o', $item['tipo']));
                
                $expId = htmlspecialchars($item['expediente_id']);
                $titulo = htmlspecialchars($item['titulo']);
                $mod = htmlspecialchars($item['modulo']);
                $tipo = htmlspecialchars($item['tipo']);
                $fecha = date('d/m/Y', strtotime($item['fecha']));
                $estado = htmlspecialchars($item['estado']);
                $hasFile = !empty($item['file_url']) ? 'SÍ' : 'NO';
                
                echo "
                        <tr>
                            <td><strong style='font-family: monospace; font-size: 12px; color: #184e5b;'>{$expId}</strong></td>
                            <td>
                                <strong>{$titulo}</strong><br>
                                <span style='font-size: 11px; color: #777;'>Módulo: {$mod}</span>
                            </td>
                            <td><span class='badge badge-{$tipoClass}'>{$tipo}</span></td>
                            <td>{$fecha}</td>
                            <td><span class='badge badge-{$statusClass}'>{$estado}</span></td>
                            <td><strong>{$hasFile}</strong></td>
                        </tr>";
            }
            
            echo "
                    </tbody>
                </table>
                <script>
                    window.onload = function() {
                        window.print();
                    }
                </script>
            </body>
            </html>";
            exit;
        } catch (Exception $e) {
            error_log("Error en ArchivoController::exportPdf - " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
