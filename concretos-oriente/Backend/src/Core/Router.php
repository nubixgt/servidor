<?php
namespace App\Core;

use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Attributes\HasPrivilege;
use App\Utils\Response;
use App\Utils\JwtUtils;
use ReflectionClass;
use ReflectionMethod;

class Router
{
    private $controllers = [];

    public function registerController(string $controllerClass)
    {
        $this->controllers[] = $controllerClass;
    }

    public function dispatch($method, $uri)
    {
        // Simple URI matching for now (ignores query params in matching logic)
        $uri = strtok($uri, '?');

        // Strip the base path if we are running in a subdirectory
        $scriptName = $_SERVER['SCRIPT_NAME']; // e.g., /project/api/v1/index.php
        $scriptDir = dirname($scriptName);     // e.g., /project/api/v1

        // Normalize slashes
        $scriptDir = str_replace('\\', '/', $scriptDir);

        // If URI starts with scriptDir, remove it
        if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        // Ensure URI starts with /
        if ($uri === '' || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        foreach ($this->controllers as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);

            // Check Class Level Authorization (Optional, usually we check at method level or both)
            $classAuth = $reflection->getAttributes(Authorize::class);
            // logic for class Level auth could go here...

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $methodRef) {
                $attributes = $methodRef->getAttributes(Route::class);

                foreach ($attributes as $attribute) {
                    $route = $attribute->newInstance();

                    // Check if Method and URI match
                    // This supports parameters like /users/{id} via regex
                    $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_]+)', $route->path);
                    $pattern = "@^" . $pattern . "$@D";

                    if ($route->method === $method && preg_match($pattern, $uri, $matches)) {
                        array_shift($matches); // Remove full match

                        // Handle Authorization (Roles and Granular Permissions)
                        $authAttrs = $methodRef->getAttributes(Authorize::class);
                        if (!empty($authAttrs)) {
                            $auth = $authAttrs[0]->newInstance();
                            $this->checkPermissions($auth->roles, $method, $route->path);
                        }

                        // Handle Privileges (Specific Capabilities)
                        $privAttrs = $methodRef->getAttributes(HasPrivilege::class);
                        if (!empty($privAttrs)) {
                            $priv = $privAttrs[0]->newInstance();
                            $this->checkPrivilege($priv->privilege);
                        }

                        // Instantiate Controller and Call Method
                        $controllerInstance = new $controllerClass();
                        call_user_func_array([$controllerInstance, $methodRef->getName()], $matches);
                        return;
                    }
                }
            }
        }

        // Default: 404 Endpoint Not Found
        // You can make a generic Response class or just echo json
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Endpoint not found"]);
    }

    private function checkPermissions(array $requiredRoles, string $method, string $routePath)
    {
        $payload = $this->validateToken(); // Reuse validation logic

        $userRole = $payload['role'] ?? 'guest';
        $userPermisos = $payload['permisos'] ?? [];

        if (!empty($requiredRoles) && !in_array($userRole, $requiredRoles)) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Forbidden: Insufficient permissions"]);
            exit;
        }

        // Si el usuario tiene permisos granulares configurados
        if (!empty($userPermisos) && is_array($userPermisos)) {
            $moduleKey = $this->getModuleKeyFromUri($routePath);
            if ($moduleKey) {
                $isWrite = in_array(strtoupper($method), ['POST', 'PUT', 'DELETE']);
                $hasEdit = in_array($moduleKey . '_edit', $userPermisos) || in_array($moduleKey, $userPermisos);
                $hasView = in_array($moduleKey . '_view', $userPermisos) || $hasEdit;

                if ($isWrite && !$hasEdit) {
                    http_response_code(403);
                    echo json_encode([
                        "status" => "error",
                        "success" => false,
                        "message" => "Acceso denegado: este perfil no cuenta con permisos de edición para el módulo de $moduleKey."
                    ]);
                    exit;
                }

                if (!$isWrite && !$hasView) {
                    http_response_code(403);
                    echo json_encode([
                        "status" => "error",
                        "success" => false,
                        "message" => "Acceso denegado: este perfil no cuenta con permisos de lectura para el módulo de $moduleKey."
                    ]);
                    exit;
                }
            }
        }
    }

    private function getModuleKeyFromUri(string $uri): ?string
    {
        $uri = trim($uri, '/');
        $segments = explode('/', $uri);
        $first = $segments[0] ?? '';

        $map = [
            'vehicles' => 'vehicles',
            'vehicle-log' => 'vehicles',
            'vehicle-logs' => 'vehicles',
            'personnel' => 'personnel',
            'rrhh-planillas' => 'personnel',
            'rrhh-incidencias' => 'personnel',
            'puestos' => 'personnel',
            'machinery' => 'machinery',
            'heavy-transport' => 'transporte-pesado',
            'special-machinery' => 'maquinaria-especial',
            'fuel-records' => 'combustible',
            'mechanic-records' => 'mecanica',
            'projects' => 'projects',
            'project-incomes' => 'project-incomes',
            'clients' => 'clients',
            'inventory' => 'inventory',
            'suppliers' => 'suppliers',
            'finance' => 'finance',
            'recurrents' => 'recurrents',
            'bank-conciliation' => 'bank-conciliation',
            'bank-statement' => 'bank-conciliation',
            'budgets' => 'budgets-estimations',
            'budget-extensions' => 'budgets-estimations',
            'credits' => 'credits-accounts-payable',
            'documents' => 'digital-documents',
            'alerts' => 'notifications-alerts',
            'payroll' => 'payroll-expenses',
            'concrete-control' => 'concrete-control',
            'viaticos' => 'viaticos',
            'contractors' => 'contractors',
            'users' => 'users',
        ];

        return $map[$first] ?? null;
    }

    private function checkPrivilege(string $requiredPrivilege)
    {
        $payload = $this->validateToken();

        $userPrivileges = $payload['privileges'] ?? [];

        if (!in_array($requiredPrivilege, $userPrivileges)) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Forbidden: Missing privilege '$requiredPrivilege'"]);
            exit;
        }
    }

    private function validateToken()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized: Missing Bearer Token"]);
            exit;
        }

        $token = $matches[1];
        $payload = JwtUtils::validate($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized: Invalid Token"]);
            exit;
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized: Token Expired"]);
            exit;
        }

        return $payload;
    }
}
