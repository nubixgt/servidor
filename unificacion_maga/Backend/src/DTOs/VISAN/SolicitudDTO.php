<?php
namespace App\DTOs\VISAN;

class SolicitudDTO
{
    public static function map($data)
    {
        return [
            'id' => $data['id'],
            'id_solicitud' => $data['id_solicitud'],
            'fecha' => date('d M Y', strtotime($data['fecha'])),
            'beneficiario' => $data['beneficiario_nombre'] ?? 'N/D',
            'dpi' => $data['beneficiario_dpi'] ?? 'N/D',
            'departamento' => $data['departamento'],
            'municipio' => $data['municipio'],
            'comunidad' => $data['comunidad'],
            'programa' => $data['programa'],
            'estado' => $data['estado']
        ];
    }

    public static function mapCollection($collection)
    {
        return array_map([self::class, 'map'], $collection);
    }
}
