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

    public function createProject(
        array $data,
        ?array $fotoFile = null,
        ?array $contratosFiles = null,
        ?array $fotoContratoFile = null,
        ?array $excelPresupuestoFile = null,
        ?array $especificacionesFile = null,
        ?array $conveniosFiles = null
    ): void {
        $pdo = $this->projectRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $newId = $this->projectRepository->create($data);
            
            $baseDir = __DIR__ . "/../../Uploads/Projects/$newId";
            if (!file_exists($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            // Foto de Portada
            if ($fotoFile && ($fotoFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $fotoPath = $this->handleSingleFileUpload($newId, $fotoFile, $baseDir, 'foto');
                if ($fotoPath) {
                    $this->projectRepository->updatePhoto($newId, $fotoPath);
                }
            }

            // Foto de Contrato
            if ($fotoContratoFile && ($fotoContratoFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $fotoContratoPath = $this->handleSingleFileUpload($newId, $fotoContratoFile, $baseDir, 'foto_contrato');
                if ($fotoContratoPath) {
                    $this->projectRepository->updateFotoContrato($newId, $fotoContratoPath);
                }
            }

            // Excel Presupuesto
            if ($excelPresupuestoFile && ($excelPresupuestoFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $excelPath = $this->handleSingleFileUpload($newId, $excelPresupuestoFile, $baseDir, 'presupuesto');
                if ($excelPath) {
                    $this->projectRepository->updateExcelPresupuesto($newId, $excelPath);
                }
            }

            // Especificaciones Técnicas
            if ($especificacionesFile && ($especificacionesFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $specPath = $this->handleSingleFileUpload($newId, $especificacionesFile, $baseDir, 'especificaciones');
                if ($specPath) {
                    $this->projectRepository->updateEspecificacionesTecnicas($newId, $specPath);
                }
            }

            // Convenios (Múltiples archivos)
            if ($conveniosFiles && isset($conveniosFiles['name']) && is_array($conveniosFiles['name'])) {
                $convenios = $this->handleMultipleFilesUpload($newId, $conveniosFiles, 'convenios', $baseDir);
                if (!empty($convenios)) {
                    $this->projectRepository->updateConvenios($newId, json_encode($convenios));
                }
            }

            // Archivos de Contrato legado / general
            if ($contratosFiles && isset($contratosFiles['name']) && is_array($contratosFiles['name'])) {
                $docs = $this->handleMultipleFilesUpload($newId, $contratosFiles, 'docs', $baseDir);
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

    public function updateProject(
        int $id,
        array $data,
        ?array $fotoFile = null,
        ?array $contratosFiles = null,
        ?array $fotoContratoFile = null,
        ?array $excelPresupuestoFile = null,
        ?array $especificacionesFile = null,
        ?array $conveniosFiles = null
    ): void {
        $project = $this->projectRepository->findById($id);
        if (!$project) {
            throw new Exception("Proyecto no encontrado", 404);
        }

        // Merge with existing values
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
        $data['snip']               = array_key_exists('snip', $data) ? $data['snip'] : ($project['snip'] ?? null);
        $data['nog']                = array_key_exists('nog', $data) ? $data['nog'] : ($project['nog'] ?? null);
        $data['monto_cocode']       = array_key_exists('monto_cocode', $data) ? $data['monto_cocode'] : ($project['monto_cocode'] ?? 0);
        $data['monto_muni']         = array_key_exists('monto_muni', $data) ? $data['monto_muni'] : ($project['monto_muni'] ?? 0);
        $data['monto_comunidad']    = array_key_exists('monto_comunidad', $data) ? $data['monto_comunidad'] : ($project['monto_comunidad'] ?? 0);
        $data['tipo_inversion']     = array_key_exists('tipo_inversion', $data) ? $data['tipo_inversion'] : ($project['tipo_inversion'] ?? null);

        $pdo = $this->projectRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $this->projectRepository->update($id, $data);

            $baseDir = __DIR__ . "/../../Uploads/Projects/$id";
            if (!file_exists($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            // Foto Portada
            if ($fotoFile && ($fotoFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                if (!empty($project['foto']) && file_exists(__DIR__ . "/../../" . $project['foto'])) {
                    @unlink(__DIR__ . "/../../" . $project['foto']);
                }
                $fotoPath = $this->handleSingleFileUpload($id, $fotoFile, $baseDir, 'foto');
                if ($fotoPath) {
                    $this->projectRepository->updatePhoto($id, $fotoPath);
                }
            }

            // Foto Contrato
            if ($fotoContratoFile && ($fotoContratoFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                if (!empty($project['foto_contrato']) && file_exists(__DIR__ . "/../../" . $project['foto_contrato'])) {
                    @unlink(__DIR__ . "/../../" . $project['foto_contrato']);
                }
                $fotoContratoPath = $this->handleSingleFileUpload($id, $fotoContratoFile, $baseDir, 'foto_contrato');
                if ($fotoContratoPath) {
                    $this->projectRepository->updateFotoContrato($id, $fotoContratoPath);
                }
            }

            // Excel Presupuesto
            if ($excelPresupuestoFile && ($excelPresupuestoFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                if (!empty($project['excel_presupuesto']) && file_exists(__DIR__ . "/../../" . $project['excel_presupuesto'])) {
                    @unlink(__DIR__ . "/../../" . $project['excel_presupuesto']);
                }
                $excelPath = $this->handleSingleFileUpload($id, $excelPresupuestoFile, $baseDir, 'presupuesto');
                if ($excelPath) {
                    $this->projectRepository->updateExcelPresupuesto($id, $excelPath);
                }
            }

            // Especificaciones Técnicas
            if ($especificacionesFile && ($especificacionesFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                if (!empty($project['especificaciones_tecnicas']) && file_exists(__DIR__ . "/../../" . $project['especificaciones_tecnicas'])) {
                    @unlink(__DIR__ . "/../../" . $project['especificaciones_tecnicas']);
                }
                $specPath = $this->handleSingleFileUpload($id, $especificacionesFile, $baseDir, 'especificaciones');
                if ($specPath) {
                    $this->projectRepository->updateEspecificacionesTecnicas($id, $specPath);
                }
            }

            // Convenios
            if ($conveniosFiles && isset($conveniosFiles['error']) && is_array($conveniosFiles['error']) && ($conveniosFiles['error'][0] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $convenios = $this->handleMultipleFilesUpload($id, $conveniosFiles, 'convenios', $baseDir, true);
                if (!empty($convenios)) {
                    $this->projectRepository->updateConvenios($id, json_encode($convenios));
                }
            }

            // Contratos generales
            if ($contratosFiles && isset($contratosFiles['error']) && is_array($contratosFiles['error']) && ($contratosFiles['error'][0] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $docs = $this->handleMultipleFilesUpload($id, $contratosFiles, 'docs', $baseDir, true);
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

    private function handleSingleFileUpload(int $id, array $fileData, string $baseDir, string $prefix): ?string
    {
        $ext = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $fileName = "{$prefix}_" . time() . "_" . uniqid() . ".$ext";
        $destPath = "$baseDir/$fileName";
        
        if (move_uploaded_file($fileData['tmp_name'], $destPath)) {
            return "Uploads/Projects/$id/$fileName";
        }
        
        return null;
    }

    private function handleMultipleFilesUpload(int $id, array $filesData, string $subDir, string $baseDir, bool $cleanOld = false): array
    {
        $targetDir = "$baseDir/$subDir";
        
        if ($cleanOld) {
            if (file_exists($targetDir)) {
                $files = array_diff(scandir($targetDir), array('.','..'));
                foreach ($files as $file) {
                    @unlink("$targetDir/$file");
                }
            } else {
                mkdir($targetDir, 0777, true);
            }
        } elseif (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $uploadedPaths = [];
        $totalFiles = count($filesData['name'] ?? []);
        for ($i = 0; $i < $totalFiles; $i++) {
            if (($filesData['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $docName = basename($filesData['name'][$i]);
                $safeDocName = time() . "_" . preg_replace("/[^a-zA-Z0-9.-]/", "_", $docName);
                $docPath = "$targetDir/$safeDocName";
                
                if (move_uploaded_file($filesData['tmp_name'][$i], $docPath)) {
                    $uploadedPaths[] = "Uploads/Projects/$id/$subDir/$safeDocName";
                }
            }
        }

        return $uploadedPaths;
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
