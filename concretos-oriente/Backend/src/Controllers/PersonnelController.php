<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\PersonnelService;
use Exception;

class PersonnelController extends Controller
{
    private PersonnelService $personnelService;

    public function __construct()
    {
        $this->personnelService = new PersonnelService();
    }

    // ----------------------------------------------------------------
    // GET /personnel
    // ----------------------------------------------------------------
    #[Route('/personnel', 'GET')]
    public function index()
    {
        try {
            $personnel = $this->personnelService->getAllPersonnel();

            $this->json([
                'status' => 'success',
                'data'   => $personnel
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /personnel  — crear nuevo empleado
    // ----------------------------------------------------------------
    #[Route('/personnel', 'POST')]
    public function store()
    {
        try {
            $data = [
                'tipo_empleado'      => trim($_POST['tipo_empleado']      ?? ''),
                'nombres'            => trim($_POST['nombres']            ?? ''),
                'apellidos'          => trim($_POST['apellidos']          ?? ''),
                'dpi'                => preg_replace('/\D/', '', trim($_POST['dpi'] ?? '')),
                'puesto'             => trim($_POST['puesto']             ?? ''),
                'salario_base'       => $_POST['salario_base']            ?? null,
                'tipo_planilla'      => trim($_POST['tipo_planilla']      ?? ''),
                'fecha_contratacion' => trim($_POST['fecha_contratacion'] ?? ''),
                'nit'                => trim($_POST['nit']              ?? '') ?: null,
                'telefono'           => trim($_POST['telefono']         ?? '') ?: null,
                'direccion'          => trim($_POST['direccion']        ?? '') ?: null,
                'numero_cuenta'      => trim($_POST['numero_cuenta']    ?? '') ?: null,
                'nombre_banco'       => trim($_POST['nombre_banco']     ?? '') ?: null,
                'tarifa_hora_extra'  => (isset($_POST['tarifa_hora_extra']) && $_POST['tarifa_hora_extra'] !== '')
                                        ? $_POST['tarifa_hora_extra'] : null,
                'diario_viaticos'    => (isset($_POST['diario_viaticos']) && $_POST['diario_viaticos'] !== '')
                                        ? $_POST['diario_viaticos'] : null,
                'contacto_nombres'   => trim($_POST['contacto_nombres'] ?? '') ?: null,
                'contacto_numero'    => trim($_POST['contacto_numero']  ?? '') ?: null,
                'cantidad_hijos'     => (isset($_POST['cantidad_hijos']) && $_POST['cantidad_hijos'] !== '')
                                        ? (int)$_POST['cantidad_hijos'] : null,
                'nivel_academico'    => trim($_POST['nivel_academico']  ?? '') ?: null,
                'fecha_nacimiento'   => (isset($_POST['fecha_nacimiento']) && $_POST['fecha_nacimiento'] !== '')
                                        ? $_POST['fecha_nacimiento'] : null,
                'igss'               => (isset($_POST['igss']) && $_POST['igss'] !== '')
                                        ? (int)$_POST['igss'] : null,
                'fecha_baja'         => (isset($_POST['fecha_baja']) && $_POST['fecha_baja'] !== '')
                                        ? $_POST['fecha_baja'] : null,
                'proyecto_id'        => (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                        ? (int)$_POST['proyecto_id'] : null,
            ];


            $fileData = $_FILES['foto'] ?? null;

            $result = $this->personnelService->createPersonnel($data, $fileData);

            $this->json([
                'status'    => 'success',
                'message'   => 'Personal creado correctamente',
                'id'        => $result['id'],
                'foto_path' => $result['foto_path']
            ], 201);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // POST /personnel/{id}  — actualizar empleado
    // ----------------------------------------------------------------
    #[Route('/personnel/{id}', 'POST')]
    public function update($id)
    {
        try {
            $data = [
                'tipo_empleado'      => trim($_POST['tipo_empleado']      ?? ''),
                'nombres'            => trim($_POST['nombres']            ?? ''),
                'apellidos'          => trim($_POST['apellidos']          ?? ''),
                'dpi'                => preg_replace('/\D/', '', trim($_POST['dpi'] ?? '')),
                'puesto'             => trim($_POST['puesto']             ?? ''),
                'salario_base'       => $_POST['salario_base']            ?? null,
                'tipo_planilla'      => trim($_POST['tipo_planilla']      ?? ''),
                'fecha_contratacion' => trim($_POST['fecha_contratacion'] ?? ''),
                'nit'                => trim($_POST['nit']              ?? '') ?: null,
                'telefono'           => trim($_POST['telefono']         ?? '') ?: null,
                'direccion'          => trim($_POST['direccion']        ?? '') ?: null,
                'numero_cuenta'      => trim($_POST['numero_cuenta']    ?? '') ?: null,
                'nombre_banco'       => trim($_POST['nombre_banco']     ?? '') ?: null,
                'tarifa_hora_extra'  => (isset($_POST['tarifa_hora_extra']) && $_POST['tarifa_hora_extra'] !== '')
                                        ? $_POST['tarifa_hora_extra'] : null,
                'diario_viaticos'    => (isset($_POST['diario_viaticos']) && $_POST['diario_viaticos'] !== '')
                                        ? $_POST['diario_viaticos'] : null,
                'contacto_nombres'   => trim($_POST['contacto_nombres'] ?? '') ?: null,
                'contacto_numero'    => trim($_POST['contacto_numero']  ?? '') ?: null,
                'cantidad_hijos'     => (isset($_POST['cantidad_hijos']) && $_POST['cantidad_hijos'] !== '')
                                        ? (int)$_POST['cantidad_hijos'] : null,
                'nivel_academico'    => trim($_POST['nivel_academico']  ?? '') ?: null,
                'fecha_nacimiento'   => (isset($_POST['fecha_nacimiento']) && $_POST['fecha_nacimiento'] !== '')
                                        ? $_POST['fecha_nacimiento'] : null,
                'igss'               => (isset($_POST['igss']) && $_POST['igss'] !== '')
                                        ? (int)$_POST['igss'] : null,
                'fecha_baja'         => (isset($_POST['fecha_baja']) && $_POST['fecha_baja'] !== '')
                                        ? $_POST['fecha_baja'] : null,
                'proyecto_id'        => (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                        ? (int)$_POST['proyecto_id'] : null,
            ];

            $fileData = $_FILES['foto'] ?? null;

            $result = $this->personnelService->updatePersonnel((int)$id, $data, $fileData);

            $this->json([
                'status'    => 'success',
                'message'   => 'Personal actualizado correctamente',
                'foto_path' => $result['foto_path']
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /personnel/{id}
    // ----------------------------------------------------------------
    #[Route('/personnel/{id}', 'DELETE')]
    public function destroy($id)
    {
        try {
            $this->personnelService->deletePersonnel((int)$id);

            $this->json([
                'status'  => 'success',
                'message' => 'Personal eliminado correctamente'
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
