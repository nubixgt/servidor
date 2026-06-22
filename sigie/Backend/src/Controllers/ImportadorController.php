<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Models\ImportacionModel;

class ImportadorController extends Controller
{
    /* =========================================================================
     * IMPORTADORES
     * ========================================================================= */

    /**
     * Listar importadores
     */
    #[Authorize(['administrador', 'inspector'])]
    #[Route('/importadores', 'GET')]
    public function listarImportadores()
    {
        $model = new ImportacionModel();
        $importadores = $model->getAllImportadores();
        
        $this->json([
            'status' => 'success',
            'data' => $importadores
        ]);
    }

    /**
     * Crear importador (Administrador)
     */
    #[Authorize(['administrador'])]
    #[Route('/importadores', 'POST')]
    public function crearImportador()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $nombre = $input['nombre'] ?? '';
        $nit = $input['nit'] ?? '';
        $tipoProductos = $input['tipo_productos'] ?? '';

        if (empty($nombre) || empty($nit) || empty($tipoProductos)) {
            $this->json(['error' => 'Todos los campos (Nombre, NIT, Tipo de productos) son requeridos'], 400);
            return;
        }

        $model = new ImportacionModel();
        
        // Verificar si el NIT ya existe
        $importadores = $model->getAllImportadores();
        foreach ($importadores as $imp) {
            if ($imp['nit'] === $nit) {
                $this->json(['error' => 'Ya existe un importador registrado con ese NIT'], 400);
                return;
            }
        }

        $id = $model->createImportador([
            'nombre'         => $nombre,
            'nit'            => $nit,
            'tipo_productos' => $tipoProductos
        ]);

        if ($id) {
            $this->json([
                'status' => 'success',
                'message' => 'Importador registrado exitosamente.',
                'data' => ['id' => $id]
            ]);
        } else {
            $this->json(['error' => 'No se pudo registrar el importador'], 500);
        }
    }

    /**
     * Actualizar importador (Administrador)
     */
    #[Authorize(['administrador'])]
    #[Route('/importadores/{id}', 'PUT')]
    public function actualizarImportador($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $nombre = $input['nombre'] ?? '';
        $nit = $input['nit'] ?? '';
        $tipoProductos = $input['tipo_productos'] ?? '';

        if (empty($nombre) || empty($nit) || empty($tipoProductos)) {
            $this->json(['error' => 'Todos los campos son obligatorios'], 400);
            return;
        }

        $model = new ImportacionModel();
        $existente = $model->getImportadorById((int)$id);
        if (!$existente) {
            $this->json(['error' => 'Importador no encontrado'], 404);
            return;
        }

        // Verificar NIT duplicado excluyendo el propio ID
        $importadores = $model->getAllImportadores();
        foreach ($importadores as $imp) {
            if ($imp['nit'] === $nit && (int)$imp['id'] !== (int)$id) {
                $this->json(['error' => 'Ya existe otro importador con ese NIT'], 400);
                return;
            }
        }

        if ($model->updateImportador((int)$id, [
            'nombre'         => $nombre,
            'nit'            => $nit,
            'tipo_productos' => $tipoProductos
        ])) {
            $this->json([
                'status' => 'success',
                'message' => 'Importador actualizado exitosamente.'
            ]);
        } else {
            $this->json(['error' => 'No se pudo actualizar el importador'], 500);
        }
    }

    /**
     * Eliminar importador (Administrador)
     */
    #[Authorize(['administrador'])]
    #[Route('/importadores/{id}', 'DELETE')]
    public function eliminarImportador($id)
    {
        $model = new ImportacionModel();
        $existente = $model->getImportadorById((int)$id);
        if (!$existente) {
            $this->json(['error' => 'Importador no encontrado'], 404);
            return;
        }

        if ($model->deleteImportador((int)$id)) {
            $this->json([
                'status' => 'success',
                'message' => 'Importador eliminado exitosamente.'
            ]);
        } else {
            $this->json(['error' => 'No se pudo eliminar el importador'], 500);
        }
    }

    /* =========================================================================
     * IMPORTACIONES
     * ========================================================================= */

    /**
     * Listar importaciones
     */
    #[Authorize(['administrador', 'inspector'])]
    #[Route('/importaciones', 'GET')]
    public function listarImportaciones()
    {
        $importadorId = isset($_GET['importador_id']) && $_GET['importador_id'] !== '' ? (int)$_GET['importador_id'] : null;
        $tipoProducto = $_GET['tipo_producto'] ?? null;

        $model = new ImportacionModel();
        $importaciones = $model->getAllImportaciones($importadorId, $tipoProducto);

        $this->json([
            'status' => 'success',
            'data' => $importaciones
        ]);
    }

    /**
     * Registrar importación (Administrador o Inspector)
     */
    #[Authorize(['administrador', 'inspector'])]
    #[Route('/importaciones', 'POST')]
    public function registrarImportacion()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $fecha = $input['fecha'] ?? '';
        $importadorId = $input['importador_id'] ?? null;
        $tipoProducto = $input['tipo_producto'] ?? '';
        $volumenKilos = $input['volumen_kilos'] ?? null;
        $establecimiento = $input['establecimiento'] ?? '';

        if (empty($fecha) || empty($importadorId) || empty($tipoProducto) || empty($volumenKilos)) {
            $this->json(['error' => 'Los campos fecha, importador_id, tipo_producto y volumen_kilos son obligatorios'], 400);
            return;
        }

        $model = new ImportacionModel();
        $id = $model->createImportacion([
            'fecha'           => $fecha,
            'importador_id'   => $importadorId,
            'tipo_producto'   => $tipoProducto,
            'volumen_kilos'   => $volumenKilos,
            'establecimiento' => $establecimiento
        ]);

        if ($id) {
            $this->json([
                'status' => 'success',
                'message' => 'Importación registrada exitosamente.',
                'data' => ['id' => $id]
            ]);
        } else {
            $this->json(['error' => 'No se pudo registrar la importación'], 500);
        }
    }
}
