<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Models\ModuloModel;

class ModulosController extends Controller
{
    #[Route('/modulos/comisiones/export', 'GET')]
    public function exportComisiones()
    {
        try {
            $model = new ModuloModel();
            $comisiones = $model->getAll('comisiones');

            // Set CSV download headers
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="reporte_comisiones_sysdipu.csv"');
            
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write headers
            fputcsv($output, ['REPORTE DE COMISIONES LEGISLATIVAS - SYSDIPU']);
            fputcsv($output, ['Fecha de Generación', date('Y-m-d H:i:s')]);
            fputcsv($output, []);
            
            fputcsv($output, ['ID', 'Comisión', 'Presidente', 'Tipo', 'Estado', 'Dictámenes Activos', 'Integrantes / Notas']);
            
            foreach ($comisiones as $com) {
                fputcsv($output, [
                    $com['id'],
                    $com['nombre'],
                    $com['presidente'],
                    $com['tipo'],
                    $com['estado'],
                    $com['dictamenes'],
                    $com['notas']
                ]);
            }
            fclose($output);
            exit;
        } catch (\Exception $e) {
            error_log("Error en ModulosController::exportComisiones - " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/modulos/{modulo}', 'GET')]
    public function get($modulo)
    {
        $model = new ModuloModel();
        try {
            $data = $model->getAll($modulo);
            $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            error_log("Error en ModulosController::get ($modulo) - " . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/modulos/{modulo}', 'POST')]
    public function create($modulo)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $model = new ModuloModel();
        try {
            $insertId = $model->create($modulo, $input);
            if ($insertId) {
                $this->json([
                    'success' => true,
                    'message' => 'Registro creado exitosamente',
                    'data' => ['id' => $insertId]
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo guardar el registro'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en ModulosController::create ($modulo) - " . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/modulos/{modulo}/{id}', 'PUT')]
    public function update($modulo, $id)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $model = new ModuloModel();
        try {
            $success = $model->update($modulo, $id, $input);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Registro actualizado exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo actualizar el registro'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en ModulosController::update ($modulo, $id) - " . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/modulos/{modulo}/{id}', 'DELETE')]
    public function delete($modulo, $id)
    {
        $model = new ModuloModel();
        try {
            $success = $model->delete($modulo, $id);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Registro eliminado exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo eliminar el registro'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en ModulosController::delete ($modulo, $id) - " . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
