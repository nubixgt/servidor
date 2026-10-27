<?php
namespace App\Controllers;

use App\Attributes\Route;
use App\Core\Controller;
use App\Utils\Database;

class SaludController extends Controller
{
    #[Route('/salud', 'GET')]
    public function salud()
    {
        Database::getInstance()->scalar('SELECT 1');
        $this->json(['estado' => 'ok']);
    }
}
