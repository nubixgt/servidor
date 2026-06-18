<?php
namespace App\Services;

use App\Repositories\BudgetExtensionRepository;
use Exception;

class BudgetExtensionService
{
    private BudgetExtensionRepository $repo;

    public function __construct()
    {
        $this->repo = new BudgetExtensionRepository();
    }

    public function getByProject(int $projectId): array
    {
        $extensions = $this->repo->findByProject($projectId);
        foreach ($extensions as &$ext) {
            $ext['documentos'] = $ext['documentos'] ? json_decode($ext['documentos'], true) : [];
        }
        return $extensions;
    }

    public function create(array $data, ?array $filesData): void
    {
        if (empty($data['monto']) || empty($data['tipo_ampliacion'])) {
            throw new Exception('Faltan campos requeridos (monto, tipo_ampliacion).', 400);
        }

        $tiposValidos = ['Trabajo Extra', 'Orden de Cambio', 'Trabajo Suplementario'];
        if (!in_array($data['tipo_ampliacion'], $tiposValidos)) {
            throw new Exception('Tipo de ampliación no válido.', 400);
        }

        $extensionId = $this->repo->create($data);

        $uploadedDocs = [];
        if ($filesData && !empty($filesData['name']) && is_array($filesData['name'])) {
            $baseDir = __DIR__ . '/../../Uploads/AmpliacionPresupuesto/' . $extensionId;
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            $count = min(count($filesData['name']), 3);
            for ($i = 0; $i < $count; $i++) {
                if ($filesData['error'][$i] === UPLOAD_ERR_OK && !empty($filesData['name'][$i])) {
                    $ext      = pathinfo($filesData['name'][$i], PATHINFO_EXTENSION);
                    $fileName = 'doc_' . ($i + 1) . '_' . time() . '.' . $ext;
                    $destPath = $baseDir . '/' . $fileName;
                    if (move_uploaded_file($filesData['tmp_name'][$i], $destPath)) {
                        $uploadedDocs[] = 'Uploads/AmpliacionPresupuesto/' . $extensionId . '/' . $fileName;
                    }
                }
            }

            if (!empty($uploadedDocs)) {
                $this->repo->updateDocuments($extensionId, json_encode($uploadedDocs));
            }
        }
    }
}
