<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\EventService;
use App\Services\PythonBridgeService;

/**
 * El panel del operador: cómo se usa la app y en qué se va el dinero.
 *
 * **Sólo administrador, y no por costumbre**: aquí se ve el correo de cada
 * atleta, cuándo entró por última vez y cuánto ha costado. Es el dato más
 * sensible que sirve la API, y por eso las dos rutas llevan
 * `#[Authorize(['admin'])]` en vez del `['admin','user']` del resto.
 *
 * Las dos mitades del panel salen de sitios distintos a propósito: el uso, de
 * MySQL; el gasto, del libro del AiService. Van en rutas separadas para que si
 * el puente está caído se siga viendo el uso — ver EventService::spending().
 */
class MetricsController extends Controller
{
    /** Uso: embudo, retención, actividad diaria y la tabla por atleta. */
    #[Route('/admin/metrics', 'GET')]
    #[Authorize(['admin'])]
    public function usage()
    {
        // La ventana por defecto son 30 días. Se puede pedir otra, acotada
        // entre 1 y 365: sin tope, un `?days=99999` obliga a MySQL a recorrer
        // la tabla entera, y el índice es por fecha.
        $dias = isset($_GET['days']) ? (int) $_GET['days'] : 30;
        $dias = max(1, min(365, $dias));
        $this->json(['status' => 'success', 'data' => (new EventService())->dashboard($dias)]);
    }

    /**
     * Lo que el producto entrega: semanas prescritas contra entrenadas.
     *
     * Ruta aparte por lo mismo que `spending`: sale del AiService, y si el
     * puente esta caido el resto del panel tiene que seguir viendose.
     */
    #[Route('/admin/metrics/loop', 'GET')]
    #[Authorize(['admin'])]
    public function loop()
    {
        $this->json(['status' => 'success', 'data' => (new EventService())->loopFunnel()]);
    }

    #[Route('/admin/metrics/delivery', 'GET')]
    #[Authorize(['admin'])]
    public function delivery()
    {
        $this->json(['status' => 'success', 'data' => (new EventService())->delivery()]);
    }

    /** Gasto por herramienta y por cuenta, del libro del AiService. */
    #[Route('/admin/metrics/spending', 'GET')]
    #[Authorize(['admin'])]
    public function spending()
    {
        $this->json(['status' => 'success', 'data' => (new EventService())->spending()]);
    }

    // --- La salud de la app (Beta 3.0, F1) ------------------------------------
    //
    // Lo que antes sólo se veía por SSH: los trabajos, sus logs, la cola, los errores
    // de los proveedores y de PHP, y cuántas veces corrige algo cada red determinista.
    // Cada parte va por su ruta: si el AiService no responde, la de PHP se sigue viendo.

    /** El servicio y la cola, más el final del log de errores de PHP. */
    #[Route('/admin/metrics/health', 'GET')]
    #[Authorize(['admin'])]
    public function health()
    {
        $this->json(['status' => 'success', 'data' => [
            'aiservice' => $this->sobre((new PythonBridgeService())->adminHealth()),
            'php' => $this->erroresDePhp(),
        ]]);
    }

    #[Route('/admin/metrics/jobs', 'GET')]
    #[Authorize(['admin'])]
    public function jobs()
    {
        $limite = isset($_GET['limit']) ? (int) $_GET['limit'] : 60;
        $this->json(['status' => 'success', 'data' => $this->sobre((new PythonBridgeService())->adminJobs($limite))]);
    }

    #[Route('/admin/metrics/job-log/{id}', 'GET')]
    #[Authorize(['admin'])]
    public function jobLog($id)
    {
        $id = preg_replace('/\D/', '', (string) $id);
        if ($id === '') {
            $this->json(['status' => 'error', 'message' => 'Trabajo inválido'], 400);
        }
        $lineas = isset($_GET['lines']) ? (int) $_GET['lines'] : 200;
        $this->json(['status' => 'success', 'data' => $this->sobre((new PythonBridgeService())->adminJobLog($id, $lineas))]);
    }

