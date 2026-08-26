<?php
namespace App\DTOs\Presupuesto;

class PresupuestoDTO
{
    public $categoria_id;
    public $ejercicio_fiscal;
    public $asignado;
    public $modificado;
    public $vigente;
    public $devengado;
    public $saldo;
    public $pct_ejec;
    public $fecha_corte;

    public function __construct($data)
    {
        $this->categoria_id = $data['categoria_id'] ?? null;
        $this->ejercicio_fiscal = $data['ejercicio_fiscal'] ?? date('Y');
        $this->asignado = $data['asignado'] ?? 0;
        $this->modificado = $data['modificado'] ?? 0;
        $this->vigente = $data['vigente'] ?? 0;
        $this->devengado = $data['devengado'] ?? 0;
        $this->saldo = $data['saldo'] ?? 0;
        $this->pct_ejec = $data['pct_ejec'] ?? 0;
        $this->fecha_corte = $data['fecha_corte'] ?? date('Y-m-d');
    }

    public static function fromRequest($data)
    {
        return new self($data);
    }

    public function toArray()
    {
        return [
            'categoria_id' => $this->categoria_id,
            'ejercicio_fiscal' => $this->ejercicio_fiscal,
            'asignado' => $this->asignado,
            'modificado' => $this->modificado,
            'vigente' => $this->vigente,
            'devengado' => $this->devengado,
            'saldo' => $this->saldo,
            'pct_ejec' => $this->pct_ejec,
            'fecha_corte' => $this->fecha_corte
        ];
    }

    public static function mapSummary($data)
    {
        return [
            'asignado' => (float)$data['total_asignado'],
            'modificado' => (float)$data['total_modificado'],
            'vigente' => (float)$data['total_vigente'],
            'devengado' => (float)$data['total_devengado'],
            'saldo' => (float)$data['total_saldo'],
            'pct_ejec' => round((float)$data['pct_global'], 2)
        ];
    }

    public static function mapItem($data)
    {
        $nameToShow = $data['nombre'] ?? '';
        if (($data['tipo'] ?? '') === 'UNIDAD_EJECUTORA') {
            $nameToShow = $data['codigo'] ?? '';
        }
        return [
            'id' => $data['id'],
            'name' => ($data['codigo'] ?? '') . ' "' . $nameToShow . '"',
            'asignado' => (float)$data['asignado'],
            'modificado' => (float)$data['modificado'],
            'vigente' => (float)$data['vigente'],
            'devengado' => (float)$data['devengado'],
            'saldo' => (float)$data['saldo'],
            'pct_ejec' => round((float)$data['pct_ejec'], 2),
            'pct_rel' => 0.0,
            'type' => $data['tipo'] ?? null
        ];
    }

    public static function mapCollection($collection)
    {
        $totalVigente = array_sum(array_column($collection, 'vigente'));
        return array_values(array_map(function($data) use ($totalVigente) {
            $mapped = self::mapItem($data);
            if ($totalVigente > 0) {
                $mapped['pct_rel'] = round(($mapped['vigente'] / $totalVigente) * 100, 2);
            } else {
                $mapped['pct_rel'] = 0.0;
            }
            return $mapped;
        }, $collection));
    }
}
