<?php
namespace App\Services;

use App\Repositories\PadronRepository;

class StatsService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new PadronRepository();
    }

    public function getDashboardStats()
    {
        $stats = $this->repository->getGeneralStats();
        
        // Clean up the top_municipios names
        foreach ($stats['top_municipios'] as &$muni) {
            // Remove leading numbers and spaces (e.g., "1 GUASTATOYA" -> "Guastatoya")
            $cleanName = preg_replace('/^\d+\s+/', '', $muni['raw_name']);
            // Title case it
            $muni['name'] = mb_convert_case($cleanName, MB_CASE_TITLE, "UTF-8");
            $muni['count'] = (int)$muni['count'];
        }
        
        // Adding hardcoded gender stats as in the original python code
        $stats['genero'] = [
            'hombres' => 67243,
            'mujeres' => 68762,
            'otros' => 0
        ];
        
        return $stats;
    }
}
