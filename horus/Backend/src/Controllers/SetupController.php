<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Utils\Database;
use PDO;

class SetupController extends Controller
{
    #[Route('/setup', 'GET')]
    public function initAdmin()
    {
        $pdo = Database::getInstance()->getConnection();
        
        // Ensure table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            rol VARCHAR(50) DEFAULT 'admin',
            activo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Check if admin exists
        $stmt = $pdo->query("SELECT id FROM usuarios WHERE username = 'admin'");
        if ($stmt->fetch()) {
            $this->json(['message' => 'Admin user already exists']);
            return;
        }

        // Create admin user
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, rol) VALUES ('admin', :password, 'admin')");
        $stmt->execute(['password' => $hash]);

        $this->json(['message' => 'Admin user created. Username: admin, Password: admin123']);
    }
}
