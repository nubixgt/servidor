<?php
namespace App\Services;

use App\Repositories\PadronRepository;

class PadronService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new PadronRepository();
    }

    public function queryPadron($query, $municipio, $page, $limit)
    {
        return $this->repository->search($query, $municipio, $page, $limit);
    }

    public function matchAldeas($inputList, $municipio = null)
    {
        // 1. Get all unique aldeas and their exact population from DB
        $db = \App\Utils\Database::getInstance()->getConnection();
        $whereSql = "";
        $params = [];
        if (!empty($municipio)) {
            $whereSql = "WHERE municipio = :municipio AND aldea != ''";
            $params[':municipio'] = $municipio;
        } else {
            $whereSql = "WHERE aldea != ''";
        }
        
        $sql = "SELECT aldea, COUNT(*) as total FROM padron_electoral {$whereSql} GROUP BY aldea";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $dbAldeas = $stmt->fetchAll(\PDO::FETCH_ASSOC); // array of ['aldea' => '...', 'total' => 123]
        
        $results = [];
        $totalFound = 0;
        
        // 2. Try to match each input aldea
        foreach ($inputList as $input) {
            $input = trim(strtoupper($input));
            if (empty($input)) continue;
            
            $bestMatch = null;
            $bestScore = 0;
            $bestTotal = 0;
            
            foreach ($dbAldeas as $row) {
                $dbAldea = trim(strtoupper($row['aldea']));
                
                // Exact match first
                if ($dbAldea === $input) {
                    $bestMatch = $dbAldea;
                    $bestScore = 100;
                    $bestTotal = $row['total'];
                    break;
                }
                
                // If input is exactly contained within dbAldea (e.g., input "SAN JUAN" in "ALDEA SAN JUAN")
                if (strpos($dbAldea, $input) !== false) {
                    $percent = 90; // High score for substring match
                } else {
                    // Remove common prefixes for a fairer comparison
                    $prefixes = ['ALDEA ', 'CASERIO ', 'COMUNIDAD ', 'BARRIO ', 'CANTON ', 'COLONIA ', 'FINCA ', 'PARAJE ', 'SECTOR ', 'LOTIFICACION '];
                    $cleanInput = str_replace($prefixes, '', $input);
                    $cleanDb = str_replace($prefixes, '', $dbAldea);
                    
                    similar_text($cleanInput, $cleanDb, $percent);
                }

                if ($percent > $bestScore) {
                    $bestScore = $percent;
                    $bestMatch = $dbAldea;
                    $bestTotal = $row['total'];
                }
            }
            
            // If the best score is acceptable (e.g. > 60%), we consider it a match
            if ($bestScore > 65) {
                $results[] = [
                    'original' => $input,
                    'mapped' => $bestMatch,
                    'match_score' => round($bestScore, 1),
                    'count' => $bestTotal
                ];
                $totalFound += $bestTotal;
            } else {
                $results[] = [
                    'original' => $input,
                    'mapped' => 'NO ENCONTRADA',
                    'match_score' => 0,
                    'count' => 0
                ];
            }
        }
        
        return [
            'results' => $results,
            'total_personas' => $totalFound,
            'total_aldeas_analizadas' => count($inputList)
        ];
    }
}
