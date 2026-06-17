<?php
namespace App\Services;

use App\Repositories\UserRepository;
use Exception;

class UserService
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function createUser(array $data, ?array $fotoFile): void
    {
        if (empty($data['nombre']) || empty($data['usuario']) || empty($data['passwordRaw'])) {
            throw new Exception('Nombre, usuario y contraseña son obligatorios', 400);
        }

        if ($this->userRepository->findByUsuario($data['usuario'])) {
            throw new Exception('El nombre de usuario ya está en uso', 400);
        }

        $data['password'] = password_hash($data['passwordRaw'], PASSWORD_DEFAULT);

        $newId = $this->userRepository->create($data);

        if ($fotoFile && $fotoFile['error'] === UPLOAD_ERR_OK) {
            $baseDir = __DIR__ . "/../../Uploads/Users/$newId";
            if (!file_exists($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            $fotoExt = pathinfo($fotoFile['name'], PATHINFO_EXTENSION);
            $fotoName = "foto_" . time() . ".$fotoExt";
            $fotoPath = "$baseDir/$fotoName";
            
            if (move_uploaded_file($fotoFile['tmp_name'], $fotoPath)) {
                $this->userRepository->updateFoto($newId, "Uploads/Users/$newId/$fotoName");
            }
        }
    }

    public function updateUser(int $id, array $data, ?array $fotoFile): void
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new Exception('Usuario no encontrado', 404);
        }

        $data['nombre'] = $data['nombre'] ?? $user['nombre'];
        $data['usuario'] = $data['usuario'] ?? $user['usuario'];
        $data['rol'] = $data['rol'] ?? $user['rol'];
        $data['estado'] = $data['estado'] ?? $user['estado'];
        
        if (array_key_exists('permisos', $data)) {
            // Keep it as is (can be null or string)
        } else {
            $data['permisos'] = $user['permisos'] ?? null;
        }
        
        if (array_key_exists('proyectos', $data)) {
            // Keep it as is
        } else {
            $data['proyectos'] = $user['proyectos'] ?? null;
        }
        
        if ($data['usuario'] !== $user['usuario']) {
            if ($this->userRepository->findByUsuario($data['usuario'])) {
                throw new Exception('El nombre de usuario ya está en uso', 400);
            }
        }

        if (!empty($data['passwordRaw'])) {
            $data['password'] = password_hash($data['passwordRaw'], PASSWORD_DEFAULT);
        } else {
            $data['password'] = null;
        }

        $this->userRepository->update($id, $data);

        if ($fotoFile && $fotoFile['error'] === UPLOAD_ERR_OK) {
            $baseDir = __DIR__ . "/../../Uploads/Users/$id";
            if (!file_exists($baseDir)) {
                mkdir($baseDir, 0777, true);
            }

            if (!empty($user['foto']) && file_exists(__DIR__ . "/../../" . $user['foto'])) {
                unlink(__DIR__ . "/../../" . $user['foto']);
            }

            $fotoExt = pathinfo($fotoFile['name'], PATHINFO_EXTENSION);
            $fotoName = "foto_" . time() . ".$fotoExt";
            $fotoPath = "$baseDir/$fotoName";
            
            if (move_uploaded_file($fotoFile['tmp_name'], $fotoPath)) {
                $this->userRepository->updateFoto($id, "Uploads/Users/$id/$fotoName");
            }
        }
    }

    public function deleteUser(int $id): void
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new Exception('Usuario no encontrado', 404);
        }

        $this->userRepository->delete($id);

        $dirPath = __DIR__ . "/../../Uploads/Users/$id";
        if (is_dir($dirPath)) {
            $this->deleteDirectory($dirPath);
        }
    }

    private function deleteDirectory(string $dir): bool 
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        
        return rmdir($dir);
    }
}
