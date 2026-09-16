<?php
namespace App\Repositories\Search;

use App\Utils\Database;
use PDO;

class SearchRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Búsqueda global en TODAS las tablas del sistema MAGA.
     */
    public function globalSearch(string $query, int $limit = 6): array
    {
        $results = [];
        $formattedQuery = preg_replace('/\s+/', '%', trim($query));
        $param   = "%$formattedQuery%";

        // ── 1. PRODUCTORES ──────────────────────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT id, nombre, apellido, dpi, municipio, departamento, tipo, estado
                 FROM productores
                 WHERE nombre LIKE ? OR apellido LIKE ? OR dpi LIKE ?
                    OR finca LIKE ? OR municipio LIKE ? OR departamento LIKE ?
                 ORDER BY nombre ASC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $param, $param, $param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Productor',
                    'icon'      => 'user',
                    'primary'   => trim($row['nombre'] . ' ' . $row['apellido']),
                    'secondary' => 'DPI: ' . $row['dpi'] . ' · ' . $row['municipio'] . ', ' . $row['departamento'],
                    'badge'     => $row['tipo'] ?? null,
                    'route'     => '/admin/productores',
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 2. CONGRESISTAS / DIPUTADOS ─────────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT id, nombre
                 FROM votaciones_congresistas
                 WHERE nombre LIKE ? OR nombre_normalizado LIKE ?
                 ORDER BY nombre ASC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Congresista',
                    'icon'      => 'building',
                    'primary'   => $row['nombre'],
                    'secondary' => 'Congresista de la República',
                    'badge'     => null,
                    'route'     => '/admin/votaciones?tab=congresistas&search=' . urlencode($row['nombre']),
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 3. BLOQUES / PARTIDOS LEGISLATIVOS ──────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT id, nombre, nombre_corto
                 FROM votaciones_bloques
                 WHERE nombre LIKE ? OR nombre_corto LIKE ?
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Partido / Bloque',
                    'icon'      => 'building',
                    'primary'   => $row['nombre'],
                    'secondary' => 'Siglas: ' . ($row['nombre_corto'] ?? 'N/D'),
                    'badge'     => $row['nombre_corto'] ?? null,
                    'route'     => '/admin/votaciones?tab=bloques&search=' . urlencode($row['nombre']),
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 4. EVENTOS DE VOTACIÓN ───────────────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT e.id, e.numero_evento, e.titulo, e.sesion_numero,
                        DATE_FORMAT(e.fecha_hora, '%d/%m/%Y') as fecha,
                        IFNULL(r.resultado, 'PENDIENTE') as resultado
                 FROM votaciones_eventos e
                 LEFT JOIN votaciones_resumen_eventos r ON e.id = r.evento_id
                 WHERE e.titulo LIKE ? OR e.numero_evento LIKE ? OR e.sesion_numero LIKE ?
                 ORDER BY e.fecha_hora DESC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Evento de Votación',
                    'icon'      => 'calendar',
                    'primary'   => 'Evento ' . $row['numero_evento'] . ': ' . $row['titulo'],
                    'secondary' => 'Sesión ' . $row['sesion_numero'] . ' · ' . $row['fecha'] . ' · ' . $row['resultado'],
                    'badge'     => $row['resultado'],
                    'route'     => '/admin/votaciones?tab=eventos&search=' . urlencode($row['titulo']),
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 5. LICENCIAS Y PERMISOS (VISAR) ─────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT id, titular, documento, tipo, estado,
                        DATE_FORMAT(fecha_emision, '%d/%m/%Y') as fecha_emision
                 FROM visar_licencias
                 WHERE titular LIKE ? OR documento LIKE ? OR identificacion LIKE ? OR tipo LIKE ?
                 ORDER BY fecha_emision DESC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Licencia VISAR',
                    'icon'      => 'document',
                    'primary'   => $row['titular'],
                    'secondary' => 'Doc: ' . $row['documento'] . ' · Tipo: ' . $row['tipo'] . ' · ' . $row['estado'],
                    'badge'     => $row['estado'],
                    'route'     => '/admin/licencias',
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 6. INSPECCIONES (VISAR) ──────────────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT id, nombre, tipo_inspeccion, departamento, municipio, resultado
                 FROM visar_inspecciones
                 WHERE nombre LIKE ? OR departamento LIKE ? OR municipio LIKE ? OR tipo_inspeccion LIKE ?
                 ORDER BY id DESC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Inspección VISAR',
                    'icon'      => 'shield',
                    'primary'   => $row['nombre'],
                    'secondary' => $row['tipo_inspeccion'] . ' · ' . $row['municipio'] . ', ' . $row['departamento'],
                    'badge'     => $row['resultado'] ?? null,
                    'route'     => '/admin/sanidad',
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 7. ENTREGAS ALIMENTARIAS (VISAN) ─────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT id, departamento, municipio, tipo_asistencia, raciones, familias,
                        DATE_FORMAT(fecha, '%d/%m/%Y') as fecha
                 FROM visan_entregas
                 WHERE departamento LIKE ? OR municipio LIKE ? OR tipo_asistencia LIKE ?
                 ORDER BY fecha DESC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Entrega VISAN',
                    'icon'      => 'heart',
                    'primary'   => $row['municipio'] . ', ' . $row['departamento'],
                    'secondary' => $row['tipo_asistencia'] . ' · ' . number_format($row['raciones']) . ' raciones · ' . number_format($row['familias']) . ' familias · ' . $row['fecha'],
                    'badge'     => $row['tipo_asistencia'],
                    'route'     => '/admin/visan/tabla',
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 8. EXTENSIÓN RURAL ────────────────────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT id, municipio, departamento, tipo_asistencia, tecnico
                 FROM extension_rural
                 WHERE municipio LIKE ? OR departamento LIKE ? OR tipo_asistencia LIKE ? OR tecnico LIKE ?
                 ORDER BY id DESC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Extensión Rural',
                    'icon'      => 'map',
                    'primary'   => $row['municipio'] . ', ' . $row['departamento'],
                    'secondary' => 'Asistencia: ' . ($row['tipo_asistencia'] ?? 'N/D') . ' · Técnico: ' . ($row['tecnico'] ?? 'N/D'),
                    'badge'     => null,
                    'route'     => '/admin/extension',
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 9. USUARIOS DEL SISTEMA ─────────────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, nombre_completo, rol as role
                 FROM maga_usuarios
                 WHERE username LIKE ? OR nombre_completo LIKE ? OR rol LIKE ?
                 ORDER BY nombre_completo ASC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Usuario',
                    'icon'      => 'user-group',
                    'primary'   => $row['nombre_completo'] ?? $row['username'],
                    'secondary' => 'Usuario: ' . $row['username'] . ' · Rol: ' . $row['role'],
                    'badge'     => $row['role'],
                    'route'     => '/admin/users',
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 10. ACTIVIDADES DEL DESPACHO ─────────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT a.id, a.titulo, a.categoria, a.estado, t.nombre as tecnico,
                        DATE_FORMAT(a.fecha_creacion, '%d/%m/%Y') as fecha
                 FROM despacho_actividades a
                 LEFT JOIN despacho_tecnicos t ON a.tecnico_id = t.id
                 WHERE a.titulo LIKE ? OR a.descripcion LIKE ? OR a.categoria LIKE ? OR t.nombre LIKE ?
                 ORDER BY a.fecha_creacion DESC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Actividad Ministerial',
                    'icon'      => 'document',
                    'primary'   => $row['titulo'],
                    'secondary' => ($row['categoria'] ?? 'General') . ' · ' . ($row['tecnico'] ?? 'N/D') . ' · ' . $row['fecha'],
                    'badge'     => $row['estado'],
                    'route'     => '/admin/actividades-despacho',
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 11. EJECUCIÓN FÍSICA VIDER ────────────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT id, departamento, municipio, fisico_tipo, fisico_ejecutado,
                        DATE_FORMAT(fecha, '%d/%m/%Y') as fecha
                 FROM vider_ejecucion
                 WHERE departamento LIKE ? OR municipio LIKE ?
                 ORDER BY fecha DESC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Ejecución VIDER',
                    'icon'      => 'map',
                    'primary'   => $row['municipio'] . ', ' . $row['departamento'],
                    'secondary' => number_format($row['fisico_ejecutado']) . ' ' . $row['fisico_tipo'] . ' · ' . $row['fecha'],
                    'badge'     => null,
                    'route'     => '/admin/vider/tabla',
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 12. COOPERATIVAS TOBANIK (VIDER) ──────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT id, departamento, nombre_cooperativa, productores, monto_otorgado
                 FROM vider_tobanik
                 WHERE nombre_cooperativa LIKE ? OR departamento LIKE ?
                 ORDER BY nombre_cooperativa ASC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Cooperativa Tobanik',
                    'icon'      => 'user-group',
                    'primary'   => $row['nombre_cooperativa'],
                    'secondary' => $row['departamento'] . ' · ' . $row['productores'] . ' productores · Q' . number_format($row['monto_otorgado'], 2),
                    'badge'     => null,
                    'route'     => '/admin/vider/tobanik',
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        // ── 13. EJECUCIÓN PRESUPUESTARIA ──────────────────────────────
        try {
            $stmt = $this->db->prepare(
                "SELECT e.id, c.nombre as categoria, c.tipo, e.ejercicio_fiscal, e.pct_ejec,
                        DATE_FORMAT(e.fecha_corte, '%d/%m/%Y') as fecha_corte
                 FROM presupuesto_ejecucion e
                 JOIN presupuesto_categorias c ON e.categoria_id = c.id
                 WHERE c.nombre LIKE ? OR c.codigo LIKE ?
                 ORDER BY e.fecha_corte DESC
                 LIMIT ?"
            );
            $stmt->execute([$param, $param, $limit]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $results[] = [
                    'type'      => 'Ejecución Presupuestaria',
                    'icon'      => 'document',
                    'primary'   => $row['categoria'],
                    'secondary' => $row['tipo'] . ' · Ejercicio ' . $row['ejercicio_fiscal'] . ' · ' . number_format($row['pct_ejec'], 1) . '% ejecutado',
                    'badge'     => $row['fecha_corte'],
                    'route'     => '/admin/presupuesto',
                    'entity_id' => $row['id'],
                ];
            }
        } catch (\Throwable $e) {}

        return $results;
    }
}
