<?php
namespace App\Services;

use App\Repositories\HorarioRepository;

/**
 * Horarios de estudio del usuario autenticado en la web (JWT). Misma tabla
 * y mismas reglas que AsistenteService::guardarHorario (que identifica al
 * usuario por teléfono para el bot de WhatsApp).
 */
class HorarioService
{
    private const DIAS_VALIDOS = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

    private $horarioRepository;

    public function __construct()
    {
        $this->horarioRepository = new HorarioRepository();
    }

    public function listar(int $usuarioId): array
    {
        return $this->horarioRepository->obtenerPorUsuario($usuarioId);
    }

    public function guardar(int $usuarioId, int $cursoId, string $dias, string $hora, int $duracionMinutos): void
    {
        $letras = array_unique(array_filter(array_map(
            fn($d) => strtoupper(trim($d)),
            preg_split('/[,\s]+/', $dias)
        )));
        $diasNormalizados = implode(',', array_values(array_intersect($letras, self::DIAS_VALIDOS)));

        if ($diasNormalizados === '') {
            throw new \Exception('Elige al menos un día de la semana.');
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) {
            throw new \Exception('La hora debe tener formato HH:MM (24 horas).');
        }
        if ($duracionMinutos < 5 || $duracionMinutos > 180) {
            throw new \Exception('La duración debe estar entre 5 y 180 minutos.');
        }
        if (!in_array($cursoId, array_column($this->horarioRepository->cursosDisponibles(), 'id'), false)) {
            throw new \Exception('El curso no existe.');
        }

        $this->horarioRepository->guardar($usuarioId, $cursoId, $diasNormalizados, $hora . ':00', $duracionMinutos);
    }

    public function actualizarActivo(int $usuarioId, int $cursoId, bool $activo): bool
    {
        return $this->horarioRepository->actualizarActivo($usuarioId, $cursoId, $activo);
    }
}
