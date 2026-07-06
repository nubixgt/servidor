<?php
namespace App\Controllers\VISAR;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\HasPrivilege;
use App\Services\VISAR\VisarLicenciasFitoService;
use Exception;

#[HasPrivilege('modulo_visar')]
class LicenciasFitoController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new VisarLicenciasFitoService();
    }

    #[Route('/visar/licencias-fitosanitarias', 'GET')]
    public function index()
    {
        try {
            if (isset($_GET['get_filters'])) {
                $filters = $this->service->getFilters();
                $stats = $this->service->getStats();
                $this->json([
                    'success' => true,
                    'categorias' => $filters['categorias'],
                    'departamentos' => $filters['departamentos'],
                    'municipios' => $filters['municipios'],
                    'stats' => $stats
                ]);
                return;
            }

            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = 20;

            $filters = [
                'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
                'categoria' => isset($_GET['categoria']) ? trim($_GET['categoria']) : '',
                'departamento' => isset($_GET['departamento']) ? trim($_GET['departamento']) : '',
                'municipio' => isset($_GET['municipio']) ? trim($_GET['municipio']) : '',
                'fechaDesde' => isset($_GET['fechaDesde']) ? trim($_GET['fechaDesde']) : '',
                'fechaHasta' => isset($_GET['fechaHasta']) ? trim($_GET['fechaHasta']) : ''
            ];

            $data = $this->service->getLicencias($filters, $page, $perPage);
            
            $this->json([
                'success' => true,
                'records' => $data['records'],
                'total_records' => $data['total_records'],
                'current_page' => $data['current_page'],
                'total_pages' => $data['total_pages'],
                'per_page' => $data['per_page']
            ]);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/visar/licencias-fitosanitarias/stats', 'GET')]
    public function stats()
    {
        try {
            $data = $this->service->getStats();
            $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error al obtener estadisticas: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/visar/licencias-fitosanitarias/importar', 'POST')]
    public function importar()
    {
        try {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("No se ha enviado un archivo válido.");
            }

            $fileInfo = pathinfo($_FILES['file']['name']);
            if (strtolower($fileInfo['extension']) !== 'xlsx' && strtolower($fileInfo['extension']) !== 'xls') {
                throw new Exception("El archivo debe ser un Excel (.xlsx o .xls).");
            }

            $tmpPath = $_FILES['file']['tmp_name'];
            $result = $this->service->processExcel($tmpPath);

            (new \App\Services\Notifications\NotificationService())->createGlobalNotification(
                "Importación Exitosa", 
                "Se importaron {$result['nuevos']} registros de Licencias Fitosanitarias en VISAR.", 
                "success"
            );

            $this->json([
                'success' => true,
                'message' => "Proceso completado. Registros nuevos: {$result['nuevos']}, actualizados: {$result['actualizados']}"
            ]);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error en la importación: ' . $e->getMessage()
            ], 400);
        }
    }
}
