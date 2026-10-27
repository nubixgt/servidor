<?php
namespace App\Repositories;

use App\Utils\CsvLector;
use App\Utils\Database;

class ImportacionRepository
{
    /** Columnas de stg_matriz que son TEXT; el resto admite 255 caracteres. */
    private const TEXTO_LARGO = ['direccion', 'ultima_observacion', 'resolucion'];

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function crearLote(string $archivo, string $ruta, string $modo, ?int $usuarioId, string $estado = 'PENDIENTE'): int
    {
        return $this->db->insert(
            'INSERT INTO stg_import_lote(archivo, ruta, modo, estado, usuario_id) VALUES (?, ?, ?, ?, ?)',
            [$archivo, $ruta, $modo, $estado, $usuarioId]
        );
    }

    public function lote(int $id): ?array
    {
        return $this->db->one('SELECT l.*, u.usuario FROM stg_import_lote l LEFT JOIN seg_usuario u ON u.id = l.usuario_id WHERE l.id = ?', [$id]);
    }

    public function listar(): array
    {
        return $this->db->all(
            'SELECT l.id, l.archivo, l.modo, l.estado, l.progreso, l.mensaje, l.total_filas, l.filas_insertadas,
                    l.filas_actualizadas, l.filas_rechazadas, l.advertencias, l.creado_en, l.iniciado_en, l.finalizado_en,
                    l.formato, l.codificacion, u.usuario
             FROM stg_import_lote l LEFT JOIN seg_usuario u ON u.id = l.usuario_id ORDER BY l.id DESC LIMIT 50');
    }

    public function resumenErrores(int $lote): array
    {
        return $this->db->all(
            'SELECT nivel, regla, COUNT(*) AS n, MIN(linea) AS primera_linea FROM stg_import_error
             WHERE lote_id = ? GROUP BY nivel, regla ORDER BY nivel, n DESC', [$lote]);
    }

    public function errores(int $lote): \PDOStatement
    {
        return $this->db->run('SELECT linea, nivel, regla, campo, valor, mensaje FROM stg_import_error WHERE lote_id = ? ORDER BY linea', [$lote]);
    }

    public function actualizar(int $lote, array $campos): void
    {
        $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($campos)));
        $this->db->execute("UPDATE stg_import_lote SET $sets WHERE id = ?", array_merge(array_values($campos), [$lote]));
    }

    /** Cancela un lote que todavía no empezó. */
    public function cancelar(int $lote): bool
    {
        return $this->db->execute("UPDATE stg_import_lote SET estado = 'CANCELADO', finalizado_en = NOW() WHERE id = ? AND estado = 'PENDIENTE'", [$lote]) > 0;
    }

    /** Toma el siguiente lote pendiente (lo marca LEYENDO para que nadie más lo procese). */
    public function tomarPendiente(): ?int
    {
        return $this->db->transaction(function (Database $db) {
            $id = $db->scalar("SELECT id FROM stg_import_lote WHERE estado = 'PENDIENTE' ORDER BY id LIMIT 1 FOR UPDATE SKIP LOCKED");
            if ($id === null) {
                return null;
            }
            $db->execute("UPDATE stg_import_lote SET estado = 'LEYENDO' WHERE id = ?", [$id]);
            return (int) $id;
        });
    }

    public function marcarInterrumpidos(): void
    {
        $this->db->execute(
            "UPDATE stg_import_lote SET estado = 'ERROR', finalizado_en = NOW(),
                    mensaje = 'Proceso interrumpido (reinicio del servicio). Vuelva a cargar el archivo.'
             WHERE estado IN ('LEYENDO','VALIDANDO','TRANSFORMANDO','REFRESCANDO') AND iniciado_en < DATE_SUB(NOW(), INTERVAL 6 HOUR)");
    }

    // ---------------------------------------------------------------- carga y proceso
    public function limpiarMatriz(int $lote): void
    {
        $this->db->execute('DELETE FROM stg_matriz WHERE lote_id = ?', [$lote]);
    }

    /** Inserta filas de la matriz ya mapeadas: cada elemento es [línea, valores]. */
    public function insertarMatriz(int $lote, array $filas): void
    {
        if (!$filas) {
            return;
        }
        $columnas = CsvLector::destinos();
        $una = '(' . implode(',', array_fill(0, count($columnas) + 2, '?')) . ')';
        $params = [];
        $largos = array_map(fn($c) => in_array($c, self::TEXTO_LARGO, true) ? null : 255, $columnas);
        foreach ($filas as [$linea, $valores]) {
            foreach ($valores as $i => $v) {
                if ($v !== null && $largos[$i] !== null && mb_strlen($v) > $largos[$i]) {
                    $valores[$i] = mb_substr($v, 0, $largos[$i]);
                }
            }
            array_push($params, $lote, $linea, ...$valores);
        }
        $this->db->execute(
            'INSERT INTO stg_matriz(lote_id, linea, ' . implode(', ', $columnas) . ') VALUES ' . implode(',', array_fill(0, count($filas), $una)),
            $params
        );
    }

    public function procesar(int $lote, bool $soloValidar): void
    {
        $this->db->execute('CALL stg_procesar_lote(?, ?)', [$lote, $soloValidar ? 1 : 0]);
    }

    public function refrescarTablero(): void
    {
        $this->db->execute('CALL mart_refrescar()');
        $this->db->execute("INSERT INTO mart_meta(clave, valor) VALUES ('pendiente_refresco', '0') ON DUPLICATE KEY UPDATE valor = '0'");
        $this->invalidarCache();
    }

    public function invalidarCache(): void
    {
        $this->db->execute("INSERT INTO mart_meta(clave, valor) VALUES ('cache_version', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)", [(string) microtime(true)]);
    }

    // ---------------------------------------------------------------- reporte de conciliación
    /** Conteo de la matriz cargada por departamento × estado. */
    public function conteoMatriz(int $lote): array
    {
        return $this->db->all(
            "SELECT CAST(NULLIF(REGEXP_SUBSTR(TRIM(municipio), '^[0-9]{2}'), '') AS UNSIGNED) AS dep, UPPER(TRIM(estado_actual)) AS estado, COUNT(*) AS n
             FROM stg_matriz WHERE lote_id = ? GROUP BY 1, 2", [$lote]);
    }

    /** Conteo de lo que quedó en la base para ese lote por departamento × estado. */
    public function conteoBase(int $lote): array
    {
        return $this->db->all(
            'SELECT mu.departamento_id AS dep, ce.texto_origen AS estado, COUNT(*) AS n
             FROM core_expediente e JOIN ref_municipio mu ON mu.id = e.municipio_id JOIN ref_cat_estado ce ON ce.id = e.estado_id
             WHERE e.lote_id = ? GROUP BY 1, 2', [$lote]);
    }

    public function nombresDepartamentos(): array
    {
        $out = [];
        foreach ($this->db->all('SELECT id, nombre FROM ref_departamento') as $d) {
            $out[(int) $d['id']] = $d['nombre'];
        }
        return $out;
    }

    public function erroresPorRegla(int $lote, string $nivel): array
    {
        $out = [];
        foreach ($this->db->all('SELECT regla, COUNT(*) AS n FROM stg_import_error WHERE lote_id = ? AND nivel = ? GROUP BY regla', [$lote, $nivel]) as $r) {
            $out[$r['regla']] = (int) $r['n'];
        }
        return $out;
    }

    public function totales(): array
    {
        return [
            (int) $this->db->scalar('SELECT COUNT(*) FROM core_persona'),
            (int) $this->db->scalar('SELECT COUNT(*) FROM core_expediente'),
        ];
    }
}
