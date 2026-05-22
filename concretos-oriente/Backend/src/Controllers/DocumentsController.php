<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\DocumentService;
use Exception;

class DocumentsController extends Controller
{
    private DocumentService $documentService;

    public function __construct()
    {
        $this->documentService = new DocumentService();
    }

    #[Route('/documents', 'GET')]
    public function getDocuments()
    {
        try {
            $documents = $this->documentService->getAllDocuments();
            $this->json(['status' => 'success', 'message' => 'Documentos', 'data' => $documents]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/documents', 'POST')]
    public function createDocument()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $tipo_documento = trim($input['tipo_documento'] ?? '');
            if ($tipo_documento === 'Otro' && !empty($input['tipo_documento_otro'])) {
                $tipo_documento = trim($input['tipo_documento_otro']);
            }

            $data = [
                'tipo_documento'     => $tipo_documento,
                'project_id'         => !empty($input['project_id']) ? (int)$input['project_id'] : null,
                'modulo_relacionado' => !empty($input['modulo_relacionado']) ? trim($input['modulo_relacionado']) : null,
                'nombre_documento'   => trim($input['nombre_documento'] ?? ''),
                'etiquetas'          => !empty($input['etiquetas']) ? trim($input['etiquetas']) : null,
                'usuario_id'         => !empty($input['usuario_id']) ? trim($input['usuario_id']) : 'default',
            ];

            $fileData = $_FILES['archivo'] ?? null;

            $this->documentService->createDocument($data, $fileData);

            $this->json(['status' => 'success', 'message' => 'Documento subido exitosamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], $code);
        }
    }
}
