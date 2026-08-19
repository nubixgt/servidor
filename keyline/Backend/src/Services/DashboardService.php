<?php
namespace App\Services;

use App\Repositories\ParcelaRepository;
use App\Entities\Parcela;

class DashboardService
{
    private ParcelaRepository $repository;

    private const DEPARTAMENTOS = [
        'Alta Verapaz', 'Baja Verapaz', 'Chimaltenango', 'Chiquimula', 'El Progreso',
        'Escuintla', 'Guatemala', 'Huehuetenango', 'Izabal', 'Jalapa', 'Jutiapa',
        'Petén', 'Quetzaltenango', 'Quiché', 'Retalhuleu', 'Sacatepéquez',
        'San Marcos', 'Santa Rosa', 'Sololá', 'Suchitepéquez', 'Totonicapán', 'Zacapa',
    ];

    private const ESTADOS_PROCESO = ['Levantamiento', 'Diseño', 'Implementado', 'Pendiente'];

    public function __construct()
    {
        $this->repository = new ParcelaRepository();
    }

    public function resumen(array $currentUser): array
    {
        $data = $this->scopedParcelas($currentUser);

        $totalArea = array_sum(array_map(fn(Parcela $p) => (float)($p->areaHa ?: 0), $data));
        $deptos = array_unique(array_filter(array_map(fn($p) => $p->departamento, $data)));
        $municipios = array_unique(array_map(fn($p) => $p->departamento . '|' . $p->municipio, $data));
        $implementadas = count(array_filter($data, fn($p) => $p->estado === 'Implementado'));
        $pendientesValidacion = count(array_filter($data, fn($p) => $p->estadoValidacion === 'Pendiente de revisión'));
        $validadas = count(array_filter($data, fn($p) => $p->estadoValidacion === 'Validado'));
        $familias = array_sum(array_map(fn($p) => (int)($p->numFamiliasBeneficiadas ?: 0), $data));

        $semaforo = [
            'verde' => count(array_filter($data, fn($p) => $p->estado === 'Implementado')),
            'amarillo' => count(array_filter($data, fn($p) => $p->estado === 'Diseño' || $p->estado === 'Levantamiento')),
            'rojo' => count(array_filter($data, fn($p) => $p->estado === 'Pendiente')),
        ];

        $porDepartamento = [];
        foreach (self::DEPARTAMENTOS as $dep) {
            $items = array_values(array_filter($data, fn($p) => $p->departamento === $dep));
            if (!$items) {
                continue;
            }
            $porDepartamento[] = [
                'departamento' => $dep,
                'cantidad' => count($items),
                'area' => array_sum(array_map(fn($p) => (float)($p->areaHa ?: 0), $items)),
                'implementadas' => count(array_filter($items, fn($p) => $p->estado === 'Implementado')),
            ];
        }
        usort($porDepartamento, fn($a, $b) => $b['cantidad'] <=> $a['cantidad'] ?: $b['area'] <=> $a['area']);

        $conTalpetate = count(array_filter($data, fn($p) => $p->talpetate === 'Sí'));
        $sinTalpetate = count(array_filter($data, fn($p) => $p->talpetate === 'No'));
        $conEncharca = count(array_filter($data, fn($p) => $p->encharca === 'Sí'));
        $sinEncharca = count(array_filter($data, fn($p) => $p->encharca === 'No'));
        $profVals = array_values(array_filter(array_map(fn($p) => (float)($p->profundidadSuelo ?: 0), $data), fn($v) => $v > 0));
        $profundidadProm = $profVals ? array_sum($profVals) / count($profVals) : 0;

        $bioList = [];
        foreach ($data as $p) {
            $partes = preg_split('/[,;\/]+/', (string)$p->bioindicadores);
            foreach ($partes as $parte) {
                $k = mb_strtolower(trim($parte));
                if ($k === '') {
                    continue;
                }
                $bioList[$k] = ($bioList[$k] ?? 0) + 1;
            }
        }
        arsort($bioList);
        $topBioindicadores = [];
        $i = 0;
        foreach ($bioList as $nombre => $conteo) {
            if ($i++ >= 10) {
                break;
            }
            $topBioindicadores[] = ['nombre' => $nombre, 'conteo' => $conteo];
        }

        $porEstadoProceso = array_map(fn($estado) => [
            'estado' => $estado,
            'cantidad' => count(array_filter($data, fn($p) => $p->estado === $estado)),
        ], self::ESTADOS_PROCESO);

        $puntosMapa = [];
        foreach ($data as $p) {
            if ($p->latitud === '' || $p->latitud === null || $p->longitud === '' || $p->longitud === null) {
                continue;
            }
            $puntosMapa[] = [
                'id' => $p->id, 'nombre' => $p->nombreParcela, 'lat' => (float)$p->latitud, 'lng' => (float)$p->longitud,
                'estado' => $p->estado, 'departamento' => $p->departamento, 'codigo' => $p->codigo,
            ];
        }

        $ordenados = $data;
        usort($ordenados, fn($a, $b) => strtotime($b->createdAt) <=> strtotime($a->createdAt));
        $registrosRecientes = array_map(fn($p) => [
            'id' => $p->id, 'nombre' => $p->nombreParcela, 'codigo' => $p->codigo, 'tecnico' => $p->tecnicoNombre,
            'fecha' => $p->createdAt, 'departamento' => $p->departamento, 'estado' => $p->estado,
        ], array_slice($ordenados, 0, 8));

        // Tendencias (últimos 30 días vs 30 días previos)
        $now = time();
        $DAY = 86400;
        $dentroDe = fn(Parcela $p, $desde, $hasta) => strtotime($p->createdAt) >= $desde && strtotime($p->createdAt) < $hasta;
        $ultimos30 = array_values(array_filter($data, fn($p) => $dentroDe($p, $now - 30 * $DAY, $now)));
        $previos30 = array_values(array_filter($data, fn($p) => $dentroDe($p, $now - 60 * $DAY, $now - 30 * $DAY)));
        $pctCambio = fn($actual, $previo) => $previo ? (int)round((($actual - $previo) / $previo) * 100) : ($actual > 0 ? 100 : 0);
        $areaUltimos30 = array_sum(array_map(fn($p) => (float)($p->areaHa ?: 0), $ultimos30));
        $areaPrevios30 = array_sum(array_map(fn($p) => (float)($p->areaHa ?: 0), $previos30));
        $tendencias = [
            'parcelas' => ['valor' => count($ultimos30), 'cambioPct' => $pctCambio(count($ultimos30), count($previos30))],
            'areaHa' => ['valor' => $areaUltimos30, 'cambioPct' => $pctCambio($areaUltimos30, $areaPrevios30)],
        ];

        // Insights automáticos
        $insights = [];
        $totalParaPct = count($data) ?: 1;
        $pctEncharca = (int)round(($conEncharca / $totalParaPct) * 100);
        if ($conEncharca > 0 && $pctEncharca >= 15) {
            $insights[] = ['tipo' => 'alert', 'texto' => "$conEncharca parcela(s) ($pctEncharca%) reportan encharcamiento y podrían requerir obras de drenaje o rediseño de canales keyline."];
        } elseif ($conEncharca > 0) {
            $insights[] = ['tipo' => 'alert', 'texto' => "$conEncharca parcela(s) presentan encharcamiento reportado; conviene dar seguimiento técnico."];
        }
        if ($porDepartamento) {
            $top = $porDepartamento[0];
            $areaFmt = number_format($top['area'], 1, '.', ',');
            $insights[] = ['tipo' => 'recommendation', 'texto' => "{$top['departamento']} concentra el mayor número de parcelas registradas ({$top['cantidad']}, {$areaFmt} ha acumuladas)."];
        }
        if ($tendencias['parcelas']['valor'] > 0) {
            $signo = $tendencias['parcelas']['cambioPct'] >= 0 ? 'aumentó' : 'disminuyó';
            $insights[] = ['tipo' => 'trend', 'texto' => "El registro de parcelas $signo " . abs($tendencias['parcelas']['cambioPct']) . "% en los últimos 30 días ({$tendencias['parcelas']['valor']} parcelas nuevas) frente al periodo anterior."];
        }
        if ($pendientesValidacion > 0) {
            $insights[] = ['tipo' => 'alert', 'texto' => "$pendientesValidacion parcela(s) están pendientes de revisión técnica por un supervisor."];
        }
        if ($conTalpetate > 0) {
            $insights[] = ['tipo' => 'recommendation', 'texto' => "$conTalpetate parcela(s) presentan talpetate; priorizar diagnóstico de profundidad antes de diseñar obras de infiltración."];
        }

        return [
            'totales' => [
                'parcelas' => count($data),
                'areaHa' => $totalArea,
                'departamentos' => count($deptos),
                'metaDepartamentos' => count(self::DEPARTAMENTOS),
                'coberturaPct' => (int)round((count($deptos) / count(self::DEPARTAMENTOS)) * 100),
                'municipios' => count($municipios),
                'implementadas' => $implementadas,
                'pendientesValidacion' => $pendientesValidacion,
                'validadas' => $validadas,
                'familiasBeneficiadas' => $familias,
            ],
            'tendencias' => $tendencias,
            'insights' => array_slice($insights, 0, 5),
            'semaforo' => $semaforo,
            'porDepartamento' => array_values($porDepartamento),
            'porEstadoProceso' => $porEstadoProceso,
            'diagnosticoFisico' => [
                'conTalpetate' => $conTalpetate, 'sinTalpetate' => $sinTalpetate,
                'conEncharca' => $conEncharca, 'sinEncharca' => $sinEncharca,
                'profundidadProm' => $profundidadProm, 'muestras' => count($profVals),
            ],
            'topBioindicadores' => $topBioindicadores,
            'puntosMapa' => $puntosMapa,
            'registrosRecientes' => $registrosRecientes,
        ];
    }

    /** @return Parcela[] */
    private function scopedParcelas(array $currentUser): array
    {
        $criteria = [];
        if ($currentUser['role'] === 'tecnico') {
            $criteria['tecnicoId'] = (int)$currentUser['id'];
        } elseif ($currentUser['role'] === 'supervisor' && !empty($currentUser['regionAsignada'])) {
            $criteria['departamentoScope'] = $currentUser['regionAsignada'];
        }
        return $this->repository->findByFilters($criteria);
    }
}
