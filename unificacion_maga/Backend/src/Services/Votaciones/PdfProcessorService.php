<?php
namespace App\Services\Votaciones;

use Exception;

// Assuming Smalot PdfParser will be mapped in autoload
use Smalot\PdfParser\Parser;

class PdfProcessorService
{
    private $parser;

    public function __construct()
    {
        // Try to initialize parser, it requires Smalot library
        if (class_exists('Smalot\PdfParser\Parser')) {
            $this->parser = new Parser();
        }
    }

    public function processPdf($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("El archivo PDF no existe.");
        }

        if (!$this->parser) {
            throw new Exception("La librería de parsing PDF no está disponible.");
        }

        $pdf = $this->parser->parseFile($filePath);
        $text = $pdf->getText();

        if (empty(trim($text))) {
            throw new Exception("No se pudo extraer texto del PDF. Podría ser un documento escaneado.");
        }

        return $this->extractData($text);
    }

    private function extractData($text)
    {
        $datos = [
            'evento' => [],
            'votos' => []
        ];

        // Normalizar saltos de línea
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // 1. Extraer Número de Evento
        if (preg_match('/EVENTO\s+DE\s+VOTACI[ÓO]N\s*#?\s*(\d+)/ui', $text, $matches)) {
            $datos['evento']['numero'] = $matches[1];
        }

        // 2. Extraer Sesión
        if (preg_match('/SESI[ÓO]N\s+No\.?\s*(\d+)/ui', $text, $matches)) {
            $datos['evento']['sesion'] = $matches[1];
        }

        // 3. Extraer Fecha y Hora
        if (preg_match('/(\d{2})[-/](\d{2})[-/](\d{4})\s+(\d{2}):(\d{2})(?::(\d{2}))?/', $text, $matches)) {
            // Convertir de DD-MM-YYYY a YYYY-MM-DD
            $hora = $matches[4] . ':' . $matches[5] . (isset($matches[6]) ? ':' . $matches[6] : ':00');
            $datos['evento']['fecha_hora'] = $matches[3] . '-' . $matches[2] . '-' . $matches[1] . ' ' . $hora;
        } else {
            $datos['evento']['fecha_hora'] = date('Y-m-d H:i:s');
        }

        // 4. Extraer Título (Lógica similar a Python)
        $titulo = 'Sin título';
        // Patrón que busca APROBACION/ELECCION seguido de cualquier cosa hasta "SESION No" o "Fecha" o fin
        if (preg_match('/(APROBACI[ÓO]N|ELECCI[ÓO]N|DESIGNACI[ÓO]N)\s+DE\s+(.+?)(?=\s*SESI[ÓO]N\s+No\.|\s*Fecha\s+y\s+Hora|$)/uis', $text, $matches)) {
            $titulo = trim(preg_replace('/\s+/', ' ', $matches[0]));
        } else if (preg_match('/EVENTO\s+DE\s+VOTACI[ÓO]N[^\n]*\n+(.*?)(?=\n*No\.\s+NOMBRE)/uis', $text, $matches)) {
            // Fallback: capturar lo que está entre EVENTO DE VOTACION y la tabla
            $contenido = $matches[1];
            if (preg_match('/(APROBACI[ÓO]N|ELECCI[ÓO]N|DESIGNACI[ÓO]N).*/uis', $contenido, $mTipo)) {
                $titulo = trim(preg_replace('/\s+/', ' ', $mTipo[0]));
            }
        }
        
        // Limpiar título
        $titulo = preg_replace('/\d{2}[-\/]\d{2}[-\/]\d{4}\s+\d{2}:\d{2}(:\d{2})?/', '', $titulo);
        $titulo = preg_replace('/Fecha\s+y\s+Hora\s*:/i', '', $titulo);
        $titulo = preg_replace('/SESI[ÓO]N\s+No\.?\s*\d+\s*$/i', '', $titulo);
        $datos['evento']['titulo'] = trim($titulo);

        // 5. Extraer Votos (Tabla)
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Patrón para una fila de voto: Numero Nombre(s) Bloque Voto
            // Ej: "23 JUAN PÉREZ UNE A FAVOR"
            if (preg_match('/^(\d+)\s+([A-ZÁÉÍÓÚÑa-záéíóúñ\'\.\s]+?)\s+([A-ZÁÉÍÓÚÑ0-9\s]+?)\s+(A\s+FAVOR|EN\s+CONTRA|AUSENTE|LICENCIA|ABSTENCION|ABSTENCIÓN)$/ui', $line, $matches)) {
                
                $numero = (int)$matches[1];
                $nombre = trim($matches[2]);
                $bloque = trim($matches[3]);
                $voto = mb_strtoupper(trim($matches[4]), 'UTF-8');
                
                if ($voto === 'ABSTENCIÓN') {
                    $voto = 'ABSTENCION';
                }

                // Limpiar bloque de espacios extra
                $bloque = trim(preg_replace('/\s+/', ' ', $bloque));
                if (empty($bloque)) $bloque = 'INDEPENDIENTE';

                // Validar que el nombre tenga al menos dos palabras (para evitar falsos positivos)
                $palabras = array_filter(explode(' ', $nombre), function($p) { return mb_strlen($p) >= 2; });
                if (count($palabras) >= 2) {
                    $datos['votos'][] = [
                        'numero' => $numero,
                        'nombre' => $nombre,
                        'bloque' => $bloque,
                        'voto'   => $voto
                    ];
                }
            }
        }

        return $datos;
    }
}
