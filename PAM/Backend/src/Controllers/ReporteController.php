<?php
namespace App\Controllers;

use App\Attributes\HasPrivilege;
use App\Attributes\Route;
use App\Core\Controller;
use App\DTOs\ExportacionDTO;
use App\Services\ReporteExcelService;
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

    // ---------------------------------------------------------------- reportes en Excel
    #[Route('/reportes/columnas', 'GET')]
    #[HasPrivilege('reportes.ver')]
    public function columnas()
    {
        $this->json((new ReporteExcelService())->columnas($this->user()));
    }

    #[Route('/reportes/geografico/resumen', 'POST')]
    #[HasPrivilege('reportes.ver')]
    public function geograficoResumen()
    {
        $this->json((new ReporteExcelService())->resumen($this->user(), $this->body()));
    }

    #[Route('/reportes/geografico/previa', 'POST')]
    #[HasPrivilege('reportes.ver')]
    public function geograficoPrevia()
    {
        $this->json((new ReporteExcelService())->previa($this->user(), $this->body()));
    }

    #[Route('/reportes/geografico/exportar', 'POST')]
    #[HasPrivilege('reportes.exportar')]
    public function geograficoExportar()
    {
        $service = new ReporteExcelService();
        $u = $this->user();
        $this->entregar($service, $u, 'reporte_geografico', $service->exportarGeografico($u, $this->body()));
    }

    #[Route('/reportes/dpi/previa', 'POST')]
    #[HasPrivilege('reportes.ver')]
    public function dpiPrevia()
    {
        $this->json((new ReporteExcelService())->previaDpi($this->user(), $_FILES['archivo'] ?? null, $this->columnasForm()));
    }

    #[Route('/reportes/dpi/exportar', 'POST')]
    #[HasPrivilege('reportes.exportar')]
    public function dpiExportar()
    {
        $service = new ReporteExcelService();
        $u = $this->user();
        $this->entregar($service, $u, 'reporte_dpi', $service->exportarDpi($u, $_FILES['archivo'] ?? null, $this->columnasForm()));
    }

    #[Route('/reportes/bitacora', 'GET')]
    #[HasPrivilege('auditoria.ver')]
    public function bitacora()
    {
        $this->json((new ReporteExcelService())->bitacora($_GET));
    }

    /** Columnas enviadas como JSON dentro de un formulario multipart. */
    private function columnasForm(): array
    {
        $c = json_decode((string) ($_POST['columnas'] ?? '[]'), true);
        return is_array($c) ? $c : [];
    }

    /** Envía el .xlsx, borra el temporal y deja constancia en la bitácora. */
    private function entregar(ReporteExcelService $service, $u, string $entidad, array $r): void
    {
        $ruta = $r['ruta'];
        register_shutdown_function(static function () use ($ruta) {
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        });
        $service->limpiarTemporales();
        ignore_user_abort(true);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $r['nombre'] . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Access-Control-Expose-Headers: Content-Disposition');
        $ok = @readfile($ruta) !== false && !connection_aborted();
        @unlink($ruta);
        $service->registrar($u, $entidad, $ok ? 'DESCARGADO' : 'INTERRUMPIDO', $r['auditoria']);
        exit;
    }
}
