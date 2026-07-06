<?php
namespace App\Services\Dashboard;

use App\Repositories\ActividadesDespacho\ActividadRepository;
use App\Repositories\Votaciones\EventoRepository;
use App\Repositories\Votaciones\CongresistaRepository;
use App\Repositories\Votaciones\BloqueRepository;
use App\Repositories\Votaciones\VotoRepository;
use App\Repositories\VISAR\InspeccionRepository;
use App\Repositories\VISAR\LicenciaRepository;
use App\Repositories\VISAN\AsistenciaRepository;
use App\Repositories\VIDER\ViderRepository;
use App\Utils\Database;

class DashboardService
{
    private $actividadRepo;
    private $eventoRepo;
    private $congresistaRepo;
    private $bloqueRepo;
    private $votoRepo;
    private $inspeccionRepo;
    private $licenciaRepo;
    private $visanRepo;
    private $viderRepo;

    public function __construct()
    {
        $this->actividadRepo = new ActividadRepository();
        $this->eventoRepo = new EventoRepository();
        $this->congresistaRepo = new CongresistaRepository();
        $this->bloqueRepo = new BloqueRepository();
        $this->votoRepo = new VotoRepository();
        $this->inspeccionRepo = new InspeccionRepository();
        $this->licenciaRepo = new LicenciaRepository();
        $this->visanRepo = new AsistenciaRepository();
        $this->viderRepo = new ViderRepository();
    }

    public function getGlobalStats()
    {
        return [
            'despacho' => $this->getDespachoStats(),
            'votaciones' => $this->getVotacionesStats(),
            'visar' => $this->getVisarStats(),
            'visan' => $this->getVisanStats(),
            'vider' => $this->getViderStats(),
            'registro_climatologico' => $this->getRegistroClimatologicoStats()
        ];
    }

    private function getRegistroClimatologicoStats()
    {
        try {
            $db = Database::getInstance()->getConnection();

            $stmtAlertas = $db->query("SELECT COUNT(*) as total FROM clima_alertas");
            $alertasTotal = (int) $stmtAlertas->fetchColumn();

            $stmtActivas = $db->query("SELECT COUNT(*) as activas FROM clima_alertas WHERE estado = 'Activa'");
            $activas = (int) $stmtActivas->fetchColumn();

            $stmtUsuarios = $db->query("SELECT COUNT(*) as total_usuarios FROM clima_usuarios");
            $usuarios = (int) $stmtUsuarios->fetchColumn();

            $stmtRegiones = $db->query("SELECT COUNT(DISTINCT region) as regiones FROM clima_alertas");
            $regiones = (int) $stmtRegiones->fetchColumn();

            return [
                'alertas_total' => $alertasTotal,
                'activas' => $activas,
                'usuarios' => $usuarios,
                'regiones_monit' => $regiones
            ];
        } catch (\Throwable $e) {
            return ['alertas_total' => 0, 'activas' => 0, 'usuarios' => 0, 'regiones_monit' => 0];
        }
    }

    private function getDespachoStats()
    {
        try {
            $stats = $this->actividadRepo->getSummaryStats();
            return [
                'total' => (int) ($stats['total'] ?? 0),
                'criticas' => (int) ($stats['criticas'] ?? 0),
                'en_progreso' => (int) ($stats['en_progreso'] ?? 0),
                'completadas' => (int) ($stats['completadas'] ?? 0)
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'criticas' => 0, 'en_progreso' => 0, 'completadas' => 0];
        }
    }

    private function getVotacionesStats()
    {
        try {
            $summary = $this->eventoRepo->getSummary();
            return [
                'eventos' => (int) ($summary['eventos'] ?? 0),
                'congresistas' => (int) ($summary['congresistas'] ?? 0),
                'bloques' => (int) ($summary['bloques'] ?? 0),
                'votos_reg' => (int) ($summary['votos'] ?? 0)
            ];
        } catch (\Throwable $e) {
            return ['eventos' => 0, 'congresistas' => 0, 'bloques' => 0, 'votos_reg' => 0];
        }
    }

    private function getVisarStats()
    {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Exportaciones
            $stmtExp = $db->query("SELECT COUNT(*) as total, SUM(valor_fob) as fob FROM visar_exportaciones");
            $rowExp = $stmtExp->fetch(\PDO::FETCH_ASSOC);
            $exportaciones = (int)($rowExp['total'] ?? 0);
            $fob = (float)($rowExp['fob'] ?? 0);

            // Importaciones
            $stmtImp = $db->query("SELECT COUNT(*) as total, SUM(valor_dolares) as cif FROM visar_importaciones");
            $rowImp = $stmtImp->fetch(\PDO::FETCH_ASSOC);
            $importaciones = (int)($rowImp['total'] ?? 0);
            $cif = (float)($rowImp['cif'] ?? 0);

            // Transporte
            $stmtTrans = $db->query("SELECT COUNT(*) as total FROM visar_licencias_transporte");
            $rowTrans = $stmtTrans->fetch(\PDO::FETCH_ASSOC);
            $transporte = (int)($rowTrans['total'] ?? 0);

            // Fitosanitarias
            $stmtFito = $db->query("SELECT COUNT(*) as total FROM visar_licencias_fitosanitarias");
            $rowFito = $stmtFito->fetch(\PDO::FETCH_ASSOC);
            $fito = (int)($rowFito['total'] ?? 0);

            return [
                'exportaciones'   => $exportaciones,
                'importaciones'   => $importaciones,
                'lic_transporte'  => $transporte,
                'lic_fitosanitar' => $fito,
                'valor_fob'       => number_format($fob / 1000000, 1) . 'M',
                'valor_cif'       => number_format($cif / 1000000, 1) . 'M',
                'inspecciones'    => 0
            ];
        } catch (\Throwable $e) {
            return [
                'exportaciones'   => 0,
                'importaciones'   => 0,
                'lic_transporte'  => 0,
                'lic_fitosanitar' => 0,
                'valor_fob'       => '0.0M',
                'valor_cif'       => '0.0M',
                'inspecciones'    => 0
            ];
        }
    }

    private function getVisanStats()
    {
        try {
            $stats = $this->visanRepo->getEstadisticasGenerales([]);
            return [
                'asistencia_alimentaria' => (int)($stats['total_aa_r'] ?? 0),
                'alimentos_por_acciones' => (int)($stats['total_apa_f'] ?? 0),
                'total_raciones_aa' => (int)($stats['total_aa_apa'] ?? 0),
                'reserva_estrategica' => (int)($stats['reserva_estrategica_r'] ?? 0)
            ];
        } catch (\Throwable $e) {
            return ['asistencia_alimentaria' => 0, 'alimentos_por_acciones' => 0, 'total_raciones_aa' => 0, 'reserva_estrategica' => 0];
        }
    }

    private function getViderStats()
    {
        try {
            $stats = $this->viderRepo->getDashboardStats();
            return [
                'beneficiarios' => (int)($stats['total_beneficiarios'] ?? 0),
                'ejecutado'     => (float)($stats['total_ejecutado'] ?? 0),
                'departamentos' => (int)($stats['total_departamentos'] ?? 0),
                'financiero'    => (float)($stats['total_financiero_ejecutado'] ?? 0)
            ];
        } catch (\Throwable $e) {
            return ['beneficiarios' => 0, 'ejecutado' => 0, 'departamentos' => 0, 'financiero' => 0];
        }
    }
}
