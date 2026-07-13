<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\PadronService;
use Shuchkin\SimpleXLSX;

class MatchController extends Controller
{
    #[Route('/match', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function matchText()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $aldeas = $data['aldeas'] ?? [];
        $municipio = $data['municipio'] ?? null;
        
        if (empty($aldeas) || !is_array($aldeas)) {
            $this->json(['error' => 'Lista de aldeas vacía o inválida'], 400);
        }

        $service = new PadronService();
        try {
            $result = $service->matchAldeas($aldeas, $municipio);
            $this->json($result);
        } catch (\Exception $e) {
            $this->json(['error' => 'Error al procesar el texto', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/match-file', 'POST')]
    #[Authorize(['admin', 'user'])]
    public function matchFile()
    {
        $municipio = $_POST['municipio'] ?? null;
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No se subió ningún archivo válido'], 400);
        }

        $tmpFile = $_FILES['file']['tmp_name'];
        $aldeas = [];

        try {
            // Requiring our lightweight XLSX parser
            require_once __DIR__ . '/../Utils/SimpleXLSX.php';
            
            if ($xlsx = SimpleXLSX::parse($tmpFile)) {
                // Read the first sheet
                $rows = $xlsx->rows();
                
                // Assume the first column of the Excel has the aldeas.
                // We'll skip the first row (header) if it looks like a header, 
                // but to be safe we'll just extract everything and the match logic will ignore 'ALDEA' if it doesn't match well.
                foreach ($rows as $index => $row) {
                    if ($index === 0) continue; // skip header
                    if (!empty($row[0])) {
                        $aldeas[] = trim($row[0]);
                    }
                }
            } else {
                $this->json(['error' => 'El archivo no es un Excel válido o está corrupto: ' . SimpleXLSX::parseError()], 400);
            }
            
            if (empty($aldeas)) {
                $this->json(['error' => 'No se encontraron aldeas en la primera columna del archivo.'], 400);
            }

            $service = new PadronService();
            $result = $service->matchAldeas($aldeas, $municipio);
            $this->json($result);

        } catch (\Exception $e) {
            $this->json(['error' => 'Error al procesar el archivo Excel', 'details' => $e->getMessage()], 500);
        }
    }
}
