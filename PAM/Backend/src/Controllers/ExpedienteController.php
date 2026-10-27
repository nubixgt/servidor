<?php
namespace App\Controllers;

use App\Attributes\HasPrivilege;
use App\Attributes\Route;
use App\Core\Controller;
use App\DTOs\CambioEstadoDTO;
use App\DTOs\CasoDTO;
use App\DTOs\ContactoDTO;
use App\DTOs\FiltrosDTO;
use App\DTOs\MovimientoNominaDTO;
use App\DTOs\ObservacionDTO;
use App\DTOs\ResolucionDTO;
use App\DTOs\SolicitudDTO;
use App\DTOs\VisitaDTO;
use App\Services\ExpedienteService;

/**
 * Beneficiarios / expedientes: búsqueda paginada en servidor, Expediente 360° y registro de gestiones.
 */
class ExpedienteController extends Controller
{
    #[Route('/expedientes', 'GET')]
    #[HasPrivilege('beneficiarios.ver')]
    public function index()
    {
        $this->json((new ExpedienteService())->listar(
            $this->user(),
            FiltrosDTO::fromRequest($_GET),
            (string) $this->query('orden', 'fsol'),
            (string) $this->query('dir', 'desc'),
            $this->query('cursor'),
            (int) $this->query('limite', 25)
        ));
    }

    #[Route('/expedientes/{eid}', 'GET')]
    #[HasPrivilege('beneficiarios.ver')]
    public function detalle($eid)
    {
        $this->json((new ExpedienteService())->detalle($this->user(), (int) $eid));
    }

    #[Route('/expedientes/{eid}/dpi', 'GET')]
    #[HasPrivilege('beneficiarios.ver_dpi')]
    public function dpi($eid)
    {
        $this->json((new ExpedienteService())->verDpi($this->user(), (int) $eid));
    }

    #[Route('/expedientes/{eid}/observaciones', 'POST')]
    #[HasPrivilege('observacion.registrar')]
    public function agregarObservacion($eid)
    {
        $this->json((new ExpedienteService())->agregarObservacion($this->user(), (int) $eid, ObservacionDTO::fromRequest($this->body())), 201);
    }

    #[Route('/expedientes/{eid}/visitas', 'POST')]
    #[HasPrivilege('visita.registrar')]
    public function agregarVisita($eid)
    {
        $this->json((new ExpedienteService())->agregarVisita($this->user(), (int) $eid, VisitaDTO::fromRequest($this->body())), 201);
    }

    #[Route('/expedientes/{eid}/resoluciones', 'POST')]
    #[HasPrivilege('resolucion.emitir')]
    public function agregarResolucion($eid)
    {
        $this->json((new ExpedienteService())->agregarResolucion($this->user(), (int) $eid, ResolucionDTO::fromRequest($this->body())), 201);
    }

    #[Route('/expedientes/{eid}/estado', 'POST')]
    #[HasPrivilege('estado.cambiar')]
    public function cambiarEstado($eid)
    {
        $this->json((new ExpedienteService())->cambiarEstado($this->user(), (int) $eid, CambioEstadoDTO::fromRequest($this->body())), 201);
    }

    #[Route('/expedientes/{eid}/nomina', 'POST')]
    #[HasPrivilege('nomina.gestionar')]
    public function movimientoNomina($eid)
    {
        $this->json((new ExpedienteService())->movimientoNomina($this->user(), (int) $eid, MovimientoNominaDTO::fromRequest($this->body())), 201);
    }

    #[Route('/expedientes/{eid}/contacto', 'PATCH')]
    #[HasPrivilege('beneficiarios.editar')]
    public function actualizarContacto($eid)
    {
        $this->json((new ExpedienteService())->actualizarContacto($this->user(), (int) $eid, ContactoDTO::fromRequest($this->body())));
    }

    #[Route('/expedientes', 'POST')]
    #[HasPrivilege('solicitud.registrar')]
    public function nuevaSolicitud()
    {
        $this->json((new ExpedienteService())->nuevaSolicitud($this->user(), SolicitudDTO::fromRequest($this->body())), 201);
    }

    #[Route('/expedientes/{eid}/casos', 'POST')]
    #[HasPrivilege('alertas.gestionar', 'calidad.gestionar')]
    public function gestionarCaso($eid)
    {
        $this->json((new ExpedienteService())->gestionarCaso($this->user(), (int) $eid, CasoDTO::fromRequest($this->body())));
    }
}
