<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Services\UsageService;
use App\Utils\JwtUtils;

class UsageController extends Controller
{
    /**
     * Lo que ha costado la cuenta que hace la petición.
     *
     * El id sale del token y nunca del cuerpo: el gasto es un dato de dinero, y
     * dejar que el cliente diga de quién quiere ver la cuenta sería exactamente
     * la fuga que arreglamos en el estado de generación.
     */
    /**
     * SOLO ADMINISTRADOR desde la beta.
     *
     * No es por privacidad -- cada quien veria solo lo suyo, y el id sale del
     * token. Es porque ensenarle el costo al atleta le CAMBIA lo que hace: a un
     * beta al que se le dijo que pagara el costo exacto de IA, ver el contador
     * subir en cada pregunta le ensena a no preguntar, y entonces se pierde
     * justo el dato de uso que la beta existe para recoger.
     *
     * Se cierra tambien aqui y no solo en la interfaz: esconder un numero en el
     * navegador lo deja a un F12 de distancia, y entonces el atleta que si mira
     * es exactamente el que mas se autolimita.
     *
     * El desglose completo -- por herramienta y por atleta -- esta en
     * /admin/metrics/spending.
     */
    #[Route('/usage', 'GET')]
    #[Authorize(['admin'])]
    public function mine()
    {
        $payload = JwtUtils::fromRequest();
        $data = (new UsageService())->forUser((int) $payload['sub']);
        $this->json(['status' => 'success', 'data' => $data]);
    }
}
