<?php
namespace App\Services;

use App\Repositories\CalificacionRepository;
use App\Utils\RondaConfig;

class CalificacionService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new CalificacionRepository();
    }

    /**
     * Guarda (o actualiza) la calificación de UN jurado para UN participante en UNA ronda.
     * El total es el promedio de los 3 rubros que metió ese jurado.
     */
    public function guardar(string $rondaKey, int $participanteId, int $juradoId, array $rubrosInput): array
    {
        if (empty($participanteId)) {
            throw new \Exception('Falta el participante');
        }

        $config = RondaConfig::get($rondaKey);
        $rubricKeys = $config['rubros'];

        $rubros = [];
        foreach ($rubricKeys as $key) {
            $valor = (int) ($rubrosInput[$key] ?? 0);
            if ($valor < 1 || $valor > 10) {
                throw new \Exception("El rubro '$key' debe estar entre 1 y 10");
            }
            $rubros[$key] = $valor;
        }

        $total = round(array_sum($rubros) / count($rubros), 2);

        $this->repository->upsert($config['tabla'], $rubricKeys, $participanteId, $juradoId, $rubros, $total);

        return array_merge($rubros, ['total' => $total]);
    }

    /**
     * Las calificaciones que YA metió este jurado en esta ronda, indexadas por participante_id.
     * Sirve para rellenar la hoja de calificación cuando el jurado vuelve a entrar.
     */
    public function misCalificaciones(string $rondaKey, int $juradoId): array
    {
        $config = RondaConfig::get($rondaKey);
        $rows = $this->repository->findByJurado($config['tabla'], $juradoId);

        $result = [];
        foreach ($rows as $row) {
            $entry = ['total' => (float) $row['total']];
            foreach ($config['rubros'] as $key) {
                $entry[$key] = (int) $row[$key];
            }
            $result[(int) $row['participante_id']] = $entry;
        }
        return $result;
    }
}
