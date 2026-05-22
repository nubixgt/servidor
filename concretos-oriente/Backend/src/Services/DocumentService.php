<?php
namespace App\Services;

use App\Repositories\DocumentRepository;
use Exception;

class DocumentService
{
    private DocumentRepository $documentRepository;

    public function __construct()
    {
        $this->documentRepository = new DocumentRepository();
    }

    public function getAllDocuments(): array
    {
        return $this->documentRepository->findAll();
    }

    public function createDocument(array $data, ?array $fileData): void
    {
        if (empty($data['tipo_documento']) || empty($data['nombre_documento'])) {
            throw new Exception('El tipo y nombre de documento son obligatorios', 400);
        }

        if (!$fileData || empty($fileData['name']) || $fileData['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Debe subir un archivo válido', 400);
        }

        $usuario_id = !empty($data['usuario_id']) ? $data['usuario_id'] : 'default';
        $baseDir = __DIR__ . '/../../Uploads/Documents/' . $usuario_id;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0777, true);
        }
        
        $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'xls', 'xlsx', 'csv'];
        if (!in_array($ext, $allowedExts)) {
            throw new Exception('Formato de archivo no permitido. Solo PDF, Imagenes o Excel.', 400);
        }

        $fileName = uniqid('doc_') . '_' . time() . '.' . $ext;
        $destPath = $baseDir . '/' . $fileName;
        
        if (!move_uploaded_file($fileData['tmp_name'], $destPath)) {
            throw new Exception('No se pudo subir el archivo al servidor', 500);
        }
        
        $data['archivo_path'] = 'Uploads/Documents/' . $usuario_id . '/' . $fileName;
        $data['tipo_archivo'] = $ext;
        $data['peso_archivo'] = $fileData['size'];

        $this->documentRepository->create($data);
    }
}
