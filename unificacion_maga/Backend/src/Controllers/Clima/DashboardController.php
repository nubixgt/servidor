<?php
namespace App\Controllers\Clima;

use App\Core\Controller;
use App\Attributes\Route;
use App\Services\Clima\OpenWeatherService;
use App\Attributes\HasPrivilege;

#[HasPrivilege('modulo_clima')]
class DashboardController extends Controller
{
    private $weatherService;

    public function __construct()
    {
        $this->weatherService = new OpenWeatherService();
    }

    #[Route('/clima/weather/current', 'GET')]
    public function current()
    {
        try {
            $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 14.6349;
            $lon = isset($_GET['lon']) ? (float)$_GET['lon'] : -90.5069;
            
            $data = $this->weatherService->getCurrentWeather($lat, $lon);
            if (!$data) {
                return $this->json(['status' => 'error', 'message' => 'No se pudo obtener el clima'], 502);
            }
            
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/clima/weather/forecast', 'GET')]
    public function forecast()
    {
        try {
            $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 14.6349;
            $lon = isset($_GET['lon']) ? (float)$_GET['lon'] : -90.5069;
            
            $data = $this->weatherService->getForecast($lat, $lon);
            if (!$data) {
                return $this->json(['status' => 'error', 'message' => 'No se pudo obtener el pronóstico'], 502);
            }
            
            // Agrupar por día (buscando la hora más cercana al mediodía local)
            $pronostico_por_dia = [];
            
            foreach ($data['list'] as $item) {
                // Ajustar a hora de Guatemala (UTC-6)
                $local_time = $item['dt'] - 21600; 
                $fecha = date('Y-m-d', $local_time);
                $hora = (int)date('H', $local_time);
                
                if (!isset($pronostico_por_dia[$fecha])) {
                    $pronostico_por_dia[$fecha] = $item;
                } else {
                    // Preferir la hora más cercana a las 12:00 PM
                    $hora_actual_guardada = (int)date('H', $pronostico_por_dia[$fecha]['dt'] - 21600);
                    if (abs(12 - $hora) < abs(12 - $hora_actual_guardada)) {
                        $pronostico_por_dia[$fecha] = $item;
                    }
                }
            }

            $pronostico = [];
            $hoy = date('Y-m-d', time() - 21600);
            $manana = date('Y-m-d', time() - 21600 + 86400);
            $dias_procesados = 0;

            foreach ($pronostico_por_dia as $fecha => $item) {
                if ($dias_procesados >= 4) break;
                
                if ($fecha == $hoy) {
                    $nombreDia = 'Hoy';
                } elseif ($fecha == $manana) {
                    $nombreDia = 'Mañana';
                } else {
                    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                    $nombreDia = $dias[date('w', $item['dt'] - 21600)];
                }

                $pronostico[] = [
                    'dia' => $nombreDia,
                    'temperatura' => round($item['main']['temp']),
                    'icono' => $this->obtenerIconoEmoji($item['weather'][0]['icon']),
                    'descripcion' => ucfirst($item['weather'][0]['description'])
                ];
                $dias_procesados++;
            }

            $this->json(['status' => 'success', 'data' => ['pronostico' => $pronostico]]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/clima/weather/historial', 'GET')]
    public function historial()
    {
        try {
            $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 14.6349;
            $lon = isset($_GET['lon']) ? (float)$_GET['lon'] : -90.5069;
            
            $data = $this->weatherService->getHistorial($lat, $lon);
            if (!$data) {
                return $this->json(['status' => 'error', 'message' => 'No se pudo obtener el historial'], 502);
            }
            
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function obtenerIconoEmoji($codigo) {
        $iconos = [
            '01d' => '☀️', '01n' => '🌙',
            '02d' => '⛅', '02n' => '☁️',
            '03d' => '☁️', '03n' => '☁️',
            '04d' => '☁️', '04n' => '☁️',
            '09d' => '🌧️', '09n' => '🌧️',
            '10d' => '🌦️', '10n' => '🌧️',
            '11d' => '⛈️', '11n' => '⛈️',
            '13d' => '❄️', '13n' => '❄️',
            '50d' => '🌫️', '50n' => '🌫️',
        ];
        return $iconos[$codigo] ?? '🌤️';
    }
}
