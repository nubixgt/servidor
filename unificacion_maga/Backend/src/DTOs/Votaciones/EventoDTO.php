<?php
namespace App\DTOs\Votaciones;

class EventoDTO
{
    public static function fromArray(array $data)
    {
        return [
            'evento' => (int)$data['id'],
            'sesion' => (int)($data['sesion'] ?? 0),
            'titulo' => $data['titulo'] ?? '',
            'descripcion' => $data['descripcion'] ?? '',
            'fecha' => date('d/m/Y', strtotime($data['fecha'])),
            'favor' => (int)($data['favor'] ?? 0),
            'contra' => (int)($data['contra'] ?? 0),
            'ausencias' => (int)($data['ausencias'] ?? 0),
            'resultado' => ((int)($data['favor'] ?? 0) > (int)($data['contra'] ?? 0)) ? 'APROBADO' : 'RECHAZADO',
            'acta_pdf' => $data['acta_pdf'] ?? null
        ];
    }

    public static function fromRequest(array $data)
    {
        return [
            'titulo' => $data['titulo'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'sesion' => $data['sesion'] ?? 0,
            'fecha' => $data['fecha'] ?? date('Y-m-d'),
            'acta_pdf' => $data['acta_pdf'] ?? null
        ];
    }
}
