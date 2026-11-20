<?php
namespace App\Controllers;

use App\Attributes\Authorize;
use App\Attributes\Route;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\HttpException;
use App\DTOs\FiltrosBeneficiariosDTO;
use App\Services\BeneficiarioService;
use App\Services\SeguimientoService;
use App\Utils\Response;

class BeneficiarioController extends Controller
{
    #[Route('/beneficiarios', 'GET')]
    #[Authorize(['admin', 'tecnico'])]
    public function index()
    {
        Response::success((new BeneficiarioService())->listar(FiltrosBeneficiariosDTO::fromQuery($_GET)));
    }

    #[Route('/beneficiarios/resumen', 'GET')]
    #[Authorize(['admin', 'tecnico'])]
    public function resumen()
    {
        Response::success((new BeneficiarioService())->resumen());
    }

    /** Guarda comentario, imagen y/o teléfono de una fila (multipart/form-data: comentario, imagen, telefono). */
    #[Route('/beneficiarios/{id}/seguimiento', 'POST')]
    #[Authorize(['admin', 'tecnico'])]
    public function guardarSeguimiento($id)
    {
        $comentario = isset($_POST['comentario']) ? (string) $_POST['comentario'] : null;
        $archivo = $_FILES['imagen'] ?? null;
        $telefono = isset($_POST['telefono']) ? (string) $_POST['telefono'] : null;
        if ($comentario === null && $archivo === null && $telefono === null) {
            throw new HttpException('No se envió ningún dato para guardar', 422);
        }
        $r = (new SeguimientoService())->guardar($this->id($id), Auth::userId(), $comentario, $archivo, $telefono);
        Response::success($r, 'Seguimiento guardado');
    }

    /** Solo el administrador puede eliminar la imagen adjunta. */
    #[Route('/beneficiarios/{id}/seguimiento/imagen', 'DELETE')]
    #[Authorize(['admin'])]
    public function quitarImagen($id)
    {
        $r = (new SeguimientoService())->quitarImagen($this->id($id), Auth::userId());
        Response::success($r, 'Imagen eliminada');
    }

    /** Entrega el archivo de la imagen (el frontend lo pide con el token y lo muestra como blob). */
    #[Route('/beneficiarios/{id}/imagen', 'GET')]
    #[Authorize(['admin', 'tecnico'])]
    public function imagen($id)
    {
        $img = (new SeguimientoService())->imagen($this->id($id));
        header('Content-Type: ' . $img['mime']);
        header('Content-Length: ' . filesize($img['ruta']));
        header('Content-Disposition: inline');
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($img['ruta']);
        exit;
    }

    private function id($valor): int
    {
        if (!ctype_digit((string) $valor) || (int) $valor < 1) {
            throw new HttpException('Identificador inválido', 400);
        }
        return (int) $valor;
    }
}
