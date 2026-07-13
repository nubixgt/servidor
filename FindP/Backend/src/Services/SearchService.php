<?php
namespace App\Services;

use App\Utils\PadronDatabase;

class SearchService
{
    private $db;

    public function __construct()
    {
        $this->db = PadronDatabase::getInstance()->getConnection();
    }

    public function search($query)
    {
        $query = trim($query);
        if (empty($query)) {
            return ['results' => [], 'count' => 0, 'has_more' => false, 'is_dpi' => false];
        }

        $clean_query = preg_replace('/\s+/', '', $query);
        $is_dpi = ctype_digit($clean_query);

        $results = [];

        try {
            if ($is_dpi) {
                // Búsqueda por DPI (búsqueda exacta)
                $stmt = $this->db->prepare("SELECT departamento, municipio, aldea, nombre, dpi, edad FROM padron WHERE dpi_clean = ? LIMIT 100");
                $stmt->execute([$clean_query]);
                $results = $stmt->fetchAll();
            } else {
                // Búsqueda por Nombre Completo (FTS5)
                // Separar la cadena en palabras
                $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
                $formatted_words = [];
                
                foreach ($words as $w) {
                    $clean_w = preg_replace('/[^\w]/u', '', $w);
                    if ($clean_w !== '') {
                        $formatted_words[] = '"' . $clean_w . '"*';
                    }
                }
                
                if (!empty($formatted_words)) {
                    $fts_query = implode(' AND ', $formatted_words);
                    
                    $sql = "
                        SELECT p.departamento, p.municipio, p.aldea, p.nombre, p.dpi, p.edad 
                        FROM padron_fts f 
                        JOIN padron p ON p.id = f.rowid 
                        WHERE f.nombre MATCH ? 
                        LIMIT 100
                    ";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$fts_query]);
                    $results = $stmt->fetchAll();
                }
            }
        } catch (\PDOException $e) {
            throw new \Exception("Error al consultar la base de datos: " . $e->getMessage());
        }

        return [
            'results' => $results,
            'count' => count($results),
            'has_more' => count($results) >= 100,
            'is_dpi' => $is_dpi
        ];
    }
}
