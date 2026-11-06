<?php
namespace App\Services;

use App\Repositories\NotificationRepository;
use App\Utils\Database;

/**
 * La alerta de gasto (Beta 3.0, F2): un atleta que gasta más de 1 USD en un día.
 *
 * El tope de versiones frena el "agujero negro" de la Beta 1 (16 planes en un día), pero
 * la semana automática no cuenta para el tope y el chat no tiene ninguno. Esto no frena
 * nada: avisa al administrador, que es el único que ve costos (regla del producto).
 *
 * Dos sitios: el panel de Métricas (`/admin/metrics/alerts`) y un aviso a cada
 * administrador, una sola vez por atleta y día, que deja el cron de las semanas
 * automáticas al pasar cada hora.
 */
class SpendingAlertService
{
    public const UMBRAL_USD = 1.0;

    /** Las alertas de los últimos `$dias`, con el nombre de cada atleta. Nunca lanza. */
    public function alertas(int $dias = 7): array
    {
        $res = (new PythonBridgeService())->usageAlerts(self::UMBRAL_USD, $dias, date('Y-m-d'));
        $datos = $res['ok'] ? ($res['data']['data'] ?? null) : null;
        if (!is_array($datos)) {
            return ['available' => false, 'error' => $res['error'] ?? 'El AiService no respondió', 'alerts' => []];
        }
        $nombres = $this->nombres();
        $alertas = [];
        foreach ($datos['alerts'] ?? [] as $a) {
            $id = preg_match('/^u(\d+)$/', (string) ($a['user_id'] ?? ''), $m) ? (int) $m[1] : null;
            $a['id'] = $id;
            $a['name'] = $id !== null ? ($nombres[$id] ?? "Atleta {$id}") : (string) ($a['user_id'] ?? '');
            $alertas[] = $a;
        }
        return [
            'available' => true,
            'threshold_usd' => (float) ($datos['threshold_usd'] ?? self::UMBRAL_USD),
            'days' => (int) ($datos['days'] ?? $dias),
            'alerts' => $alertas,
        ];
    }

    /**
     * Avisa a cada administrador de las alertas de HOY, una vez por atleta y día (el
     * título lleva atleta y fecha, y `createFor(..., unaVez)` no lo repite).
     * Devuelve cuántos avisos nuevos dejó.
     */
    public function avisarAlAdministrador(): int
    {
        $hoy = date('Y-m-d');
        $alertas = array_filter($this->alertas(1)['alerts'] ?? [], fn($a) => ($a['dia'] ?? '') === $hoy);
        if (!$alertas) {
            return 0;
        }
        $repo = new NotificationRepository();
        $admins = Database::getInstance()->getConnection()
            ->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        $nuevos = 0;
        foreach ($alertas as $a) {
            $usos = [];
            foreach (($a['usos'] ?? []) as $tipo => $n) {
                $usos[] = "{$n} " . ($this->etiquetas()[$tipo] ?? $tipo);
            }
            $titulo = mb_substr("Gasto alto: {$a['name']} el {$hoy}", 0, 120);
            $cuerpo = sprintf(
                '%s lleva %s USD hoy (umbral %s USD): %s. Míralo en Métricas → Salud de la app.',
                $a['name'], number_format((float) $a['cost_usd'], 2, ',', '.'),
                number_format(self::UMBRAL_USD, 2, ',', '.'), implode(', ', $usos) ?: 'sin detalle'
            );
            foreach ($admins as $admin) {
                if ($repo->createFor((int) $admin['id'], null, $titulo, mb_substr($cuerpo, 0, 1000), true)) {
                    $nuevos++;
                }
            }
        }
        return $nuevos;
    }

    /** @return array<int,string> */
    private function nombres(): array
    {
        $out = [];
        foreach (Database::getInstance()->getConnection()->query('SELECT id, name FROM users')->fetchAll() as $f) {
            $out[(int) $f['id']] = (string) $f['name'];
        }
        return $out;
    }

    /** @return array<string,string> */
    private function etiquetas(): array
    {
        return [
            'week' => 'semana(s)', 'season' => 'macrociclo(s)', 'chat' => 'mensaje(s) al coach',
            'strength_routine' => 'lectura(s) de rutina', 'health_intake' => 'consulta(s) de salud',
        ];
    }
}
