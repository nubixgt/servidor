<?php
namespace App\Controllers;

use App\Attributes\HasPrivilege;
use App\Attributes\Route;
use App\Core\Controller;
use App\DTOs\ExportacionDTO;
use App\Services\ReporteService;

class ReporteController extends Controller
{
    #[Route('/reportes', 'GET')]
    #[HasPrivilege('reportes.ver')]
    public function index()
    {
        $this->json((new ReporteService())->catalogo($this->user()));
    }

    #[Route('/reportes/exportar', 'POST')]
    #[HasPrivilege('reportes.ver')]
    public function exportar()
    {
        set_time_limit(0);
        $r = (new ReporteService())->exportar($this->user(), ExportacionDTO::fromRequest($this->body()));

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $r['nombre'] . '"');
        header('Access-Control-Expose-Headers: Content-Disposition');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [$r['marca']]);
        fputcsv($out, $r['cols']);
        foreach ($r['filas'] as $fila) {
            fputcsv($out, $fila);
        }
        fclose($out);
        exit;
    }
}
