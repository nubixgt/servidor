<?php
namespace App\Repositories;

use App\Services\FiltroService;
use App\Utils\Database;
use App\Utils\Security;

class ExpedienteRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ---------------------------------------------------------------- lectura
    /** Página de resultados; $col viene de una lista cerrada del servicio. Los nulos van siempre al final. */
    public function buscar(string $where, array $params, string $col, bool $desc, ?array $cursor, int $limite): array
    {
        $op = $desc ? '<' : '>';
        $dir = $desc ? 'DESC' : 'ASC';
        $pag = '';
        $pagPrm = [];
        if ($cursor) {
            [$ultimoValor, $ultimoId] = $cursor;
            if ($ultimoValor === null) {
                $pag = " AND ($col IS NULL AND v.id $op ?)";
                $pagPrm = [$ultimoId];
            } else {
                $pag = " AND (($col, v.id) $op (?, ?) OR $col IS NULL)";
                $pagPrm = [$ultimoValor, $ultimoId];
            }
        }

        // Sin filtros y en orden descendente (los nulos ya quedan al final): se eligen los ids directo del índice de
        // core_expediente y solo entonces se une con el resto de tablas; evita unir y ordenar todos los expedientes.
        if ($where === '' && $desc && in_array($col, ['v.fecha_solicitud', 'v.numero'], true)) {
            $ec = 'e.' . substr($col, 2);
            $pagE = str_replace(['v.id', $col], ['e.id', $ec], $pag);
            return $this->db->all(
                "SELECT v.* FROM (SELECT e.id FROM core_expediente e WHERE 1=1 $pagE ORDER BY $ec DESC, e.id DESC LIMIT ?) t
                 JOIN core_v_expediente v ON v.id = t.id ORDER BY ($col IS NULL), $col DESC, v.id DESC",
                array_merge($pagPrm, [$limite])
            );
        }

        $params = array_merge($params, $pagPrm);
        $params[] = $limite;
        return $this->db->all(
            "SELECT v.* FROM core_v_expediente v WHERE 1=1 $where $pag ORDER BY ($col IS NULL), $col $dir, v.id $dir LIMIT ?",
            $params
        );
    }

    /** Total de coincidencias, con tope en 10.001 para no recorrer toda la base. */
    public function contar(string $where, array $params): int
    {
        return (int) $this->db->scalar("SELECT COUNT(*) FROM (SELECT 1 FROM core_v_expediente v WHERE 1=1 $where LIMIT 10001) x", $params);
    }

    public function obtener(int $eid, string $amb, array $ap): ?array
    {
        return $this->db->one("SELECT v.* FROM core_v_expediente v WHERE v.id = ? $amb", array_merge([$eid], $ap));
    }

    public function visitas(int $eid): array
    {
        return $this->db->all(
            'SELECT v.id, v.fecha, c.codigo AS resultado, v.observacion, v.tecnico, v.origen, v.creado_en
             FROM core_visita v JOIN ref_cat_resultado_visita c ON c.id = v.resultado_id
             WHERE v.expediente_id = ? ORDER BY v.fecha DESC, v.id DESC', [$eid]);
    }

    public function resoluciones(int $eid): array
    {
        return $this->db->all(
            'SELECT s.id, s.fecha, t.codigo, t.nombre AS tipo, f.nombre AS familia, s.numero, s.descripcion, s.origen
             FROM core_resolucion s JOIN ref_cat_tipo_resolucion t ON t.id = s.tipo_id
             JOIN ref_cat_familia_resolucion f ON f.id = t.familia_id
             WHERE s.expediente_id = ? ORDER BY s.fecha IS NULL, s.fecha DESC, s.id DESC', [$eid]);
    }

    public function observaciones(int $eid): array
    {
        return $this->db->all(
            'SELECT o.id, o.fecha, o.texto, o.origen, u.usuario
             FROM core_observacion o LEFT JOIN seg_usuario u ON u.id = o.usuario_id
             WHERE o.expediente_id = ? ORDER BY o.fecha DESC, o.id DESC', [$eid]);
    }

    public function nomina(int $eid): array
    {
        return $this->db->all('SELECT m.id, m.tipo, m.fecha, m.motivo, m.origen FROM core_movimiento_nomina m WHERE m.expediente_id = ? ORDER BY m.fecha, m.id', [$eid]);
    }

    public function estados(int $eid): array
    {
        $filas = $this->db->all(
            'SELECT x.id, c.nombre AS estado, c.orden, x.fecha, x.vigente, x.motivo, x.origen, x.creado_en, u.usuario
             FROM core_estado_expediente x JOIN ref_cat_estado c ON c.id = x.estado_id
             LEFT JOIN seg_usuario u ON u.id = x.usuario_id
             WHERE x.expediente_id = ? ORDER BY x.creado_en DESC, x.id DESC', [$eid]);
        foreach ($filas as &$f) {
            $f['vigente'] = (bool) $f['vigente'];
        }
        return $filas;
    }

    public function eventos(int $eid): array
    {
        // Mismo contenido que core_v_evento, pero con el filtro dentro de cada parte del UNION: con consultas preparadas
        // el servidor no lo empuja desde la vista y arma la línea de tiempo de todos los expedientes antes de filtrar.
        return $this->db->all(
            "SELECT * FROM (
                SELECT e.id AS expediente_id, e.fecha_solicitud AS fecha, 'SOLICITUD' AS tipo, 'Solicitud recibida' AS titulo,
                       CONCAT('Expediente ', COALESCE(e.numero, 's/n')) AS detalle, e.origen, e.creado_en
                  FROM core_expediente e WHERE e.id = ? AND e.fecha_solicitud IS NOT NULL
                UNION ALL
                SELECT v.expediente_id, v.fecha, CONCAT('VISITA_', r.codigo), CONCAT('Visita ', LOWER(r.nombre)),
                       CONCAT_WS(' · ', v.observacion, v.tecnico), v.origen, v.creado_en
                  FROM core_visita v JOIN ref_cat_resultado_visita r ON r.id = v.resultado_id WHERE v.expediente_id = ?
                UNION ALL
                SELECT o.expediente_id, o.fecha, 'OBSERVACION', 'Observación', o.texto, o.origen, o.creado_en
                  FROM core_observacion o WHERE o.expediente_id = ?
                UNION ALL
                SELECT s.expediente_id, s.fecha, 'RESOLUCION', CONCAT('Resolución ', t.codigo COLLATE utf8mb4_unicode_ci),
                       CONCAT_WS(' · ', t.nombre, s.numero, s.descripcion), s.origen, s.creado_en
                  FROM core_resolucion s JOIN ref_cat_tipo_resolucion t ON t.id = s.tipo_id WHERE s.expediente_id = ?
                UNION ALL
                SELECT m.expediente_id, m.fecha, CONCAT('NOMINA_', m.tipo),
                       CASE m.tipo WHEN 'ALTA' THEN 'Ingreso a nómina' ELSE 'Salida de nómina' END, m.motivo, m.origen, m.creado_en
                  FROM core_movimiento_nomina m WHERE m.expediente_id = ?
                UNION ALL
                SELECT e.id, e.fecha_suspension, 'SUSPENSION', 'Suspensión registrada', NULL, e.origen, e.creado_en
                  FROM core_expediente e WHERE e.id = ? AND e.fecha_suspension IS NOT NULL
                UNION ALL
                SELECT e.id, e.fecha_fallecimiento, 'FALLECIMIENTO', 'Fallecimiento registrado', NULL, e.origen, e.creado_en
                  FROM core_expediente e WHERE e.id = ? AND e.fecha_fallecimiento IS NOT NULL
                UNION ALL
                SELECT x.expediente_id, x.fecha, 'ESTADO', CONCAT('Estado: ', c.nombre), x.motivo, x.origen, x.creado_en
                  FROM core_estado_expediente x JOIN ref_cat_estado c ON c.id = x.estado_id WHERE x.expediente_id = ?
             ) ev ORDER BY fecha IS NULL, fecha, creado_en",
            array_fill(0, 8, $eid)
        );
    }

    public function apoderado(int $id): ?array
    {
        return $this->db->one('SELECT tipo_id, numero_id, nombre FROM core_apoderado WHERE id = ?', [$id]);
    }

    public function otrosExpedientes(int $personaId, int $eid): array
    {
        return $this->db->all('SELECT id, numero, fecha_solicitud, estado_id FROM core_expediente WHERE persona_id = ? AND id <> ? ORDER BY fecha_solicitud', [$personaId, $eid]);
    }

    public function casos(int $eid): array
    {
        return $this->db->all('SELECT regla, estado, comentario, actualizado_en FROM core_caso_revision WHERE expediente_id = ?', [$eid]);
    }

    /** Reglas de alerta y calidad que cumple el expediente: clave => bool. */
    public function reglasAplicables(int $eid): array
    {
        $conds = [];
        foreach (FiltroService::REGLAS as $clave => [$cond]) {
            $conds[] = "($cond) AS $clave";
        }
        $r = $this->db->one('SELECT ' . implode(', ', $conds) . ' FROM core_v_expediente v WHERE v.id = ?', [$eid]);
        return array_map('boolval', $r ?? []);
    }

    // ---------------------------------------------------------------- escritura
    public function crearObservacion(int $eid, string $fecha, string $texto, int $uid): int
    {
        return $this->db->insert('INSERT INTO core_observacion(expediente_id, fecha, texto, usuario_id) VALUES (?, ?, ?, ?)', [$eid, $fecha, $texto, $uid]);
    }

    public function crearVisita(int $eid, string $fecha, string $resultado, ?string $observacion, ?string $tecnico, int $uid): ?int
    {
        $id = $this->db->insert(
            'INSERT INTO core_visita(expediente_id, fecha, resultado_id, observacion, tecnico, usuario_id)
             SELECT ?, ?, id, ?, ?, ? FROM ref_cat_resultado_visita WHERE codigo = ?',
            [$eid, $fecha, $observacion, $tecnico, $uid, $resultado]
        );
        return $id ?: null;
    }

    public function tipoResolucionId(string $codigo): ?int
    {
        $id = $this->db->scalar('SELECT id FROM ref_cat_tipo_resolucion WHERE codigo = ? AND activo = 1', [$codigo]);
        return $id === null ? null : (int) $id;
    }

    public function crearResolucion(int $eid, string $fecha, int $tipoId, ?string $numero, ?string $descripcion, int $uid): int
    {
        return $this->db->insert(
            'INSERT INTO core_resolucion(expediente_id, fecha, tipo_id, numero, descripcion, usuario_id) VALUES (?, ?, ?, ?, ?, ?)',
            [$eid, $fecha, $tipoId, $numero, $descripcion, $uid]
        );
    }

    public function estadoId(string $codigo): ?int
    {
        $id = $this->db->scalar('SELECT id FROM ref_cat_estado WHERE codigo = ?', [$codigo]);
        return $id === null ? null : (int) $id;
    }

    public function cerrarEstadoVigente(int $eid): void
    {
        $this->db->execute('UPDATE core_estado_expediente SET vigente = 0 WHERE expediente_id = ? AND vigente = 1', [$eid]);
    }

    public function crearEstado(int $eid, int $estadoId, string $fecha, string $motivo, int $uid): void
    {
        $this->db->execute(
            'INSERT INTO core_estado_expediente(expediente_id, estado_id, fecha, vigente, motivo, usuario_id) VALUES (?, ?, ?, 1, ?, ?)',
            [$eid, $estadoId, $fecha, $motivo, $uid]
        );
    }

    public function marcarFallecimiento(int $eid, string $fecha): void
    {
        $this->db->execute('UPDATE core_expediente SET fecha_fallecimiento = COALESCE(fecha_fallecimiento, ?) WHERE id = ?', [$fecha, $eid]);
    }

    public function marcarSuspension(int $eid, string $fecha): void
    {
        $this->db->execute('UPDATE core_expediente SET fecha_suspension = ? WHERE id = ?', [$fecha, $eid]);
    }

    public function crearMovimientoNomina(int $eid, string $tipo, string $fecha, ?string $motivo, int $uid): void
    {
        $this->db->execute(
            'INSERT INTO core_movimiento_nomina(expediente_id, tipo, fecha, motivo, usuario_id) VALUES (?, ?, ?, ?, ?)',
            [$eid, $tipo, $fecha, $motivo, $uid]
        );
    }

    public function actualizarContacto(int $personaId, ?string $telefono, ?string $direccion): void
    {
        $this->db->execute('UPDATE core_persona SET telefono = ?, direccion = ? WHERE id = ?', [$telefono, $direccion, $personaId]);
    }

    public function cuiValido(string $dpi): bool
    {
        return (bool) $this->db->scalar('SELECT ref_f_cui_valido(?)', [$dpi]);
    }

    public function municipioExiste(int $id): bool
    {
        return $this->db->scalar('SELECT 1 FROM ref_municipio WHERE id = ?', [$id]) !== null;
    }

    /** Crea la persona o completa los datos de contacto de la existente (misma clave). Devuelve su id. */
    public function guardarPersona(string $clave, string $nombre, string $nombreNorm, string $tipoDoc, ?string $dpi, bool $cuiValido,
        string $fechaNacimiento, ?string $telefono, ?string $direccion, int $municipioId): int
    {
        $this->db->execute(
            'INSERT INTO core_persona(clave, nombre, nombre_norm, documento_tipo, documento_numero, cui_valido, fecha_nacimiento, telefono, direccion, municipio_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                telefono = COALESCE(VALUES(telefono), core_persona.telefono),
                direccion = COALESCE(VALUES(direccion), core_persona.direccion)',
            [$clave, $nombre, $nombreNorm, $tipoDoc, $dpi, (int) $cuiValido, $fechaNacimiento, $telefono, $direccion, $municipioId]
        );
        return (int) $this->db->scalar('SELECT id FROM core_persona WHERE clave = ?', [$clave]);
    }

    public function tieneExpedienteVigente(int $personaId): bool
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM core_expediente WHERE persona_id = ? AND estado_id IN (1, 2)', [$personaId]) > 0;
    }

    public function crearExpediente(?string $numero, int $personaId, int $municipioId, string $fechaSolicitud): int
    {
        return $this->db->insert(
            "INSERT INTO core_expediente(numero, persona_id, municipio_id, fecha_solicitud, estado_id, fecha_estado, origen)
             VALUES (?, ?, ?, ?, 2, ?, 'SISTEMA')",
            [$numero, $personaId, $municipioId, $fechaSolicitud, $fechaSolicitud]
        );
    }

    public function asignarNumero(int $eid, string $numero): void
    {
        $this->db->execute('UPDATE core_expediente SET numero = ? WHERE id = ?', [$numero, $eid]);
    }

    public function guardarCaso(string $regla, int $eid, string $estado, ?string $comentario, int $uid): void
    {
        $this->db->execute(
            'INSERT INTO core_caso_revision(regla, expediente_id, estado, comentario, usuario_id) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE estado = VALUES(estado), comentario = VALUES(comentario), usuario_id = VALUES(usuario_id)',
            [$regla, $eid, $estado, $comentario, $uid]
        );
    }

    /** Recalcula el resumen (última visita, resolución, estado, nómina…) y avisa que el tablero quedó desactualizado. */
    public function recalcularResumen(int $eid): void
    {
        $token = Security::uuid();
        $this->db->execute('INSERT INTO core_resumen_scope(token, expediente_id) VALUES (?, ?)', [$token, $eid]);
        $this->db->execute('CALL core_recalcular_resumen(?)', [$token]);
        $this->marcarRefrescoPendiente();
    }

    public function marcarRefrescoPendiente(): void
    {
        $this->db->execute("INSERT INTO mart_meta(clave, valor) VALUES ('pendiente_refresco', '1') ON DUPLICATE KEY UPDATE valor = '1'");
    }
}
