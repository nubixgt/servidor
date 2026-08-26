<?php
namespace App\DTOs\Clima;

class RegistroDTO
{
    public static function fromArray(array $data)
    {
        return [
            'id' => (int)$data['id'],
            'usuario' => $data['usuario_nombre'] ?? 'Desconocido', // de un JOIN a usuarios_unificados
            'fecha_registro' => $data['fecha_registro'] ?? '',
            'coordenadas' => [
                'lat' => (float)($data['latitud'] ?? 0),
                'lng' => (float)($data['longitud'] ?? 0)
            ],
            'direccion' => $data['direccion'] ?? '',
            'clima' => [
                'temperatura' => (float)($data['temperatura'] ?? 0),
                'humedad' => (float)($data['humedad'] ?? 0),
                'precipitacion' => (float)($data['precipitacion'] ?? 0),
                'viento' => (float)($data['viento'] ?? 0)
            ],
            'categoria' => $data['categoria'] ?? 'condicion',
            'tipo' => $data['categoria'] === 'desastre' ? $data['desastre_natural'] : $data['condicion_climatica'],
            'observaciones' => $data['observaciones'] ?? '',
            'sincronizado' => (bool)$data['sincronizado'],
            'fotografias' => $data['fotografias'] ?? [] // llenado en el Service o Repo
        ];
    }

    public static function fromRequest(array $data)
    {
        return [
            'id_usuario' => $data['idUsuario'] ?? null,
            'fecha_registro' => $data['fechaRegistro'] ?? date('Y-m-d H:i:s'),
            'latitud' => $data['latitud'] ?? null,
            'longitud' => $data['longitud'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'temperatura' => $data['temperatura'] ?? null,
            'humedad' => $data['humedad'] ?? null,
            'precipitacion' => $data['precipitacion'] ?? null,
            'viento' => $data['viento'] ?? null,
            'categoria' => $data['categoria'] ?? 'condicion',
            'condicion_climatica' => $data['condicionClimatica'] ?? null,
            'desastre_natural' => $data['desastreNatural'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
        ];
    }
}
