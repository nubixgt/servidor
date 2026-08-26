<?php
namespace App\DTOs\Clima;

class AlertaDTO
{
    public static function fromArray(array $data)
    {
        $fechaEmision = strtotime($data['fecha_emision'] ?? 'now');
        $fechaVigencia = strtotime($data['fecha_vigencia'] ?? 'now');
        
        return [
            'id' => (int)$data['id'],
            'titulo' => $data['titulo'] ?? '',
            'descripcion_corta' => $data['descripcion_corta'] ?? '',
            'descripcion_detallada' => $data['descripcion_detallada'] ?? '',
            'tipo_alerta' => $data['tipo_alerta'] ?? '',
            'nivel_severidad' => $data['nivel_severidad'] ?? 'MEDIA',
            'region' => $data['region'] ?? '',
            'icono' => $data['icono'] ?? '',
            'fecha_emision' => date('Y-m-d H:i', $fechaEmision),
            'fecha_vigencia' => date('Y-m-d H:i', $fechaVigencia),
            'estado' => $data['estado'] ?? 'Activa',
            'creador' => $data['creador'] ?? 'Desconocido', // Obtenido con JOIN a usuarios
            'fecha_creacion' => $data['fecha_creacion'] ?? ''
        ];
    }

    public static function fromRequest(array $data)
    {
        return [
            'titulo' => $data['titulo'] ?? null,
            'descripcion_corta' => $data['descripcionCorta'] ?? null,
            'descripcion_detallada' => $data['descripcionDetallada'] ?? null,
            'tipo_alerta' => $data['tipoAlerta'] ?? null,
            'nivel_severidad' => $data['nivelSeveridad'] ?? 'MEDIA',
            'region' => $data['region'] ?? null,
            'icono' => $data['icono'] ?? null,
            'fecha_emision' => $data['fechaEmision'] ?? null,
            'fecha_vigencia' => $data['fechaVigencia'] ?? null,
            'estado' => $data['estado'] ?? 'Activa',
            'id_usuario_creador' => $data['idUsuarioCreador'] ?? null
        ];
    }
}
