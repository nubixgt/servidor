<?php
namespace App\Services;

use App\Repositories\ProjectRepository;
use Exception;

class ProjectService
{
    private ProjectRepository $projectRepository;

    public function __construct()
    {
        $this->projectRepository = new ProjectRepository();
    }

    public function getAllProjects(): array
    {
        return $this->projectRepository->findAll();
    }

    public function createProject(array $data, ?array $fotoFile, ?array $contratosFiles): void
    {
        $pdo = $this->projectRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $newId = $this->projectRepository->create($data);
            
            $baseDir = __DIR__ . "/../../Uploads/Projects/$newId";
            if (!file_exists($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            if ($fotoFile && $fotoFile['error'] === UPLOAD_ERR_OK) {
                $fotoPath = $this->handlePhotoUpload($newId, $fotoFile, $baseDir);
                if ($fotoPath) {
                    $this->projectRepository->updatePhoto($newId, $fotoPath);
                }
            }

            if ($contratosFiles && isset($contratosFiles['name']) && is_array($contratosFiles['name'])) {
                $docs = $this->handleDocumentsUpload($newId, $contratosFiles, $baseDir);
                if (!empty($docs)) {
                    $this->projectRepository->updateDocuments($newId, json_encode($docs));
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateProject(int $id, array $data, ?array $fotoFile, ?array $contratosFiles): void
    {
        $project = $this->projectRepository->findById($id);
        if (!$project) {
            throw new Exception("Proyecto no encontrado", 404);
        }

        // Maintain original values if not provided in the update (using null coalesce like the original controller)
        $data['codigo']             = $data['codigo'] ?? $project['codigo'];
        $data['nombre']             = $data['nombre'] ?? $project['nombre'];
        $data['cliente_id']         = $data['cliente_id'] ?? $project['cliente_id'];
        $data['ubicacion']          = $data['ubicacion'] ?? $project['ubicacion'];
        $data['coordenadas']        = $data['coordenadas'] ?? $project['coordenadas'];
        $data['presupuesto']        = $data['presupuesto'] ?? $project['presupuesto'];
        $data['fecha_inicio']       = $data['fecha_inicio'] ?? $project['fecha_inicio'];
        $data['fecha_fin_estimada'] = $data['fecha_fin_estimada'] !== false ? $data['fecha_fin_estimada'] : $project['fecha_fin_estimada'];
        $data['fecha_fin_real']     = $data['fecha_fin_real'] !== false ? $data['fecha_fin_real'] : $project['fecha_fin_real'];
        $data['estado']             = $data['estado'] ?? $project['estado'];
        $data['numero_contrato']    = $data['numero_contrato'] ?? $project['numero_contrato'];
        $data['descripcion']        = $data['descripcion'] ?? $project['descripcion'];
        $data['contactos']          = $data['contactos'] ?? $project['contactos'];
        $data['gerente_id']         = $data['gerente_id'] ?? $project['gerente_id'];

        $pdo = $this->projectRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $this->projectRepository->update($id, $data);

            $baseDir = __DIR__ . "/../../Uploads/Projects/$id";
            if (!file_exists($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            if ($fotoFile && $fotoFile['error'] === UPLOAD_ERR_OK) {
                if (!empty($project['foto']) && file_exists(__DIR__ . "/../../" . $project['foto'])) {
                    unlink(__DIR__ . "/../../" . $project['foto']);
                }
                
                $fotoPath = $this->handlePhotoUpload($id, $fotoFile, $baseDir);
                if ($fotoPath) {
                    $this->projectRepository->updatePhoto($id, $fotoPath);
                }
            }

            if ($contratosFiles && isset($contratosFiles['error']) && is_array($contratosFiles['error']) && $contratosFiles['error'][0] === UPLOAD_ERR_OK) {
                $docs = $this->handleDocumentsUpload($id, $contratosFiles, $baseDir, true);
                if (!empty($docs)) {
                    $this->projectRepository->updateDocuments($id, json_encode($docs));
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function deleteProject(int $id): void
    {
        $project = $this->projectRepository->findById($id);
        if (!$project) {
            throw new Exception("Proyecto no encontrado", 404);
        }

        $this->projectRepository->delete($id);

        $dirPath = __DIR__ . "/../../Uploads/Projects/$id";
        if (is_dir($dirPath)) {
            $this->deleteDirectory($dirPath);
        }
    }

    private function handlePhotoUpload(int $id, array $fileData, string $baseDir): ?string
    {
        $fotoExt = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $fotoName = "foto_" . time() . ".$fotoExt";
        $fotoPath = "$baseDir/$fotoName";
        
        if (move_uploaded_file($fileData['tmp_name'], $fotoPath)) {
            return "Uploads/Projects/$id/$fotoName";
        }
        
        return null;
    }

    private function handleDocumentsUpload(int $id, array $filesData, string $baseDir, bool $cleanOld = false): array
    {
        $docsDir = "$baseDir/docs";
        
        if ($cleanOld) {
            if (file_exists($docsDir)) {
                $files = array_diff(scandir($docsDir), array('.','..'));
                foreach ($files as $file) {
                    unlink("$docsDir/$file");
                }
            } else {
                mkdir($docsDir, 0777, true);
            }
        } elseif (!file_exists($docsDir)) {
            mkdir($docsDir, 0777, true);
        }

        $contratos_archivos = [];
        $totalFiles = count($filesData['name']);
        for ($i = 0; $i < $totalFiles; $i++) {
            if ($filesData['error'][$i] === UPLOAD_ERR_OK) {
                $docName = basename($filesData['name'][$i]);
                $safeDocName = preg_replace("/[^a-zA-Z0-9.-]/", "_", $docName);
                $docPath = "$docsDir/$safeDocName";
                
                if (move_uploaded_file($filesData['tmp_name'][$i], $docPath)) {
                    $contratos_archivos[] = "Uploads/Projects/$id/docs/$safeDocName";
                }
            }
        }

        return $contratos_archivos;
    }

    private function deleteDirectory(string $dir): bool 
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        
        return rmdir($dir);
    }
}
