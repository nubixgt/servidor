<?php
namespace App\Services\Votaciones;

use App\Repositories\Votaciones\EventoRepository;
use App\Repositories\Votaciones\CongresistaRepository;
use App\Repositories\Votaciones\BloqueRepository;
use App\DTOs\Votaciones\EventoDTO;

class VotacionService
{
    private $eventoRepo;
    private $congresistaRepo;
    private $bloqueRepo;

    public function __construct()
    {
        $this->eventoRepo = new EventoRepository();
        $this->congresistaRepo = new CongresistaRepository();
        $this->bloqueRepo = new BloqueRepository();
    }

    // --- EVENTOS ---
    public function getEventos($filters = [])
    {
        $data = $this->eventoRepo->getAll($filters);
        return array_map([EventoDTO::class, 'fromArray'], $data);
    }

    public function getSummary()
    {
        return $this->eventoRepo->getSummary();
    }

    public function getEstadisticas($filters = [])
    {
        return $this->eventoRepo->getEstadisticas($filters);
    }

    public function createEvento($data)
    {
        return $this->eventoRepo->create($data);
    }

    public function updateEvento($id, $data)
    {
        return $this->eventoRepo->update($id, $data);
    }

    public function deleteEvento($id)
    {
        return $this->eventoRepo->delete($id);
    }

    // --- CONGRESISTAS ---
    public function getCongresistas($filters = [])
    {
        return $this->congresistaRepo->getAll($filters);
    }

    public function getCongresistasStats()
    {
        return $this->congresistaRepo->getStats();
    }

    public function createCongresista($data)
    {
        return $this->congresistaRepo->create($data);
    }

    public function updateCongresista($id, $data)
    {
        return $this->congresistaRepo->update($id, $data);
    }

    public function deleteCongresista($id)
    {
        return $this->congresistaRepo->delete($id);
    }

    // --- BLOQUES ---
    public function getBloques()
    {
        return $this->bloqueRepo->getAll();
    }

    public function createBloque($data)
    {
        return $this->bloqueRepo->create($data);
    }

    public function updateBloque($id, $data)
    {
        return $this->bloqueRepo->update($id, $data);
    }

    public function deleteBloque($id)
    {
        return $this->bloqueRepo->delete($id);
    }

    public function processUpload($files)
    {
        $processor = new PdfProcessorService();
        $results = [];

        // Asegurar que exista carpeta temporal
        $tempDir = __DIR__ . '/../../../../uploads/votaciones';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Si es un solo archivo o múltiples (el input 'files[]' a veces llega diferente según cómo se envía)
        // Normalizamos el array de archivos
        $archivosList = [];
        if (isset($files['name']) && is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                $archivosList[] = [
                    'name' => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i]
                ];
            }
        } else if (isset($files['name'])) {
            $archivosList[] = $files;
        }

        foreach ($archivosList as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $results[] = ['archivo' => $file['name'], 'status' => 'error', 'message' => 'Error al subir el archivo'];
                continue;
            }

            $rutaDestino = $tempDir . '/' . uniqid() . '_' . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $rutaDestino)) {
                try {
                    $datos = $processor->processPdf($rutaDestino);
                    
                    // 1. Guardar o actualizar Evento
                    $eventoData = [
                        'titulo' => $datos['evento']['titulo'] ?? 'Sin título',
                        'descripcion' => null,
                        'sesion' => $datos['evento']['sesion'] ?? 0,
                        'fecha' => $datos['evento']['fecha_hora'],
                        'acta_pdf' => $file['name'],
                        'numero_evento' => $datos['evento']['numero'] ?? null
                    ];
                    
                    $eventoId = $this->eventoRepo->createOrUpdateFromPdf($eventoData);

                    // 2. Procesar Votos
                    if (!empty($datos['votos'])) {
                        $this->eventoRepo->clearVotos($eventoId);
                        
                        foreach ($datos['votos'] as $votoData) {
                            $congresistaId = $this->congresistaRepo->findOrCreate($votoData['nombre']);
                            $bloqueId = $this->bloqueRepo->findOrCreate($votoData['bloque']);
                            
                            $this->eventoRepo->addVoto([
                                'evento_id' => $eventoId,
                                'congresista_id' => $congresistaId,
                                'bloque_id' => $bloqueId,
                                'voto' => $votoData['voto'],
                                'numero_orden' => $votoData['numero']
                            ]);
                        }
                        
                        // 3. Calcular Resumen
                        $this->eventoRepo->calcularResumen($eventoId);
                    }

                    $results[] = ['archivo' => $file['name'], 'status' => 'success', 'votos' => count($datos['votos'])];
                } catch (\Exception $e) {
                    $results[] = ['archivo' => $file['name'], 'status' => 'error', 'message' => $e->getMessage()];
                }
            }
        }

        return $results;
    }
}
