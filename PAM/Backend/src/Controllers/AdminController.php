<?php
namespace App\Controllers;

use App\Attributes\HasPrivilege;
use App\Attributes\Route;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\DTOs\CambioUsuarioDTO;
use App\DTOs\NuevoRolDTO;
use App\DTOs\NuevoUsuarioDTO;
use App\DTOs\TipoResolucionDTO;
use App\Services\AdminService;

class AdminController extends Controller
{
    #[Route('/admin/usuarios', 'GET')]
    #[HasPrivilege('usuarios.gestionar')]
    public function usuarios()
    {
        $this->json((new AdminService())->usuarios());
    }

    #[Route('/admin/usuarios', 'POST')]
    #[HasPrivilege('usuarios.gestionar')]
    public function crearUsuario()
    {
        $this->json((new AdminService())->crearUsuario(NuevoUsuarioDTO::fromRequest($this->body())), 201);
    }

    #[Route('/admin/usuarios/{uid}', 'PATCH')]
    #[HasPrivilege('usuarios.gestionar')]
    public function editarUsuario($uid)
    {
        $this->json((new AdminService())->editarUsuario($this->user(), (int) $uid, CambioUsuarioDTO::fromRequest($this->body())));
    }

    #[Route('/admin/usuarios/{uid}/restablecer-clave', 'POST')]
    #[HasPrivilege('usuarios.gestionar')]
    public function restablecerClave($uid)
    {
        $this->json((new AdminService())->restablecerClave($this->user(), (int) $uid));
    }

    #[Route('/admin/roles', 'GET')]
    #[HasPrivilege('roles.gestionar', 'usuarios.gestionar', 'auditoria.ver')]
    public function roles()
    {
        $this->json((new AdminService())->roles());
    }

    #[Route('/admin/roles', 'POST')]
    #[HasPrivilege('roles.gestionar')]
    public function crearRol()
    {
        $this->json((new AdminService())->crearRol(NuevoRolDTO::fromRequest($this->body())), 201);
    }

    #[Route('/admin/roles/{rid}/permisos', 'PUT')]
    #[HasPrivilege('roles.gestionar')]
    public function asignarPermisos($rid)
    {
        $permisos = Request::jsonCrudo();
        if (!is_array($permisos)) {
            throw new HttpException('Se esperaba una lista de permisos', 400);
        }
        $this->json((new AdminService())->asignarPermisos((int) $rid, $permisos));
    }

    #[Route('/admin/bitacora', 'GET')]
    #[HasPrivilege('auditoria.ver')]
    public function bitacora()
    {
        $this->json((new AdminService())->bitacora($_GET));
    }

    #[Route('/admin/parametros', 'GET')]
    #[HasPrivilege('catalogos.gestionar', 'auditoria.ver')]
    public function parametros()
    {
        $this->json((new AdminService())->parametros());
    }

    #[Route('/admin/parametros/{clave}', 'PUT')]
    #[HasPrivilege('catalogos.gestionar')]
    public function cambiarParametro($clave)
    {
        $this->json((new AdminService())->cambiarParametro($clave, Request::jsonCrudo()));
    }

    #[Route('/admin/tipos-resolucion', 'PUT')]
    #[HasPrivilege('catalogos.gestionar')]
    public function guardarTipo()
    {
        $this->json((new AdminService())->guardarTipoResolucion(TipoResolucionDTO::fromRequest($this->body())));
    }
}
