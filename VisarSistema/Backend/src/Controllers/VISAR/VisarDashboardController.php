<?php
namespace App\Controllers\VISAR;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\HasPrivilege;
use App\Utils\Database;

#[HasPrivilege('modulo_visar')]
class VisarDashboardController extends Controller
{
    #[Route('/visar/dashboard/stats', 'GET')]
    public function getStats()
    {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Function to safely check and count records if table exists
            $getCount = function($table) use ($db) {
                try {
                    $stmt = $db->query("SELECT COUNT(*) as total FROM {$table}");
                    return $stmt->fetchColumn() ?: 0;
                } catch (\Exception $e) {
                    return 0; // If table doesn't exist or error, return 0
                }
            };
            
            $exportaciones = ['total' => 0, 'valor' => 0, 'toneladas' => 0];
            try {
                $stmt = $db->query("SELECT COUNT(*) as total, SUM(valor_fob) as valor, SUM(peso_neto) as peso FROM visar_exportaciones");
                if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $exportaciones = [
                        'total' => (int)($row['total'] ?? 0),
                        'valor' => (float)($row['valor'] ?? 0),
                        'toneladas' => (float)(($row['peso'] ?? 0) / 1000)
                    ];
                }
            } catch (\Exception $e) {}

            $importaciones = ['total' => 0, 'valor' => 0, 'toneladas' => 0];
            try {
                $stmt = $db->query("SELECT COUNT(*) as total, SUM(valor_dolares) as valor, SUM(peso_neto) as peso FROM visar_importaciones");
                if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $importaciones = [
                        'total' => (int)($row['total'] ?? 0),
                        'valor' => (float)($row['valor'] ?? 0),
                        'toneladas' => (float)(($row['peso'] ?? 0) / 1000)
                    ];
                }
            } catch (\Exception $e) {}

            $transporteTotal = $getCount('visar_licencias_transporte');
            $fitoTotal = $getCount('visar_licencias_fitosanitarias');

            $libreVenta = ['total' => 0, 'toneladas' => 0];
            try {
                $stmt = $db->query("SELECT COUNT(*) as total, SUM(peso_neto) as peso FROM visar_libre_venta");
                if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $libreVenta = [
                        'total' => (int)($row['total'] ?? 0),
                        'toneladas' => (float)(($row['peso'] ?? 0) / 1000)
                    ];
                }
            } catch (\Exception $e) {}

            $this->json([
                'success' => true,
                'data' => [
                    'exportaciones' => $exportaciones,
                    'importaciones' => $importaciones,
                    'licencias-transporte' => ['total' => $transporteTotal],
                    'licencias-fito' => ['total' => $fitoTotal],
                    'libre-venta' => $libreVenta
                ]
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
