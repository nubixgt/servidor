<?php
namespace App\Services\VISAN;

use App\Repositories\VISAN\AsistenciaRepository;
use App\Repositories\VISAN\DapcaRepository;
use App\Repositories\VISAN\SolicitudRepository;
use App\DTOs\VISAN\SolicitudDTO;
use Exception;

class VisanService
{
    private $repository;
    private $dapcaRepo;
    private $solicitudRepo;

    public function __construct()
    {
        $this->repository = new AsistenciaRepository();
        $this->dapcaRepo = new DapcaRepository();
        $this->solicitudRepo = new SolicitudRepository();
    }

    public function getDashboardData($filters = [])
    {
        $filtros_activos = 0;
        if (!empty($filters['fecha_inicio'])) $filtros_activos++;
        if (!empty($filters['fecha_fin'])) $filtros_activos++;
        if (!empty($filters['departamento'])) $filtros_activos++;
        if (!empty($filters['municipio'])) $filtros_activos++;

        $stats = $this->repository->getEstadisticasGenerales($filters);
        $dept_list = array_column($this->repository->getDepartamentos(), 'departamento');
        $muni_list = array_column($this->repository->getMunicipios(), 'municipio');

        $top10_dept = $this->repository->getTop10Departamentos($filters);
        $top15_muni = $this->repository->getTop15Municipios($filters);
        $top10_insan = $this->repository->getTop10Insan($filters);
        $nda_dept = $this->repository->getNdaPorDept($filters);
        $aa_dept = $this->repository->getAaPorDept($filters);

        return [
            'filtros_activos' => $filtros_activos,
            'stats' => $stats,
            'graficas' => [
                'top10_dept_aa_apa' => [
                    'labels' => array_column($top10_dept, 'departamento'),
                    'data' => array_column($top10_dept, 'total_asistencia')
                ],
                'top15_municipios' => [
                    'labels' => array_column($top15_muni, 'municipio'),
                    'data' => array_column($top15_muni, 'total_asistencia')
                ],
                'top10_insan' => [
                    'labels' => array_column($top10_insan, 'departamento'),
                    'data' => array_column($top10_insan, 'total_insan')
                ],
                'nda_por_dept' => [
                    'labels' => array_column($nda_dept, 'departamento'),
                    'data' => array_column($nda_dept, 'nda_recursos')
                ],
                'aa_por_dept' => [
                    'labels' => array_column($aa_dept, 'departamento'),
                    'raciones' => array_column($aa_dept, 'aa_recursos'),
                    'fondos' => array_column($aa_dept, 'apa_fondos')
                ]
            ],
            'listas' => [
                'departamentos' => $dept_list,
                'municipios' => $muni_list
            ]
        ];
    }

    public function getTableData($filters = [])
    {
        $rows = $this->repository->getDatosTabla($filters);
        $dept_list = array_column($this->repository->getDepartamentos(), 'departamento');
        $muni_list = array_column($this->repository->getMunicipios(), 'municipio');

        $datos_por_departamento = [];
        $total_departamentos    = 0;
        
        // Calcular totales globales y agrupar
        $totales = [
            'registros' => count($rows),
            'total_aa_apa' => 0,
            'total_aa_r' => 0,
            'apa_f' => 0,
            'insan_total' => 0,
            'nda_nacional' => 0,
            'medida_transitoria' => 0
        ];

        foreach ($rows as $r) {
            // Global totals
            $totales['total_aa_apa'] += (int)$r['total_aa_apa'];
            $totales['total_aa_r'] += (int)$r['total_aa_r'];
            $totales['apa_f'] += (int)$r['apa_f'];
            $totales['insan_total'] += (int)$r['insan_r'] + (int)$r['insan_f'];
            $totales['nda_nacional'] += (int)$r['nda_nacional_r'] + (int)$r['nda_nacional_f'];
            $totales['medida_transitoria'] += (int)$r['medida_transitoria_r'] + (int)$r['medida_transitoria_f'];
            
            // Group by department
            $dept = $r['departamento'];
            if (!isset($datos_por_departamento[$dept])) {
                $datos_por_departamento[$dept] = [
                    'municipios' => [],
                    'totales'    => [
                        'total_aa_r'   => 0,
                        'total_aa_f'   => 0,
                        'total_aa_apa' => 0,
                        'nda_severa'   => 0,
                        'insan_total'  => 0,
                        'apa_f'        => 0,
                        'count'        => 0
                    ]
                ];
                $total_departamentos++;
            }
            $datos_por_departamento[$dept]['municipios'][]               = $r;
            $datos_por_departamento[$dept]['totales']['total_aa_r']      += (int)$r['total_aa_r'];
            $datos_por_departamento[$dept]['totales']['total_aa_f']      += (int)$r['total_aa_f'];
            $datos_por_departamento[$dept]['totales']['total_aa_apa']    += (int)$r['total_aa_apa'];
            // Asumiendo que nda_severa fue reemplazado por nda_nacional en las queries recientes
            $datos_por_departamento[$dept]['totales']['nda_severa']      += (int)$r['nda_nacional_r'] + (int)$r['nda_nacional_f'];
            $datos_por_departamento[$dept]['totales']['insan_total']     += (int)$r['insan_r'] + (int)$r['insan_f'];
            $datos_por_departamento[$dept]['totales']['apa_f']           += (int)$r['apa_f'];
            $datos_por_departamento[$dept]['totales']['count']++;
        }

        $filtros_activos = 0;
        if (!empty($filters['busqueda'])) $filtros_activos++;
        if (!empty($filters['departamento'])) $filtros_activos++;
        if (!empty($filters['fecha_inicio'])) $filtros_activos++;
        if (!empty($filters['fecha_fin'])) $filtros_activos++;

        return [
            'filtros_activos'     => $filtros_activos,
            'total_registros'     => count($rows),
            'total_departamentos' => $total_departamentos,
            'datos'               => $datos_por_departamento,
            'totales'             => $totales,
            'listas' => [
                'departamentos' => $dept_list,
                'municipios' => $muni_list
            ]
        ];
    }