    /** Los errores de pantalla de la última semana (Beta 3.0, F3). */
    #[Route('/admin/metrics/client-errors', 'GET')]
    #[Authorize(['admin'])]
    public function clientErrors()
    {
        $this->json(['status' => 'success', 'data' => (new EventService())->clientErrors(7)]);
    }

    /** Los atletas que gastaron más de 1 USD en un día de la última semana (Beta 3.0, F2). */
    #[Route('/admin/metrics/alerts', 'GET')]
    #[Authorize(['admin'])]
    public function alerts()
    {
        $this->json(['status' => 'success', 'data' => (new \App\Services\SpendingAlertService())->alertas(7)]);
    }

    #[Route('/admin/metrics/nets', 'GET')]
    #[Authorize(['admin'])]
    public function nets()
    {
        $dias = isset($_GET['days']) ? (int) $_GET['days'] : 30;
        $this->json(['status' => 'success', 'data' => $this->sobre((new PythonBridgeService())->adminNets($dias))]);
    }

    /** La respuesta del puente, o `available:false` con el motivo. Nunca lanza. */
    private function sobre(array $res): array
    {
        $datos = $res['ok'] ? ($res['data']['data'] ?? null) : null;
        if (!is_array($datos)) {
            $motivo = $res['data']['detail'] ?? $res['error'] ?? 'El AiService no respondió';
            return ['available' => false, 'error' => is_string($motivo) ? $motivo : 'El AiService no respondió'];
        }
        $datos['available'] = true;
        return $datos;
    }

    /**
     * El final del log de errores de PHP, saneado. Sólo si PHP escribe en un archivo
     * que se pueda leer (en cPanel, sí; con el servidor de desarrollo va a la consola).
     */
    private function erroresDePhp(int $lineas = 80): array
    {
        $ruta = (string) ini_get('error_log');
        // En cPanel el valor de fábrica es RELATIVO («error_log»): PHP lo escribe junto al
        // script que corre (api/v1/), y aquí se buscaba en el directorio de trabajo.
        if ($ruta !== '' && !preg_match('#^([A-Za-z]:)?[\\\\/]#', $ruta)) {
            $ruta = dirname((string) ($_SERVER['SCRIPT_FILENAME'] ?? __FILE__)) . DIRECTORY_SEPARATOR . $ruta;
        }
        if ($ruta === '' || !is_file($ruta) || !is_readable($ruta)) {
            return ['available' => false, 'error' => 'PHP no escribe sus errores en un archivo legible en este servidor.'];
        }
        $tamano = filesize($ruta) ?: 0;
        $fh = fopen($ruta, 'rb');
        if ($fh === false) {
            return ['available' => false, 'error' => 'No se pudo abrir el log de PHP.'];
        }
        // Sólo los últimos ~64 KB: el log de un hosting puede pesar cientos de MB.
        $desde = max(0, $tamano - 65536);
        fseek($fh, $desde);
        $texto = (string) stream_get_contents($fh);
        fclose($fh);
        $todas = preg_split('/\r?\n/', trim($texto)) ?: [];
        if ($desde > 0) {
            array_shift($todas); // la primera puede venir cortada a la mitad
        }
        $cola = array_slice($todas, -$lineas);
        $saneadas = array_map(fn ($l) => $this->sanear($l), $cola);
        return [
            'available' => true,
            'lineas' => $saneadas,
            'aviso' => ($desde > 0 || count($todas) > count($cola))
                ? 'Mostrando las últimas ' . count($cola) . ' líneas del log de PHP.' : '',
        ];
    }

    private function sanear(string $linea): string
    {
        $linea = preg_replace('/[\w.+-]+@[\w-]+\.[\w.-]+/', '***@***', $linea);
        $linea = preg_replace('/(?i)(password|contrase[nñ]a|token|secret)(["\']?\s*[:=]\s*["\']?)([^\s"\',}]+)/u', '$1$2***', $linea);
        $linea = preg_replace('/\b[A-Za-z0-9+\/=_\-]{40,}\b/', '***', $linea);
        return mb_strcut((string) $linea, 0, 500, 'UTF-8');
    }
}
