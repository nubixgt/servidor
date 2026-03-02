<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Attributes\HasPrivilege;
use App\Services\UserService;
use App\Utils\Response;

class UserController extends Controller
{
    #[Route('/users', 'GET')]
    #[Authorize(['Administrador', 'Vendedor'])]
    #[HasPrivilege('manage_users')]
    public function index()
    {
        $service = new UserService();
        $users = $service->getAllUsers();
        Response::success($users);
    }
}
