<?php
namespace App\Services;

use App\Repositories\ParticipanteRepository;
use App\Repositories\CalificacionRepository;
use App\Utils\RondaConfig;

class LeaderboardService
{
    private $participanteRepository;
    private $calificacionRepository;

    public function __construct()
    {
        $this->participanteRepository = new ParticipanteRepository();
        $this->calificacionRepository = new CalificacionRepository();
    }

    /**
     * Por cada participante: el total de cada ronda es la SUMA de los totales de todos los
     * jurados que ya calificaron esa ronda. El Total Final es la suma de las 3 rondas.
     * (Confirmado con el cliente: es suma, no promedio, en ambos niveles.)
     */
    public function calcular(): array
    {
        $participantes = $this->participanteRepository->findAll();

        $sumasPorRonda = [];
        foreach (RondaConfig::RONDAS as $rondaKey => $config) {
            $sumasPorRonda[$rondaKey] = $this->calificacionRepository->sumByParticipante($config['tabla']);
        }

        $resultado = [];
        foreach ($participantes as $p) {
            $rondas = [];
            $totalFinal = 0;

            foreach (RondaConfig::RONDAS as $rondaKey => $config) {
                $datos = $sumasPorRonda[$rondaKey][$p->id] ?? ['suma' => 0, 'jurados' => 0];
                $rondas[$rondaKey] = [
                    'total' => round($datos['suma'], 2),
                    'jurados' => $datos['jurados'],
                ];
                $totalFinal += $datos['suma'];
            }

            $resultado[] = [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria,
                'rondas' => $rondas,
                'total_final' => round($totalFinal, 2),
            ];
        }

        usort($resultado, fn($a, $b) => $b['total_final'] <=> $a['total_final']);

        return $resultado;
    }
}
