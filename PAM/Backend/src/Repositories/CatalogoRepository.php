<?php
namespace App\Repositories;

use App\Utils\Database;

class CatalogoRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function todos(): array
    {
        return [
            'departamentos' => $this->db->all('SELECT id, codigo, nombre FROM ref_departamento ORDER BY id'),
            'municipios' => $this->db->all('SELECT id, codigo, departamento_id, nombre FROM ref_municipio ORDER BY id'),
            'estados' => $this->db->all('SELECT id, codigo, nombre, orden, color FROM ref_cat_estado ORDER BY orden'),
            'familias_resolucion' => $this->db->all('SELECT id, nombre, orden FROM ref_cat_familia_resolucion ORDER BY orden'),
            'tipos_resolucion' => $this->db->all('SELECT t.id, t.codigo, t.nombre, t.familia_id, t.activo FROM ref_cat_tipo_resolucion t ORDER BY t.codigo'),
            'resultados_visita' => $this->db->all('SELECT id, codigo, nombre FROM ref_cat_resultado_visita ORDER BY id'),
        ];
    }

    public function personasPosibleDuplicado(): int
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM (SELECT nombre_norm, fecha_nacimiento FROM core_persona
             WHERE fecha_nacimiento IS NOT NULL GROUP BY nombre_norm, fecha_nacimiento HAVING COUNT(*) > 1) x'
        );
    }

    public function ultimaImportacion(): ?array
    {
        $r = $this->db->one("SELECT id, archivo, reporte, finalizado_en FROM stg_import_lote WHERE estado = 'COMPLETADO' ORDER BY id DESC LIMIT 1");
        if ($r && $r['reporte'] !== null) {
            $r['reporte'] = json_decode($r['reporte'], true);
        }
        return $r;
    }
}
