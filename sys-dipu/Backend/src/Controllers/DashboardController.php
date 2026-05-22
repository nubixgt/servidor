<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Models\TareaModel;
use App\Models\UsuarioModel;
use App\Utils\Database;
use Exception;
use PDO;

class DashboardController extends Controller
{
    private function getDb()
    {
        return Database::getInstance()->getConnection();
    }

    #[Route('/dashboard/summary', 'GET')]
    public function getSummary()
    {
        try {
            $db = $this->getDb();
            $tareaModel = new TareaModel();
            $usuarioModel = new UsuarioModel();

            // 1. KPIs
            // Tareas Pendientes (estado != 'Completada')
            $stmt = $db->query("SELECT COUNT(*) FROM dashboard_tareas WHERE estado != 'Completada'");
            $tareasPendientes = (int)$stmt->fetchColumn();

            // Tareas Vencidas (estado != 'Completada' y fecha_limite < CURDATE())
            $stmt = $db->query("SELECT COUNT(*) FROM dashboard_tareas WHERE estado != 'Completada' AND fecha_limite < CURDATE()");
            $tareasVencidas = (int)$stmt->fetchColumn();

            // Próximos 7 días (Eventos + Citaciones en los próximos 7 días)
            $stmt = $db->query("SELECT COUNT(*) FROM calendario_eventos WHERE date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
            $eventos7Dias = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM citaciones WHERE fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
            $citaciones7Dias = (int)$stmt->fetchColumn();
            $proximos7Dias = $eventos7Dias + $citaciones7Dias;

            // Fiscalizaciones Activas (Personal de fiscalización)
            $stmt = $db->query("SELECT COUNT(*) FROM fiscalizacion_personal");
            $fiscalizacionesActivas = (int)$stmt->fetchColumn();

            // Atrasos de Prensa (Redes sociales no publicadas con fecha vencida)
            $stmt = $db->query("SELECT COUNT(*) FROM redes_sociales WHERE estado != 'Publicado' AND fecha < CURDATE()");
            $atrasosPrensa = (int)$stmt->fetchColumn();

            // 2. Tendencia de Actividad Semanal (Count of items per day of current week: Mon-Sun)
            $hoy = new \DateTime();
            $diaSemana = (int)$hoy->format('N'); // 1 (Mon) - 7 (Sun)
            $lunes = clone $hoy;
            $lunes->modify('-' . ($diaSemana - 1) . ' days');

            $tendenciaLegislativo = [];
            $tendenciaTerritorial = [];
            
            for ($i = 0; $i < 7; $i++) {
                $fechaStr = $lunes->format('Y-m-d');

                // Legislativo counts (citaciones + comisiones + iniciativas created or scheduled)
                $stmt = $db->prepare("SELECT COUNT(*) FROM citaciones WHERE fecha = :fecha");
                $stmt->execute([':fecha' => $fechaStr]);
                $cCit = (int)$stmt->fetchColumn();

                $stmt = $db->prepare("SELECT COUNT(*) FROM iniciativas WHERE fecha = :fecha");
                $stmt->execute([':fecha' => $fechaStr]);
                $cIni = (int)$stmt->fetchColumn();

                $tendenciaLegislativo[] = $cCit + $cIni;

                // Territorial counts (actividades + compromisos + afiliaciones)
                $stmt = $db->prepare("SELECT COUNT(*) FROM actividades WHERE fecha = :fecha");
                $stmt->execute([':fecha' => $fechaStr]);
                $cAct = (int)$stmt->fetchColumn();

                $stmt = $db->prepare("SELECT COUNT(*) FROM compromisos_distritales WHERE fecha = :fecha");
                $stmt->execute([':fecha' => $fechaStr]);
                $cCom = (int)$stmt->fetchColumn();

                $stmt = $db->prepare("SELECT COUNT(*) FROM afiliaciones_politicas WHERE DATE(created_at) = :fecha");
                $stmt->execute([':fecha' => $fechaStr]);
                $cAfi = (int)$stmt->fetchColumn();

                $tendenciaTerritorial[] = $cAct + $cCom + $cAfi;

                $lunes->modify('+1 day');
            }

            // 3. Tareas por Miembro (Real users effectiveness)
            $users = $usuarioModel->getAll();
            $miembros = [];
            foreach ($users as $u) {
                $stmt = $db->prepare("SELECT 
                    SUM(CASE WHEN estado = 'Completada' THEN 1 ELSE 0 END) as completadas,
                    COUNT(*) as total
                    FROM dashboard_tareas WHERE asignado_a = :userId");
                $stmt->execute([':userId' => $u['id']]);
                $tData = $stmt->fetch(PDO::FETCH_ASSOC);

                $total = (int)($tData['total'] ?? 0);
                $completadas = (int)($tData['completadas'] ?? 0);
                
                $efectividad = $total > 0 ? round(($completadas / $total) * 100) : 80; // default 80 if no tasks assigned
                $miembros[] = [
                    'id' => $u['id'],
                    'nombre_completo' => $u['nombre_completo'],
                    'rol' => $u['rol'],
                    'efectividad' => $efectividad
                ];
            }

            // 4. Compromisos Distribution
            $stmt = $db->query("SELECT estado, COUNT(*) as cantidad FROM compromisos_distritales GROUP BY estado");
            $compData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $compromisos = [
                'En Proceso' => 0,
                'Completados' => 0,
                'Pendientes' => 0,
                'Total' => 0
            ];
            foreach ($compData as $row) {
                if ($row['estado'] === 'En Proceso' || $row['estado'] === 'En Curso') {
                    $compromisos['En Proceso'] += (int)$row['cantidad'];
                } elseif ($row['estado'] === 'Completado' || $row['estado'] === 'Finalizado') {
                    $compromisos['Completados'] += (int)$row['cantidad'];
                } else {
                    $compromisos['Pendientes'] += (int)$row['cantidad'];
                }
                $compromisos['Total'] += (int)$row['cantidad'];
            }

            // 5. Alertas Críticas (Overdue tasks or critical alerts)
            $stmt = $db->query("SELECT t.*, u.nombre_completo as asignado_nombre 
                                FROM dashboard_tareas t 
                                LEFT JOIN usuarios u ON t.asignado_a = u.id 
                                WHERE t.estado != 'Completada' AND t.fecha_limite < CURDATE() 
                                ORDER BY t.fecha_limite ASC LIMIT 4");
            $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If fewer than 4 alerts, supplement with today's citaciones or events
            if (count($alertas) < 4) {
                $stmt = $db->query("SELECT id, citado as titulo, 'Hoy' as asignado_nombre, 'Prioridad Crítica' as descripcion 
                                    FROM citaciones WHERE fecha = CURDATE() LIMIT 2");
                $todayCits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($todayCits as $tc) {
                    $alertas[] = [
                        'id' => $tc['id'],
                        'titulo' => "Citación: " . $tc['titulo'],
                        'descripcion' => $tc['descripcion'],
                        'asignado_nombre' => $tc['asignado_nombre'],
                        'fecha_limite' => date('Y-m-d')
                    ];
                }
            }

            // 6. Agenda Mensual (Events + Citaciones for current month)
            $stmt = $db->query("SELECT id, title, date, category, description 
                                FROM calendario_eventos 
                                WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
                                ORDER BY date ASC LIMIT 10");
            $eventsMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 7. Recent Feed (Activities log)
            $feed = [];

            // Latest political affiliations
            $stmt = $db->query("SELECT nombre_completo as titulo, 'Afiliación' as tipo, created_at FROM afiliaciones_politicas ORDER BY id DESC LIMIT 3");
            $afiFeed = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($afiFeed as $f) {
                $feed[] = [
                    'titulo' => 'Nueva Afiliación',
                    'descripcion' => $f['titulo'] . ' se registró como simpatizante.',
                    'tipo' => 'check',
                    'fecha' => $f['created_at']
                ];
            }

            // Latest tasks completed or created
            $stmt = $db->query("SELECT t.titulo, t.estado, t.created_at, u.nombre_completo 
                                FROM dashboard_tareas t 
                                LEFT JOIN usuarios u ON t.asignado_a = u.id 
                                ORDER BY t.id DESC LIMIT 3");
            $tarFeed = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tarFeed as $t) {
                $feed[] = [
                    'titulo' => $t['estado'] === 'Completada' ? 'Tarea Completada' : 'Nueva Tarea Creada',
                    'descripcion' => ($t['nombre_completo'] ?? 'Alguien') . ($t['estado'] === 'Completada' ? ' completó ' : ' tiene asignado ') . "'{$t['titulo']}'.",
                    'tipo' => $t['estado'] === 'Completada' ? 'check' : 'edit',
                    'fecha' => $t['created_at']
                ];
            }

            // Sort feed by date desc
            usort($feed, function($a, $b) {
                return strcmp($b['fecha'], $a['fecha']);
            });
            $feed = array_slice($feed, 0, 5);

            $this->json([
                'success' => true,
                'data' => [
                    'kpis' => [
                        'tareasPendientes' => $tareasPendientes,
                        'tareasVencidas' => $tareasVencidas,
                        'proximos7Dias' => $proximos7Dias,
                        'fiscalizacionesActivas' => $fiscalizacionesActivas,
                        'atrasosPrensa' => $atrasosPrensa
                    ],
                    'tendencia' => [
                        'legislativo' => $tendenciaLegislativo,
                        'territorial' => $tendenciaTerritorial
                    ],
                    'miembros' => array_slice($miembros, 0, 5),
                    'compromisos' => $compromisos,
                    'alertas' => $alertas,
                    'agenda' => $eventsMes,
                    'feed' => $feed
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error en DashboardController::getSummary: " . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/dashboard/tareas', 'GET')]
    public function getTareas()
    {
        $model = new TareaModel();
        try {
            $data = $model->getAll();
            $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            error_log("Error en DashboardController::getTareas - " . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/dashboard/tareas', 'POST')]
    public function createTarea()
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $model = new TareaModel();
        try {
            $insertId = $model->create($input);
            if ($insertId) {
                $this->json([
                    'success' => true,
                    'message' => 'Tarea creada exitosamente',
                    'data' => ['id' => $insertId]
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo crear la tarea'], 500);
            }
        } catch (Exception $e) {
            error_log("Error en DashboardController::createTarea - " . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/dashboard/tareas/{id}', 'PUT')]
    public function updateTarea($id)
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $model = new TareaModel();
        try {
            $success = $model->update($id, $input);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Tarea actualizada exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo actualizar la tarea'], 500);
            }
        } catch (Exception $e) {
            error_log("Error en DashboardController::updateTarea - " . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/dashboard/tareas/{id}', 'DELETE')]
    public function deleteTarea($id)
    {
        $model = new TareaModel();
        try {
            $success = $model->delete($id);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Tarea eliminada exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo eliminar la tarea'], 500);
            }
        } catch (Exception $e) {
            error_log("Error en DashboardController::deleteTarea - " . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/dashboard/tareas/export/excel', 'GET')]
    public function exportTareasExcel()
    {
        try {
            $model = new TareaModel();
            $tareas = $model->getAll();

            // Set CSV download headers
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="reporte_tareas_sysdipu.csv"');
            
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write headers
            fputcsv($output, ['REPORTE GENERAL DE TAREAS ORGANIZATIVAS - SYSDIPU']);
            fputcsv($output, ['Fecha de Generación', date('Y-m-d H:i:s')]);
            fputcsv($output, []);
            
            fputcsv($output, ['ID', 'Título', 'Descripción', 'Responsable', 'Fecha Límite', 'Prioridad', 'Estado']);
            
            foreach ($tareas as $t) {
                fputcsv($output, [
                    $t['id'],
                    $t['titulo'],
                    $t['descripcion'],
                    $t['asignado_nombre'] ?? 'Sin Asignar',
                    $t['fecha_limite'],
                    $t['prioridad'],
                    $t['estado']
                ]);
            }
            fclose($output);
            exit;
        } catch (Exception $e) {
            error_log("Error en DashboardController::exportTareasExcel - " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/dashboard/tareas/export/pdf', 'GET')]
    public function exportTareasPdf()
    {
        try {
            // Establecer el content type correcto para que el navegador renderice el HTML
            header('Content-Type: text/html; charset=utf-8');

            $model = new TareaModel();
            $tareas = $model->getAll();
            $fechaGen = date('d/m/Y H:i:s');

            // Generate clean HTML printable report
            echo "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Reporte de Tareas - SYSDIPU</title>
                <style>
                    body {
                        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                        color: #333;
                        margin: 40px;
                        line-height: 1.5;
                    }
                    header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-bottom: 2px solid #216170;
                        padding-bottom: 20px;
                        margin-bottom: 30px;
                    }
                    h1 {
                        color: #184e5b;
                        margin: 0;
                        font-size: 24px;
                        font-weight: 800;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                    }
                    .meta {
                        font-size: 12px;
                        color: #666;
                        text-align: right;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
                    th, td {
                        padding: 12px 15px;
                        text-align: left;
                        border-bottom: 1px solid #ddd;
                        font-size: 13px;
                    }
                    th {
                        background-color: #f4f8f9;
                        color: #184e5b;
                        font-weight: bold;
                        text-transform: uppercase;
                        font-size: 11px;
                        letter-spacing: 0.5px;
                    }
                    tr:hover {
                        background-color: #f9f9f9;
                    }
                    .badge {
                        padding: 4px 8px;
                        border-radius: 12px;
                        font-size: 10px;
                        font-weight: bold;
                        text-transform: uppercase;
                        display: inline-block;
                    }
                    .badge-pendiente { background-color: #e2e8f0; color: #475569; }
                    .badge-proceso { background-color: #dbeafe; color: #1e40af; }
                    .badge-revision { background-color: #fef3c7; color: #92400e; }
                    .badge-completada { background-color: #dcfce7; color: #166534; }
                    .badge-vencida { background-color: #fee2e2; color: #991b1b; }
                    .badge-critica { background-color: #fecaca; color: #990000; }
                    .badge-alta { background-color: #ffedd5; color: #c2410c; }
                    .badge-media { background-color: #ecfdf5; color: #047857; }
                    .badge-baja { background-color: #f3f4f6; color: #374151; }
                </style>
            </head>
            <body>
                <header>
                    <div>
                        <h1>Reporte General de Tareas</h1>
                        <p style='margin: 5px 0 0 0; font-size: 13px; color: #555;'>Sistema Legislativo SYSDIPU</p>
                    </div>
                    <div class='meta'>
                        <p><strong>Fecha Generación:</strong> {$fechaGen}</p>
                        <p><strong>Total Tareas:</strong> " . count($tareas) . "</p>
                    </div>
                </header>
                <table>
                    <thead>
                        <tr>
                            <th style='width: 5%;'>ID</th>
                            <th style='width: 30%;'>Tarea</th>
                            <th style='width: 25%;'>Responsable</th>
                            <th style='width: 15%;'>Fecha Límite</th>
                            <th style='width: 12%;'>Prioridad</th>
                            <th style='width: 13%;'>Estado</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($tareas as $t) {
                $statusClass = strtolower(str_replace(' ', '', $t['estado']));
                $prioClass = strtolower(str_replace('í', 'i', $t['prioridad']));
                
                // If overdue and not completed, mark as vencida virtually for the print view
                $isOverdue = ($t['estado'] !== 'Completada' && strtotime($t['fecha_limite']) < strtotime(date('Y-m-d')));
                $displayStatus = $isOverdue ? 'Vencida' : $t['estado'];
                $displayStatusClass = $isOverdue ? 'vencida' : $statusClass;
                
                $resp = htmlspecialchars($t['asignado_nombre'] ?? 'Sin Asignar');
                $titulo = htmlspecialchars($t['titulo']);
                $desc = htmlspecialchars($t['descripcion'] ?? '');
                
                echo "
                        <tr>
                            <td>{$t['id']}</td>
                            <td>
                                <strong>{$titulo}</strong><br>
                                <span style='font-size: 11px; color: #777;'>{$desc}</span>
                            </td>
                            <td>{$resp}</td>
                            <td>" . date('d/m/Y', strtotime($t['fecha_limite'])) . "</td>
                            <td><span class='badge badge-{$prioClass}'>{$t['prioridad']}</span></td>
                            <td><span class='badge badge-{$displayStatusClass}'>{$displayStatus}</span></td>
                        </tr>";
            }
            
            echo "
                    </tbody>
                </table>
                <script>
                    window.onload = function() {
                        window.print();
                    }
                </script>
            </body>
            </html>";
            exit;
        } catch (Exception $e) {
            error_log("Error en DashboardController::exportTareasPdf - " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/dashboard/export', 'GET')]
    public function exportCSV()
    {
        try {
            $db = $this->getDb();
            
            // Retrieve general summary statistics
            $stmt = $db->query("SELECT COUNT(*) FROM afiliaciones_politicas");
            $totalAfiliados = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT COUNT(*) FROM citaciones");
            $totalCitaciones = $stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM iniciativas");
            $totalIniciativas = $stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM comisiones");
            $totalComisiones = $stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM redes_sociales");
            $totalRedes = $stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM dashboard_tareas WHERE estado = 'Completada'");
            $tareasCompletadas = $stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM dashboard_tareas WHERE estado != 'Completada'");
            $tareasPendientes = $stmt->fetchColumn();

            // Set CSV download headers
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="reporte_dashboard_sysdipu.csv"');
            
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write titles and general KPIs
            fputcsv($output, ['REPORTE EJECUTIVO DE GESTIÓN SEMANAL - SYSDIPU']);
            fputcsv($output, ['Fecha de Generación', date('Y-m-d H:i:s')]);
            fputcsv($output, []);
            
            fputcsv($output, ['INDICADOR DE RENDIMIENTO', 'CANTIDAD / VALOR']);
            fputcsv($output, ['Afiliados Políticos Activos', $totalAfiliados]);
            fputcsv($output, ['Citaciones Programadas', $totalCitaciones]);
            fputcsv($output, ['Iniciativas de Ley presentadas', $totalIniciativas]);
            fputcsv($output, ['Comisiones Legislativas activas', $totalComisiones]);
            fputcsv($output, ['Publicaciones en Redes Sociales', $totalRedes]);
            fputcsv($output, ['Tareas Completadas', $tareasCompletadas]);
            fputcsv($output, ['Tareas Pendientes / En Proceso', $tareasPendientes]);
            
            fputcsv($output, []);
            fputcsv($output, ['LISTADO DE TAREAS ORGANIZATIVAS']);
            fputcsv($output, ['ID', 'Título', 'Asignado A', 'Fecha Límite', 'Prioridad', 'Estado']);
            
            $stmt = $db->query("SELECT t.id, t.titulo, u.nombre_completo, t.fecha_limite, t.prioridad, t.estado 
                                FROM dashboard_tareas t 
                                LEFT JOIN usuarios u ON t.asignado_a = u.id 
                                ORDER BY t.fecha_limite ASC");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}
