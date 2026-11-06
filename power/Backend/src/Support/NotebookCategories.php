<?php
namespace App\Support;

/**
 * Coach-notebook categories, in the coach's vocabulary (from PaginaLocal's
 * services/ai/notebook.py CATEGORIES_ES). Open list: an unknown category is
 * stored as-is but the UI groups it under "otro".
 */
final class NotebookCategories
{
    public const MAP = [
        'medicacion' => 'Medicación y suplementos',
        'sueno' => 'Sueño y horarios',
        'logistica' => 'Logística y disponibilidad',
        'preferencia' => 'Preferencias de entrenamiento',
        'molestia' => 'Molestias y antecedentes',
        'nutricion' => 'Nutrición',
        'vida' => 'Trabajo, viajes y vida',
        'otro' => 'Otros',
    ];

    public const STATUSES = ['vigente', 'propuesta', 'retirada'];

    public static function normalize(?string $category): string
    {
        $c = trim((string) $category);
        return array_key_exists($c, self::MAP) ? $c : 'otro';
    }
}
