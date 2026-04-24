<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// CONFIGURACION
$api_url = "https://muni.vallague.com/api/boletas_api.php";
$api_key = "5f8d93a2-b1e4-4c6d-9a7f-8b2c3d4e5f6a";

// PARAMETROS
$status = $_GET['status'] ?? '';
$getAllPages = isset($_GET['all_pages']) && $_GET['all_pages'] === 'true';
$debug      = isset($_GET['debug'])     && $_GET['debug']     === 'true';

// Completar URLs de imagenes relativas
function processImages($data, $baseUrl = 'https://muni.vallague.com') {
    if (!is_array($data)) return $data;
    $imageFields = ['image','imagen','photo','foto','picture','img','thumbnail',
                    'images','imagenes','photos','fotos','attachments','files'];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = processImages($value, $baseUrl);
        } elseif (is_string($value) && preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i', $value)) {
            if (!preg_match('/^https?:\/\//i', $value))
                $data[$key] = rtrim($baseUrl,'/').'/'.ltrim($value,'/');
        } elseif (in_array(strtolower($key),$imageFields) && is_string($value) && $value!=='') {
            if (!preg_match('/^https?:\/\//i', $value))
                $data[$key] = rtrim($baseUrl,'/').'/'.ltrim($value,'/');
        }
    }
    return $data;
}

// Extraer todas las URLs de imagen de un registro
function extractImageUrls($item, $baseUrl = 'https://muni.vallague.com') {
    $images = [];
    if (!is_array($item)) return $images;
    foreach ($item as $key => $value) {
        if (is_string($value) && preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i', $value)) {
            $url = preg_match('/^https?:\/\//i',$value) ? $value : rtrim($baseUrl,'/').'/'.ltrim($value,'/');
            $images[] = ['field'=>$key,'url'=>$url];
        } elseif (is_array($value)) {
            foreach (extractImageUrls($value,$baseUrl) as $img) {
                $img['field'] = $key.'.'.$img['field'];
                $images[] = $img;
            }
        }
    }
    return $images;
}

// Extraer coordenadas de un registro (misma lógica que extractCoords en app.js)
function extractCoordsFromRecord($b) {
    $la = $b['lat'] ?? $b['latitude'] ?? $b['latitud'] ?? $b['coordenada_lat'] ?? $b['coord_lat'] ?? $b['y'] ?? null;
    if ($la === null && isset($b['location']) && is_array($b['location']))
        $la = $b['location']['lat'] ?? $b['location']['latitude'] ?? null;

    $lo = $b['lng'] ?? $b['lon'] ?? $b['longitude'] ?? $b['longitud'] ?? $b['coordenada_lng'] ?? $b['coord_lng'] ?? $b['x'] ?? null;
    if ($lo === null && isset($b['location']) && is_array($b['location']))
        $lo = $b['location']['lng'] ?? $b['location']['lon'] ?? $b['location']['longitude'] ?? null;

    if (($la === null || $lo === null) && isset($b['coordinates']) && is_array($b['coordinates']) && count($b['coordinates']) >= 2) {
        $lo = $b['coordinates'][0];
        $la = $b['coordinates'][1];
    }

    if (!is_numeric($la) || !is_numeric($lo)) return null;
    $lan = (float)$la; $lon = (float)$lo;
    if ($lan === 0.0 || $lon === 0.0) return null;
    if ($lan < -90 || $lan > 90 || $lon < -180 || $lon > 180) return null;
    return ['lat' => $lan, 'lon' => $lon];
}

// Retorna true si el registro tiene coordenadas FUERA de Guatemala
function isOutsideGuatemala($b) {
    $c = extractCoordsFromRecord($b);
    if ($c === null) return false; // sin coords: se conserva
    return !($c['lat'] >= 13.5 && $c['lat'] <= 18.0 && $c['lon'] >= -92.5 && $c['lon'] <= -88.0);
}

