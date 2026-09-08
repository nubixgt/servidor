<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\PersonnelService;
use App\Repositories\EmployeePayrollRepository;
use Exception;

class PersonnelController extends Controller
{
    private PersonnelService $personnelService;
    private EmployeePayrollRepository $payrollRepo;

    public function __construct()
    {
        $this->personnelService = new PersonnelService();
        $this->payrollRepo = new EmployeePayrollRepository();
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
    // GET /personnel/renap/{cui}  — consultar RENAP
    // ----------------------------------------------------------------
    #[Route('/personnel/renap/{cui}', 'GET')]
    public function renap($cui)
    {
        try {
            $cleanCui = preg_replace('/\D/', '', $cui);
            if (strlen($cleanCui) !== 13) {
                $this->json(['status' => 'error', 'message' => 'El CUI debe tener 13 dígitos'], 400);
                return;
            }

            $url = "http://159.203.113.174/renap.php?cui=" . urlencode($cleanCui);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $httpCode !== 200) {
                $this->json(['status' => 'error', 'message' => 'No se pudo consultar el servicio de RENAP'], 502);
                return;
            }

            $data = json_decode($response, true);
            $this->json($data);
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
                'edades_hijos'       => trim($_POST['edades_hijos']     ?? '') ?: null,
                'nivel_academico'    => trim($_POST['nivel_academico']  ?? '') ?: null,
                'fecha_nacimiento'   => (isset($_POST['fecha_nacimiento']) && $_POST['fecha_nacimiento'] !== '')
                                        ? $_POST['fecha_nacimiento'] : null,
                'depto_nacimiento'   => trim($_POST['depto_nacimiento'] ?? '') ?: null,
                'muni_nacimiento'    => trim($_POST['muni_nacimiento']  ?? '') ?: null,
                'estado_civil'       => trim($_POST['estado_civil']     ?? '') ?: null,
                'igss'               => (isset($_POST['igss']) && $_POST['igss'] !== '')
                                        ? (int)$_POST['igss'] : null,
                'igss_numero'        => trim($_POST['igss_numero'] ?? '') ?: null,
                'fecha_baja'         => (isset($_POST['fecha_baja']) && $_POST['fecha_baja'] !== '')
                                        ? $_POST['fecha_baja'] : null,
                'proyecto_id'        => (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                        ? (int)$_POST['proyecto_id'] : null,
            ];

            $files = [
                'foto'             => $_FILES['foto'] ?? null,
                'dpi_adjunto'      => $_FILES['dpi_adjunto'] ?? null,
                'contrato_adjunto' => $_FILES['contrato_adjunto'] ?? null,
                'licencia_adjunto' => $_FILES['licencia_adjunto'] ?? null,
            ];

            $result = $this->personnelService->createPersonnel($data, $files);

            $this->json([
                'status'    => 'success',
                'message'   => 'Personal creado correctamente',
                'id'        => $result['id'],
                'foto_path' => $result['foto_path'] ?? null
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
                'edades_hijos'       => trim($_POST['edades_hijos']     ?? '') ?: null,
                'nivel_academico'    => trim($_POST['nivel_academico']  ?? '') ?: null,
                'fecha_nacimiento'   => (isset($_POST['fecha_nacimiento']) && $_POST['fecha_nacimiento'] !== '')
                                        ? $_POST['fecha_nacimiento'] : null,
                'depto_nacimiento'   => trim($_POST['depto_nacimiento'] ?? '') ?: null,
                'muni_nacimiento'    => trim($_POST['muni_nacimiento']  ?? '') ?: null,
                'estado_civil'       => trim($_POST['estado_civil']     ?? '') ?: null,
                'igss'               => (isset($_POST['igss']) && $_POST['igss'] !== '')
                                        ? (int)$_POST['igss'] : null,
                'igss_numero'        => trim($_POST['igss_numero'] ?? '') ?: null,
                'fecha_baja'         => (isset($_POST['fecha_baja']) && $_POST['fecha_baja'] !== '')
                                        ? $_POST['fecha_baja'] : null,
                'proyecto_id'        => (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] !== '')
                                        ? (int)$_POST['proyecto_id'] : null,
            ];

            $files = [
                'foto'             => $_FILES['foto'] ?? null,
                'dpi_adjunto'      => $_FILES['dpi_adjunto'] ?? null,
                'contrato_adjunto' => $_FILES['contrato_adjunto'] ?? null,
                'licencia_adjunto' => $_FILES['licencia_adjunto'] ?? null,
            ];

            $result = $this->personnelService->updatePersonnel((int)$id, $data, $files);

            $this->json([
                'status'    => 'success',
                'message'   => 'Personal actualizado correctamente',
                'foto_path' => $result['foto_path'] ?? null
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
            $this->json(['status' => 'success', 'message' => 'Personal eliminado correctamente']);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $code = $code >= 400 && $code < 600 ? $code : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // GET /personnel/payroll-payments — Historial de Pagos de Planilla
    // ----------------------------------------------------------------
    #[Route('/personnel/payroll-payments', 'GET')]
    public function getPayrollPayments()
    {
        try {
            $payments = $this->payrollRepo->findAll();
            $this->json(['status' => 'success', 'data' => $payments]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------------------
    // POST /personnel/payroll-payments — Registrar Pago Mensual
    // ----------------------------------------------------------------
    #[Route('/personnel/payroll-payments', 'POST')]
    public function createPayrollPayment()
    {
        try {
            $raw = file_get_contents('php://input');
            $body = json_decode($raw, true) ?: $_POST;

            $personnel_id = (int)($body['personnel_id'] ?? 0);
            if (!$personnel_id) {
                throw new Exception('Debe seleccionar un colaborador.', 400);
            }

            $salario_base = (float)($body['salario_base'] ?? 0);
            $dias_trabajados = (int)($body['dias_trabajados'] ?? 30);
            $sueldo_calculado = (float)($body['sueldo_calculado'] ?? round(($salario_base / 30) * $dias_trabajados, 2));

            $tiene_horas_extras = !empty($body['tiene_horas_extras']) ? 1 : 0;
            $horas_extras = $tiene_horas_extras ? (float)($body['horas_extras'] ?? 0) : 0;
            $tarifa_hora_extra = $tiene_horas_extras ? (float)($body['tarifa_hora_extra'] ?? 0) : 0;
            $monto_horas_extras = $tiene_horas_extras ? (float)($body['monto_horas_extras'] ?? round($horas_extras * $tarifa_hora_extra, 2)) : 0;

            $tiene_viaticos = !empty($body['tiene_viaticos']) ? 1 : 0;
            $monto_viaticos = $tiene_viaticos ? (float)($body['monto_viaticos'] ?? 0) : 0;
            $observaciones_viaticos = $tiene_viaticos ? trim($body['observaciones_viaticos'] ?? '') : null;

            $total_pagar = (float)($body['total_pagar'] ?? round($sueldo_calculado + $monto_horas_extras + $monto_viaticos, 2));

            $data = [
                'personnel_id'           => $personnel_id,
                'periodo'                => trim($body['periodo'] ?? date('F Y')),
                'fecha_pago'             => trim($body['fecha_pago'] ?? date('Y-m-d')),
                'salario_base'           => $salario_base,
                'dias_trabajados'        => $dias_trabajados,
                'sueldo_calculado'       => $sueldo_calculado,
                'tiene_horas_extras'     => $tiene_horas_extras,
                'horas_extras'           => $horas_extras,
                'tarifa_hora_extra'      => $tarifa_hora_extra,
                'monto_horas_extras'     => $monto_horas_extras,
                'tiene_viaticos'         => $tiene_viaticos,
                'monto_viaticos'         => $monto_viaticos,
                'observaciones_viaticos' => $observaciones_viaticos,
                'total_pagar'            => $total_pagar,
                'metodo_pago'            => trim($body['metodo_pago'] ?? 'Transferencia'),
                'observaciones'          => trim($body['observaciones'] ?? '') ?: null,
            ];

            $newId = $this->payrollRepo->create($data);

            $this->json([
                'status'  => 'success',
                'message' => 'Pago de planilla registrado correctamente',
                'id'      => $newId
            ], 201);
        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    // ----------------------------------------------------------------
    // DELETE /personnel/payroll-payments/{id} — Eliminar Pago
    // ----------------------------------------------------------------
    #[Route('/personnel/payroll-payments/{id}', 'DELETE')]
    public function deletePayrollPayment($id)
    {
        try {
            $payment = $this->payrollRepo->findById((int)$id);
            if (!$payment) {
                throw new Exception('Registro de pago no encontrado.', 404);
            }
            $this->payrollRepo->delete((int)$id);
            $this->json(['status' => 'success', 'message' => 'Pago eliminado correctamente']);
        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            $this->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
