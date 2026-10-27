<?php
namespace App\Controllers;

use App\Attributes\HasPrivilege;
use App\Attributes\Route;
use App\Core\Controller;
use App\Services\ImportacionService;
use App\Utils\Response;

/**
 * Importación masiva desde la web: subir archivo → procesar → reporte de resultados.
 */
class ImportacionController extends Controller
{
    #[Route('/importaciones', 'POST')]
    #[HasPrivilege('importacion.ejecutar')]
    public function subir()
    {
        $service = new ImportacionService();
        $resultado = $service->subir($this->user(), $_FILES['archivo'] ?? null, (string) ($_POST['modo'] ?? 'INCREMENTAL'));

        if (!$service->esInline($resultado['bytes'])) {
            // archivo grande: queda PENDIENTE y lo procesa `php bin/importar.php --worker`
            $this->json($resultado, 201);
        }

        // se responde de inmediato y el procesamiento sigue en segundo plano; la pantalla consulta el avance
        Response::finishAndContinue($resultado, 201);
        try {
            $service->ejecutarLote($resultado['id']);
        } catch (\Throwable $e) {
            // el error queda en el lote
        }
        exit;
    }

    #[Route('/importaciones', 'GET')]
    #[HasPrivilege('importacion.ejecutar', 'auditoria.ver')]
    public function index()
    {
        $this->json((new ImportacionService())->listar());
    }

    #[Route('/importaciones/{lote}', 'GET')]
    #[HasPrivilege('importacion.ejecutar', 'auditoria.ver')]
    public function detalle($lote)
    {
        $this->json((new ImportacionService())->detalle((int) $lote));
    }

    #[Route('/importaciones/{lote}/errores.csv', 'GET')]
    #[HasPrivilege('importacion.ejecutar')]
    public function errores($lote)
    {
        $stmt = (new ImportacionService())->errores($this->user(), (int) $lote);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="lote_' . (int) $lote . '_errores.csv"');
        header('Access-Control-Expose-Headers: Content-Disposition');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['linea', 'nivel', 'regla', 'campo', 'valor', 'mensaje']);
        while ($fila = $stmt->fetch(\PDO::FETCH_NUM)) {
            fputcsv($out, $fila);
        }
        fclose($out);
        exit;
    }

    #[Route('/importaciones/{lote}/cancelar', 'POST')]
    #[HasPrivilege('importacion.ejecutar')]
    public function cancelar($lote)
    {
        $this->json((new ImportacionService())->cancelar((int) $lote));
    }
}
