<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\FiltrosDTO;
use App\Entities\Usuario;
use App\Utils\Security;

/**
 * Traduce los filtros del tablero a SQL sobre core_v_expediente (alias v).
 */
class FiltroService
{
    /** clave => [condición SQL, título, prioridad] */
    public const REGLAS = [
        'qa' => ['v.q_fall_activo', 'Fallecidos que continúan activos en nómina', 'critica'],
        'qs' => ['v.q_susp_activo', 'Suspendidos que continúan activos en nómina', 'alta'],
        'old' => ['v.antiguedad = 6', 'Solicitudes en trámite con más de 365 días', 'alta'],
        'nov' => ['(v.estado_id = 2 AND v.visita_cod = 0)', 'Solicitudes en trámite sin visita', 'alta'],
        'rpn' => ['(v.ult_resolucion_familia_id = 1 AND v.situacion_nomina = 0)', 'Resolución positiva sin ingreso a nómina', 'media'],
        'vpr' => ['(v.visita_cod = 1 AND v.ult_resolucion_tipo_id IS NULL)', 'Visita positiva sin resolución', 'media'],
        'qu' => ['v.flag_dpi_duplicado', 'DPI registrado en más de un expediente', 'media'],
        'qx' => ['v.flag_exp_duplicado', 'Número de expediente duplicado', 'media'],
        'qf' => ['v.q_fechas', 'Fechas inconsistentes', 'media'],
        'qd' => ['v.q_doc', 'Documento sin CUI válido', 'baja'],
        'qe' => ['v.q_edad', 'Edad fuera de rango', 'baja'],
        'qm' => ['v.q_sin_tel', 'Sin teléfono de contacto', 'baja'],
    ];

    /** @return array{0: string, 1: array} fragmento " AND …" y sus parámetros */
    public function where(FiltrosDTO $fl): array
    {
        $sql = [];
        $p = [];

        if ($fl->dep !== null) {
            $sql[] = 'v.departamento_id = ?';
            $p[] = $fl->dep;
        }
        if ($fl->mun !== null) {
            $sql[] = 'v.municipio_id = ?';
            $p[] = $fl->mun;
        }
        if ($fl->anio !== null) {
            if ($fl->anio === 0) {
                $sql[] = 'v.fecha_solicitud IS NULL';
            } else {
                $sql[] = 'v.fecha_solicitud >= ? AND v.fecha_solicitud < ?';
                $p[] = sprintf('%04d-01-01', $fl->anio);
                $p[] = sprintf('%04d-01-01', $fl->anio + 1);
            }
        }
        if ($fl->e !== null) {
            $sql[] = 'v.estado_orden = ?';
            $p[] = $fl->e;
        }
        if ($fl->r !== null) {
            if ($fl->r === 9) {
                $sql[] = 'v.ult_resolucion_tipo_id IS NULL';
            } else {
                $sql[] = 'v.ult_resolucion_familia_id = (SELECT id FROM ref_cat_familia_resolucion WHERE orden = ?)';
                $p[] = $fl->r;
            }
        }
        if ($fl->n !== null) {
            $sql[] = 'v.situacion_nomina = ?';
            $p[] = $fl->n;
        }
        if ($fl->v !== null) {
            $sql[] = 'v.visita_cod = ?';
            $p[] = $fl->v;
        }
        if ($fl->s !== null) {
            $sql[] = $fl->s ? 'v.fecha_suspension IS NOT NULL' : 'v.fecha_suspension IS NULL';
        }
        if ($fl->f !== null) {
            $sql[] = $fl->f ? 'v.fecha_fallecimiento IS NOT NULL' : 'v.fecha_fallecimiento IS NULL';
        }
        if ($fl->a !== null) {
            $sql[] = 'v.banda_edad = ?';
            $p[] = $fl->a;
        }
        if ($fl->am !== null) {
            $sql[] = 'v.banda_edad >= ?';
            $p[] = $fl->am;
        }
        if ($fl->t !== null) {
            $sql[] = 'v.antiguedad = ?';
            $p[] = $fl->t;
        }
        if ($fl->regla) {
            if (!isset(self::REGLAS[$fl->regla])) {
                throw new HttpException('Regla desconocida', 400);
            }
            $sql[] = self::REGLAS[$fl->regla][0];
        }
        if ($fl->q !== null && trim($fl->q) !== '') {
            $this->texto(trim($fl->q), $sql, $p);
        }

        return [$sql ? ' AND ' . implode(' AND ', $sql) : '', $p];
    }

    /** Filtro territorial del usuario (municipio o departamento asignado). */
    public function ambito(Usuario $u, string $alias = 'v'): array
    {
        if ($u->municipioId) {
            return [" AND $alias.municipio_id = ?", [$u->municipioId]];
        }
        if ($u->departamentoId) {
            return [" AND $alias.departamento_id = ?", [$u->departamentoId]];
        }
        return ['', []];
    }

    private function texto(string $q, array &$sql, array &$p): void
    {
        $digitos = preg_replace('/\D/', '', $q);
        if ($digitos !== '' && strlen($digitos) >= 3 && !preg_match('/\pL/u', $q)) {
            // documento, teléfono, número de expediente o documento del apoderado (por prefijo)
            $pat = $digitos . '%';
            $sql[] = 'v.id IN (
                SELECT e.id FROM core_expediente e WHERE e.persona_id IN
                    (SELECT id FROM core_persona WHERE documento_numero LIKE ? OR telefono LIKE ?)
                UNION SELECT e.id FROM core_expediente e WHERE e.numero LIKE ?
                UNION SELECT e.id FROM core_expediente e WHERE e.apoderado_id IN (SELECT id FROM core_apoderado WHERE numero_id LIKE ?))';
            array_push($p, $pat, $pat, $pat, $pat);
            return;
        }

        // nombre de la persona o del apoderado: todas las palabras, sin importar tildes ni mayúsculas
        $tokens = array_slice(array_values(array_filter(explode(' ', Security::norm($q)), fn($t) => $t !== '')), 0, 6);
        if (!$tokens) {
            return;
        }
        $pats = array_map(fn($t) => '%' . str_replace(['%', '_'], '', $t) . '%', $tokens);
        $nombre = implode(' AND ', array_fill(0, count($tokens), 'pp.nombre_norm LIKE ?'));
        $ap = implode(' AND ', array_fill(0, count($tokens), 'a.nombre_norm LIKE ?'));
        $sql[] = "v.id IN (
            SELECT e.id FROM core_expediente e WHERE e.persona_id IN (SELECT pp.id FROM core_persona pp WHERE $nombre)
            UNION SELECT e.id FROM core_expediente e WHERE e.apoderado_id IN (SELECT a.id FROM core_apoderado a WHERE $ap))";
        array_push($p, ...$pats, ...$pats);
    }
}