    // --- Historial ---
    public function getHistorialData($filters = [])
    {
        $stats = $this->repository->getHistorialStats($filters);
        $top_campos = $this->repository->getCamposFrecuentes($filters);
        $historial = $this->repository->getHistorial($filters);
        $opciones = $this->repository->getHistorialFiltrosOpciones();

        return [
            'stats' => $stats,
            'graficas' => [
                'campos_frecuentes' => [
                    'labels' => array_column($top_campos, 'campo_modificado'),
                    'data' => array_column($top_campos, 'total_cambios')
                ]
            ],
            'historial' => $historial,
            'opciones_filtros' => [
                'departamentos' => array_column($opciones['departamentos'], 'departamento'),
                'municipios' => array_column($opciones['municipios'], 'municipio'),
                'campos' => array_column($opciones['campos'], 'campo_modificado')
            ]
        ];
    }

    public function getRegistroById($id)
    {
        return $this->repository->getRegistroById($id);
    }

    public function updateRegistro($id, $data)
    {
        $registro_antiguo = $this->repository->getRegistroById($id);
        if (!$registro_antiguo) {
            throw new Exception("Registro no encontrado");
        }

        // Calcula total_aa_apa si no viene
        if (!isset($data['total_aa_apa'])) {
            $data['total_aa_apa'] = ($data['total_aa_r'] ?? 0) + ($data['apa_f'] ?? 0);
        }

        $exito = $this->repository->updateRegistro($id, $data);
        if ($exito) {
            // Registrar cambios en historial
            $campos_a_monitorear = [
                'nda_nacional_r', 'nda_nacional_f', 'insan_r', 'insan_f',
                'medida_transitoria_r', 'medida_transitoria_f', 'total_aa_r', 'total_aa_f',
                'apa_f', 'total_aa_apa'
            ];

            foreach ($campos_a_monitorear as $campo) {
                if (isset($data[$campo]) && $registro_antiguo[$campo] != $data[$campo]) {
                    $this->repository->registrarCambio(
                        $id,
                        $registro_antiguo['departamento'],
                        $registro_antiguo['municipio'],
                        $campo,
                        $registro_antiguo[$campo],
                        $data[$campo],
                        $data['observaciones'] ?? 'Actualización manual'
                    );
                }
            }
        }
        return $exito;
    }

