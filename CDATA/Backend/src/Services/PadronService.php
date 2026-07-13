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
                
                $prefixes = ['ALDEA ', 'CASERIO ', 'COMUNIDAD ', 'BARRIO ', 'CANTON ', 'COLONIA ', 'FINCA ', 'PARAJE ', 'SECTOR ', 'LOTIFICACION ', 'ZONA ', 'CASCO '];
                $cleanInput = trim(str_replace($prefixes, '', $input));
                $cleanDb = trim(str_replace($prefixes, '', $dbAldea));

                // 1. Exact match
                if ($dbAldea === $input || $cleanDb === $cleanInput) {
                    $percent = 100;
                } 
                // 2. Input is fully contained in DB (e.g. cleanInput "SAN RAFAEL EL JUTE" in cleanDb "EL JUTE O SAN RAFAEL EL JUTE")
                elseif (strlen($cleanInput) >= 4 && strpos($cleanDb, $cleanInput) !== false) {
                    $percent = 95;
                }
                // 3. DB is fully contained in input (e.g. cleanDb "UPAYON" in cleanInput "SAN ANTONIO, EL UPAYON")
                elseif (strlen($cleanDb) >= 4 && strpos($cleanInput, $cleanDb) !== false) {
                    // Score based on how much of the input is covered by the DB string
                    $coverage = (strlen($cleanDb) / strlen($cleanInput)) * 100;
                    $percent = 80 + ($coverage * 0.15); // Gives a score between 80 and 95
                } 
                // 4. Standard fuzzy match
                else {
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

    public function matchDpis($inputList)
    {
        $db = \App\Utils\Database::getInstance()->getConnection();
        
        $sql = "SELECT dpi, nombre_ciudadano, municipio, aldea FROM padron_electoral WHERE dpi = :dpi";
        $stmt = $db->prepare($sql);
        
        $results = [];
        $encontrados = 0;
        
        foreach ($inputList as $dpi) {
            $dpiStr = trim((string)$dpi);
            if (empty($dpiStr)) continue;
            
            // Format DPI if it's stored in a specific way, otherwise use as is
            $stmt->execute([':dpi' => $dpiStr]);
            $record = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($record) {
                $results[] = [
                    'dpi_buscado' => $dpiStr,
                    'encontrado' => true,
                    'nombre' => $record['nombre_ciudadano'],
                    'municipio' => $record['municipio'],
                    'aldea' => $record['aldea']
                ];
                $encontrados++;
            } else {
                $results[] = [
                    'dpi_buscado' => $dpiStr,
                    'encontrado' => false,
                    'nombre' => 'NO REGISTRADO',
                    'municipio' => '-',
                    'aldea' => '-'
                ];
            }
        }
        
        return [
            'results' => $results,
            'total_encontrados' => $encontrados,
            'total_analizados' => count($inputList)
        ];
    }
}
