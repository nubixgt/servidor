<?php
namespace App\Controllers;

use App\Attributes\HasPrivilege;
use App\Attributes\Route;
use App\Core\Controller;
use App\Core\Request;
use App\Services\TableroService;
use App\Utils\Response;

class TableroController extends Controller
{
    #[Route('/tablero/datos', 'GET')]
    #[HasPrivilege('dashboard.ver')]
    public function datos()
    {
        $d = (new TableroService())->datos($this->user());
        $headers = ['ETag' => $d['etag'], 'Cache-Control' => 'private, no-cache', 'Vary' => 'Authorization, Accept-Encoding'];

        if (Request::header('If-None-Match') === $d['etag']) {
            Response::raw('', $headers, 304);
        }
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        if (str_contains(Request::header('Accept-Encoding'), 'gzip')) {
            $headers['Content-Encoding'] = 'gzip';
            Response::raw($d['gz'], $headers);
        }
        Response::raw(gzdecode($d['gz']), $headers);
    }
}
