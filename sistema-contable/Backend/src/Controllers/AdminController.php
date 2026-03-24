<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Attributes\HasPrivilege;
use App\Services\AdminService;
use App\Utils\JwtUtils;

class AdminController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new AdminService();
    }

    private function getUserId()
    {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        $payload = JwtUtils::validate($token);
        return $payload ? $payload['id'] : null;
    }

    #[Route('/admin/dashboard', 'GET')]
    #[Authorize(['admin'])]
    #[HasPrivilege('view_dashboard_admin')]
    public function getDashboard()
    {
        $data = $this->service->getDashboardData();
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/locations', 'GET')]
    #[Authorize(['admin', 'tech'])]
    #[HasPrivilege('view_locations')]
    public function getLocations()
    {
        $data = $this->service->getLocations();
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/locations', 'POST')]
    #[Authorize(['admin', 'tech'])]
    #[HasPrivilege('view_locations')]
    public function createLocation()
    {
        // Using $_POST because we will receive multipart/form-data for file uploads
        $data = $_POST;
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/locations/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filename = uniqid() . '_' . basename($_FILES['photo']['name']);
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                $data['photo_path'] = '/uploads/locations/' . $filename;
            }
        }

        try {
            $id = $this->service->createLocation($data);
            $this->json(['status' => 'success', 'message' => 'Propiedad creada', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/locations/{id}', 'POST')]
    #[Authorize(['admin', 'tech'])]
    #[HasPrivilege('view_locations')]
    public function updateLocation($id)
    {
        $data = $_POST;
        $location = $this->service->getLocationById($id);
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            // Delete old photo if it exists
            if ($location && !empty($location['photo_path'])) {
                $oldFile = __DIR__ . '/../../' . ltrim($location['photo_path'], '/');
                if (file_exists($oldFile) && is_file($oldFile)) {
                    unlink($oldFile);
                }
            }
            
            $uploadDir = __DIR__ . '/../../uploads/locations/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filename = uniqid() . '_' . basename($_FILES['photo']['name']);
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                $data['photo_path'] = '/uploads/locations/' . $filename;
            }
        }

        try {
            $this->service->updateLocation($id, $data);
            $this->json(['status' => 'success', 'message' => 'Propiedad actualizada'], 200);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/locations/{id}', 'DELETE')]
    #[Authorize(['admin', 'tech'])]
    #[HasPrivilege('view_locations')]
    public function deleteLocation($id)
    {
        try {
            $location = $this->service->getLocationById($id);
            $this->service->deleteLocation($id);
            
            // Si la eliminación en base de datos es exitosa, removemos el archivo físico
            if ($location && !empty($location['photo_path'])) {
                $oldFile = __DIR__ . '/../../' . ltrim($location['photo_path'], '/');
                if (file_exists($oldFile) && is_file($oldFile)) {
                    unlink($oldFile);
                }
            }

            $this->json(['status' => 'success', 'message' => 'Propiedad eliminada'], 200);
        } catch (\PDOException $e) {
            // Usually error code 23000 indicates a foreign key constraint failure
            if ($e->getCode() == 23000) {
                $this->json(['error' => 'No se puede eliminar la propiedad porque tiene transacciones o activos enlazados.'], 400);
            } else {
                $this->json(['error' => 'Ocurrió un error en la base de datos al eliminar.'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/reports', 'GET')]
    #[Authorize(['admin'])]
    #[HasPrivilege('view_reports')]
    public function getReports()
    {
        $data = $this->service->getReports();
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/users', 'GET')]
    #[Authorize(['admin'])]
    #[HasPrivilege('manage_users')]
    public function getUsers()
    {
        $data = $this->service->getUsers();
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/users', 'POST')]
    #[Authorize(['admin'])]
    #[HasPrivilege('manage_users')]
    public function createUser()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $id = $this->service->createUser($data);
            $this->json(['status' => 'success', 'message' => 'Usuario creado exitosamente', 'id' => $id], 201);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                // Posible duplicación de llaves, e.g., username o email unique
                $this->json(['error' => 'El nombre de usuario o correo electrónico ya existe.'], 400);
            } else {
                $this->json(['error' => 'Ocurrió un error en la base de datos al crear el usuario.'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/users/{id}', 'PUT')]
    #[Authorize(['admin'])]
    #[HasPrivilege('manage_users')]
    public function updateUser($id)
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $this->service->updateUser($id, $data);
            $this->json(['status' => 'success', 'message' => 'Usuario actualizado correctamente'], 200);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $this->json(['error' => 'El nombre de usuario o correo electrónico ya existe en oro registro.'], 400);
            } else {
                $this->json(['error' => 'Ocurrió un error en la base de datos al actualizar el usuario.'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/users/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    #[HasPrivilege('manage_users')]
    public function deleteUser($id)
    {
        try {
            $this->service->deleteUser($id);
            $this->json(['status' => 'success', 'message' => 'Usuario eliminado permanentemente'], 200);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $this->json(['error' => 'No se puede eliminar el usuario porque ha registrado transacciones o activos. Por favor interviene cambiando su Estado a "Inactivo" en la edición.'], 400);
            } else {
                $this->json(['error' => 'Ocurrió un error en la base de datos al eliminar.'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/transactions', 'POST')]
    #[Authorize(['admin', 'tech'])]
    #[HasPrivilege('create_financial_transaction')]
    public function createTransaction()
    {
        // Use $_POST because we receive multipart/form-data (supports file upload)
        $data = $_POST;
        $userId = $this->getUserId();
        
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        $payload = JwtUtils::validate($token);

        if (!$userId) {
            $this->json(['error' => 'No autorizado'], 401);
            return;
        }

        // If location is missing and user is tech, use their assigned location
        if (empty($data['location_id']) && $payload && $payload['role'] === 'tech') {
            $data['location_id'] = $payload['location_id'];
        }

        if (empty($data['location_id'])) {
            $this->json(['error' => 'No se ha provisto una locación y el usuario no tiene una asignada.'], 400);
            return;
        }

        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/transactions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = uniqid() . '_' . basename($_FILES['receipt']['name']);
            $destination = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $destination)) {
                $data['receipt_path'] = '/uploads/transactions/' . $filename;
            }
        }

        try {
            $id = $this->service->createTransaction($data, $userId);
            $this->json(['status' => 'success', 'message' => 'Transacción registrada', 'id' => $id], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/transactions', 'GET')]
    #[Authorize(['admin'])]
    #[HasPrivilege('view_dashboard_admin')]
    public function getTransactions()
    {
        $data = $this->service->getAllTransactions();
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/transactions/{id}', 'GET')]
    #[Authorize(['admin'])]
    #[HasPrivilege('view_dashboard_admin')]
    public function getTransaction($id)
    {
        $data = $this->service->getTransactionById($id);
        if (!$data) {
            $this->json(['error' => 'Transacción no encontrada'], 404);
            return;
        }
        $this->json(['status' => 'success', 'data' => $data]);
    }

    #[Route('/transactions/{id}', 'PUT')]
    #[Authorize(['admin'])]
    #[HasPrivilege('view_dashboard_admin')]
    public function updateTransaction($id)
    {
        // Supports both JSON (for status-only updates) and multipart (for full edit with receipt)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'multipart/form-data')) {
            $data = $_POST;
        } else {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
        }

        // Handle new receipt upload
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            // Delete old receipt if exists
            $existing = $this->service->getTransactionById($id);
            if ($existing && !empty($existing['receipt_path'])) {
                $oldFile = __DIR__ . '/../../' . ltrim($existing['receipt_path'], '/');
                if (file_exists($oldFile) && is_file($oldFile)) unlink($oldFile);
            }

            $uploadDir = __DIR__ . '/../../uploads/transactions/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $filename = uniqid() . '_' . basename($_FILES['receipt']['name']);
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadDir . $filename)) {
                $data['receipt_path'] = '/uploads/transactions/' . $filename;
            }
        }

        try {
            $this->service->updateTransaction($id, $data);
            $this->json(['status' => 'success', 'message' => 'Transacción actualizada']);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/transactions/{id}', 'DELETE')]
    #[Authorize(['admin'])]
    #[HasPrivilege('view_dashboard_admin')]
    public function deleteTransaction($id)
    {
        $trx = $this->service->getTransactionById($id);

        // Delete associated receipt file
        if ($trx && !empty($trx['receipt_path'])) {
            $file = __DIR__ . '/../../' . ltrim($trx['receipt_path'], '/');
            if (file_exists($file) && is_file($file)) unlink($file);
        }

        try {
            $this->service->deleteTransaction($id);
            $this->json(['status' => 'success', 'message' => 'Transacción eliminada']);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
