<?php
namespace App\DTOs\ActividadesDespacho;

class ActividadDTO
{
    public static function fromArray(array $data)
    {
        // Dividir la fecha de creación en fecha y hora
        $timestamp = strtotime($data['fecha_creacion'] ?? 'now');
        
        return [
            'id' => (int)$data['id'],
            'tecnico' => $data['tecnico'] ?? 'No asignado',
            'titulo' => $data['titulo'] ?? '',
            'descripcion' => $data['descripcion'] ?? '',
            'categoria' => $data['categoria'] ?? '',
            'estado' => $data['estado'] ?? 'PENDIENTE',
            'prioridad' => $data['prioridad'] ?? 'MEDIA',
            'fechaInicio' => $data['fecha_inicio'] ?? '',
            'fechaFin' => $data['fecha_fin'] ?? '',
            'creada' => [
                'date' => date('d/m/Y', $timestamp),
                'time' => date('H:i', $timestamp)
            ]
        ];
    }

    public static function fromRequest(array $data)
    {
        // Traducir de CamelCase (Frontend) a SnakeCase (Backend)
        return [
            'tecnico_id' => $data['tecnicoId'] ?? null,
            'titulo' => $data['titulo'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'categoria' => $data['categoria'] ?? null,
            'estado' => $data['estado'] ?? 'PENDIENTE',
            'prioridad' => $data['prioridad'] ?? 'MEDIA',
            'fecha_inicio' => $data['fechaInicio'] ?? null,
            'fecha_fin' => $data['fechaFin'] ?? null
        ];
    }
}
