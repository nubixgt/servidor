<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Attributes\HasPrivilege;
use App\DTOs\ExampleDTO;
use App\Services\ExampleService;



class ExampleController extends Controller
{
    #[Route('/example', 'GET')]
    public function index()
    {
        // Use Service to get data
        $service = new ExampleService();
        $data = $service->getAllExamples();

        $this->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    #[Route('/example', 'POST')]
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $dto = ExampleDTO::fromRequest($data);
        $service = new ExampleService();

        try {
            $service->createExample($dto);
            $this->json(['message' => 'Created successfully'], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/example/protected', 'GET')]
    #[Authorize(['admin', 'user'])]
    public function protectedEndpoint()
    {
        $this->json([
            'message' => 'This is a protected endpoint. You have valid credentials.',
            'user' => 'Extracted from token in a real app'
        ]);
    }

    #[Route('/example/privileged', 'GET')]
    #[HasPrivilege('can_view_reports')]
    public function privilegedEndpoint()
    {
        $this->json(['message' => 'You have the "can_view_reports" privilege.']);
    }
}