// Llamar a la API
function fetchFromAPI($url, $debug = false) {
    $ch = curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_SSL_VERIFYHOST=>false,
        CURLOPT_TIMEOUT=>30,
        CURLOPT_HTTPHEADER=>["Accept: application/json","User-Agent: Mozilla/5.0"],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response===false) return ["error"=>"cURL Error: $curlErr","url"=>$url];

    $decoded = json_decode($response,true);
    if (json_last_error()!==JSON_ERROR_NONE)
        return ["error"=>"JSON Error: ".json_last_error_msg(),
                "raw_response"=>substr($response,0,500),"http_code"=>$httpCode,"url"=>$url];

    $baseUrl = 'https://muni.vallague.com';
    $listKey = isset($decoded['data']) ? 'data' : (isset($decoded['boletas']) ? 'boletas' : null);
    if ($listKey) {
        foreach ($decoded[$listKey] as $i => $item) {
            $decoded[$listKey][$i] = processImages($item,$baseUrl);
            $imgUrls = extractImageUrls($decoded[$listKey][$i],$baseUrl);
            if ($imgUrls) $decoded[$listKey][$i]['_image_urls'] = $imgUrls;
        }
    } else {
        $decoded = processImages($decoded,$baseUrl);
    }
    if ($debug) $decoded['_debug_info'] = ['http_code'=>$httpCode,'url'=>$url];
    return $decoded;
}

// ── MODO: TODAS LAS PAGINAS ───────────────────────────────────────────────────
if ($getAllPages) {

    $allBoletas  = [];
    $seenIds     = [];
    $currentPage = 1;
    $emptyStreak = 0;

    while (true) {
        // status + page + key siempre en orden fijo
        $url = $api_url
             . "?status="     . urlencode($status)
             . "&page="       . $currentPage
             . "&access_key=" . urlencode($api_key);

        $result = fetchFromAPI($url,$debug);

        if (isset($result['error'])) {
            echo json_encode(["error"=>$result['error'],"page"=>$currentPage]);
            exit;
        }

        $boletas  = $result['data'] ?? $result['boletas'] ?? [];
        $totalApi = $result['total']    ?? null;
        $perPage  = $result['per_page'] ?? null;

        // Pagina vacia
        if (empty($boletas) || !is_array($boletas)) {
            if (++$emptyStreak >= 2) break;
            $currentPage++;
            continue;
        }
        $emptyStreak = 0;

        // Agregar solo registros nuevos (evita duplicados cuando la API repite la ultima pagina)
        $newCount = 0;
        foreach ($boletas as $b) {
            $id = $b['id'] ?? $b['boleta_number'] ?? $b['numero'] ?? $b['folio'] ?? $b['code'] ?? null;
            if ($id === null) {
                $allBoletas[] = $b;
                $newCount++;
            } elseif (!array_key_exists((string)$id, $seenIds)) {
                $seenIds[(string)$id] = true;
                $allBoletas[]         = $b;
                $newCount++;
            }
        }

        // Ninguno nuevo: la API esta repitiendo, salir
        if ($newCount === 0) break;

        // Calcular si ya llegamos a la ultima pagina
        if ($totalApi !== null && $perPage !== null && $perPage > 0) {
            if ($currentPage >= (int) ceil($totalApi / $perPage)) break;
        }

        // Pagina incompleta = ultima pagina
        if ($perPage !== null && count($boletas) < (int)$perPage) break;

        $currentPage++;
        if ($currentPage > 500) break;
    }

    // Excluir registros con coordenadas fuera de Guatemala
    $allBoletas = array_values(array_filter($allBoletas, function($b) {
        return !isOutsideGuatemala($b);
    }));

    echo json_encode([
        "success"       => true,
        "total"         => count($allBoletas),
        "pages_fetched" => $currentPage,
        "data"          => $allBoletas,
    ]);

// ── MODO: UNA SOLA PAGINA ─────────────────────────────────────────────────────
} else {
    $page   = (int)($_GET['page'] ?? 1);
    $url    = $api_url
            . "?status="     . urlencode($status)
            . "&page="       . $page
            . "&access_key=" . urlencode($api_key);
    $result = fetchFromAPI($url, $debug);
    $listKey = isset($result['data']) ? 'data' : (isset($result['boletas']) ? 'boletas' : null);
    if ($listKey) {
        $result[$listKey] = array_values(array_filter($result[$listKey], function($b) {
            return !isOutsideGuatemala($b);
        }));
    }
    echo json_encode($result);
}
