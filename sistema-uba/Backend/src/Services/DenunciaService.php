<?php
namespace App\Services;

use App\Entities\Denuncia;
use App\Entities\ArchivoDenuncia;
use App\Repositories\DenunciaRepository;
use App\Repositories\ArchivoDenunciaRepository;
use Exception;

class DenunciaService
{
    private DenunciaRepository $denunciaRepo;
    private ArchivoDenunciaRepository $archivoRepo;
    private string $uploadDir;

    public function __construct()
    {
        $this->denunciaRepo = new DenunciaRepository();
        $this->archivoRepo = new ArchivoDenunciaRepository();
        $this->uploadDir = __DIR__ . '/../../uploads/';

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function createDenuncia(array $data, array $files): int
    {
        $denuncia = Denuncia::fromArray($data);
        
        // Convert array of infractions to string if needed
        if (isset($data['infracciones']) && is_array($data['infracciones'])) {
            $denuncia->infracciones = implode(',', $data['infracciones']);
        }

        $id = $this->denunciaRepo->create($denuncia);

        if (!$id) {
            throw new Exception("Error al crear la denuncia en la base de datos.");
        }

        // Handle File Uploads
        $this->handleFiles($id, $files);

        return $id;
    }

    private function handleFiles(int $denunciaId, array $files): void
    {
        // $files follows the $_FILES structure
        foreach ($files as $key => $fileData) {
            // Check if it's multiple files (like evidence[])
            if (is_array($fileData['name'])) {
                for ($i = 0; $i < count($fileData['name']); $i++) {
                    if ($fileData['error'][$i] === UPLOAD_ERR_OK) {
                        $this->saveFile($denunciaId, $key, [
                            'name' => $fileData['name'][$i],
                            'tmp_name' => $fileData['tmp_name'][$i],
                            'type' => $fileData['type'][$i],
                            'error' => $fileData['error'][$i],
                            'size' => $fileData['size'][$i],
                        ]);
                    }
                }
            } else {
                if ($fileData['error'] === UPLOAD_ERR_OK) {
                    $this->saveFile($denunciaId, $key, $fileData);
                }
            }
        }
    }

    private function saveFile(int $denunciaId, string $tipo, array $file): void
    {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $denunciaId . '_' . $tipo . '_' . uniqid() . '.' . $ext;
        $targetPath = $this->uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $archivo = new ArchivoDenuncia();
            $archivo->denunciaId = $denunciaId;
            $archivo->tipoArchivo = $this->normalizeType($tipo);
            $archivo->rutaArchivo = 'uploads/' . $fileName;
            $archivo->nombreOriginal = $file['name'];
            $archivo->mimeType = $file['type'];

            $this->archivoRepo->create($archivo);
        }
    }

    private function normalizeType(string $key): string
    {
        // Map the form field key to the ENUM in DB
        $map = [
            'dpi_frontal' => 'dpi_frontal',
            'dpi_reverso' => 'dpi_reverso',
            'fachada' => 'fachada',
            'evidencia_foto' => 'evidencia_foto',
            'evidencia_documento' => 'evidencia_documento'
        ];

        return $map[$key] ?? 'evidencia_foto';
    }
}