    // --- Excel Import ---
    public function importExcel($fileInfo)
    {
        require_once __DIR__ . '/../../../vendor_excel/autoload.php';

        $file = $fileInfo['tmp_name'];
        $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
            throw new Exception('Solo se permiten archivos CSV, XLSX o XLS.');
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (count($rows) < 2) {
            throw new Exception('El archivo está vacío o no tiene el formato correcto.');
        }

        $insertados = 0;
        $actualizados = 0;
        $errores = [];

        // Detectar si VISAN 2025 (2 filas header) o antiguo
        $csv_mode = false;
        $start_row = 2; 

        if (isset($rows[1][0]) && strtoupper(trim((string)$rows[1][0])) === 'DEPARTAMENTO') {
            $start_row = 2;
        } else {
            $csv_mode = true;
            $start_row = 1;
            $headers = array_map(function($h) { return strtolower(trim((string)$h)); }, $rows[0]);
        }

        for ($i = $start_row; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            if ($csv_mode) {
                if (count($row) !== count($headers)) continue;
                $data = array_combine($headers, $row);
            } else {
                if (empty(trim((string)($row[0] ?? ''))) || empty(trim((string)($row[1] ?? '')))) {
                    continue;
                }
                $data = [
                    'departamento' => $row[0] ?? '',
                    'municipio' => $row[1] ?? '',
                    'nda_severa_r' => 0, 'nda_severa_f' => 0,
                    'nda_nacional_r' => $row[2] ?? 0, 'nda_nacional_f' => $row[3] ?? 0,
                    'nda_plan_abordaje_r' => 0, 'nda_plan_abordaje_f' => 0,
                    'insan_r' => $row[4] ?? 0, 'insan_f' => $row[5] ?? 0,
                    'insan_emergencia_r' => 0, 'insan_emergencia_f' => 0,
                    'medida_transitoria_r' => $row[6] ?? 0, 'medida_transitoria_f' => $row[7] ?? 0,
                    'medida_cautelar_r' => $row[8] ?? 0, 'medida_cautelar_f' => $row[9] ?? 0,
                    'total_aa_r' => $row[10] ?? 0, 'total_aa_f' => $row[11] ?? 0,
                    'apa_f' => $row[12] ?? 0, 'apa_huertos' => 0,
                    'reserva_estrategica_r' => $row[13] ?? 0, 'reserva_estrategica_f' => $row[14] ?? 0
                ];
            }

            $departamento = mb_strtoupper(trim((string)($data['departamento'] ?? '')));
            $municipio = ucwords(strtolower(trim((string)($data['municipio'] ?? ''))));

            if (empty($departamento) || empty($municipio)) continue;

            $parseInt = function($val) {
                return intval(preg_replace('/[^\d\-]/', '', (string)$val));
            };

            $fila = [
                'departamento' => $departamento,
                'municipio' => $municipio,
                'nda_severa_r' => $parseInt($data['nda_severa_r'] ?? 0),
                'nda_severa_f' => $parseInt($data['nda_severa_f'] ?? 0),
                'nda_nacional_r' => $parseInt($data['nda_nacional_r'] ?? 0),
                'nda_nacional_f' => $parseInt($data['nda_nacional_f'] ?? 0),
                'nda_plan_abordaje_r' => $parseInt($data['nda_plan_abordaje_r'] ?? 0),
                'nda_plan_abordaje_f' => $parseInt($data['nda_plan_abordaje_f'] ?? 0),
                'insan_r' => $parseInt($data['insan_r'] ?? 0),
                'insan_f' => $parseInt($data['insan_f'] ?? 0),
                'insan_emergencia_r' => $parseInt($data['insan_emergencia_r'] ?? 0),
                'insan_emergencia_f' => $parseInt($data['insan_emergencia_f'] ?? 0),
                'medida_transitoria_r' => $parseInt($data['medida_transitoria_r'] ?? 0),
                'medida_transitoria_f' => $parseInt($data['medida_transitoria_f'] ?? 0),
                'medida_cautelar_r' => $parseInt($data['medida_cautelar_r'] ?? 0),
                'medida_cautelar_f' => $parseInt($data['medida_cautelar_f'] ?? 0),
                'total_aa_r' => $parseInt($data['total_aa_r'] ?? 0),
                'total_aa_f' => $parseInt($data['total_aa_f'] ?? 0),
                'apa_f' => $parseInt($data['apa_f'] ?? 0),
                'apa_huertos' => $parseInt($data['apa_huertos'] ?? 0),
                'reserva_estrategica_r' => $parseInt($data['reserva_estrategica_r'] ?? 0),
                'reserva_estrategica_f' => $parseInt($data['reserva_estrategica_f'] ?? 0),
            ];

            $fila['total_aa_apa'] = isset($data['total_aa_apa']) ? $parseInt($data['total_aa_apa']) : ($fila['total_aa_r'] + $fila['apa_f']);

            try {
                $resultado = $this->repository->upsertDatosImportacion($fila);
                if ($resultado === 'insert') {
                    $insertados++;
                } else {
                    $actualizados++;
                }
            } catch (Exception $e) {
                $errores[] = "Error Fila $i: " . $e->getMessage();
            }
        }

        return [
            'exito' => true,
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'errores' => $errores,
            'total_filas' => $insertados + $actualizados,
            'mensaje' => "$actualizados actualizados, $insertados insertados."
        ];
    }

    // --- DAPCA (Existing) ---
    public function getDapcaData() { return $this->dapcaRepo->getAll(); }
    public function createDapca($data) { return $this->dapcaRepo->create($data); }
    public function updateDapca($id, $data) { return $this->dapcaRepo->update($id, $data); }
    public function deleteDapca($id) { return $this->dapcaRepo->delete($id); }

    // --- SOLICITUDES INDIVIDUALES (Existing) ---
    public function getSolicitudes($filters = [])
    {
        $data = $this->solicitudRepo->getAll($filters);
        return SolicitudDTO::mapCollection($data);
    }
    public function createSolicitud($data) { return $this->solicitudRepo->create($data); }
    public function updateSolicitud($id, $data) { return $this->solicitudRepo->update($id, $data); }
    public function deleteSolicitud($id) { return $this->solicitudRepo->delete($id); }
}
