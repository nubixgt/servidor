<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Repositories\IncidentRepository;
use Exception;

class IncidentController extends Controller
{
    private IncidentRepository $repository;

    public function __construct()
    {
        $this->repository = new IncidentRepository();
    }

    #[Route('/incidents', 'GET')]
    public function index()
    {
        try {
            $incidents = $this->repository->findAllWithPersonnel();
            $this->json(['status' => 'success', 'data' => $incidents]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/incidents', 'POST')]
    public function store()
    {
        try {
            $data = [
                'personnel_id' => (isset($_POST['personnel_id']) && $_POST['personnel_id'] !== '')
                                    ? (int)$_POST['personnel_id'] : null,
                'texto'        => trim($_POST['texto']  ?? ''),
                'fecha'        => trim($_POST['fecha']  ?? ''),
                'motivo'       => trim($_POST['motivo'] ?? ''),
                'adjunto_path' => null,
            ];

            if (!$data['personnel_id'] || empty($data['texto']) || empty($data['fecha']) || empty($data['motivo'])) {
                throw new Exception('Todos los campos son obligatorios.', 400);
            }

            $id = $this->repository->create($data);

            $savedPaths = [];
            $uploadDir = __DIR__ . '/../../Uploads/Incidents/' . $id . '/';
            $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'webp'];

            $processUpload = function($tmpName, $originalName) use ($uploadDir, $allowed, $id, &$savedPaths) {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $uniqueName = uniqid('evidencia_') . '.' . $ext;
                    $dest = $uploadDir . $uniqueName;
                    if (move_uploaded_file($tmpName, $dest)) {
                        $savedPaths[] = 'Uploads/Incidents/' . $id . '/' . $uniqueName;
                    }
                }
            };

            if (!empty($_FILES['adjuntos']['name'])) {
                if (is_array($_FILES['adjuntos']['name'])) {
                    for ($i = 0; $i < count($_FILES['adjuntos']['name']); $i++) {
                        if ($_FILES['adjuntos']['error'][$i] === UPLOAD_ERR_OK) {
                            $processUpload($_FILES['adjuntos']['tmp_name'][$i], $_FILES['adjuntos']['name'][$i]);
                        }
                    }
                } else if ($_FILES['adjuntos']['error'] === UPLOAD_ERR_OK) {
                    $processUpload($_FILES['adjuntos']['tmp_name'], $_FILES['adjuntos']['name']);
                }
            }

            if (!empty($_FILES['adjunto']['name'])) {
                if (is_array($_FILES['adjunto']['name'])) {
                    for ($i = 0; $i < count($_FILES['adjunto']['name']); $i++) {
                        if ($_FILES['adjunto']['error'][$i] === UPLOAD_ERR_OK) {
                            $processUpload($_FILES['adjunto']['tmp_name'][$i], $_FILES['adjunto']['name'][$i]);
                        }
                    }
                } else if ($_FILES['adjunto']['error'] === UPLOAD_ERR_OK) {
                    $processUpload($_FILES['adjunto']['tmp_name'], $_FILES['adjunto']['name']);
                }
            }

            if (!empty($savedPaths)) {
                $this->repository->updateAdjuntoPath($id, json_encode($savedPaths));
            }

            $this->json(['status' => 'success', 'message' => 'Incidencia registrada correctamente', 'id' => $id], 201);

        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    #[Route('/incidents/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $incident = $this->repository->findById((int)$id);
            if (!$incident) {
                throw new Exception('Incidencia no encontrada.', 404);
            }

            $uploadDir = __DIR__ . '/../../Uploads/Incidents/' . (int)$id . '/';
            if (is_dir($uploadDir)) {
                foreach (glob($uploadDir . '*') as $file) {
                    if (is_file($file)) unlink($file);
                }
                rmdir($uploadDir);
            }

            $this->repository->delete((int)$id);

            $this->json(['status' => 'success', 'message' => 'Incidencia eliminada correctamente']);

        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
