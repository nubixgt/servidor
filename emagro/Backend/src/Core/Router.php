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

                        // Handle Authorization (Roles)
                        $authAttrs = $methodRef->getAttributes(Authorize::class);
                        if (!empty($authAttrs)) {
                            $auth = $authAttrs[0]->newInstance();
                            $this->checkPermissions($auth->roles);
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

    private function checkPermissions(array $requiredRoles)
    {
        if (empty($requiredRoles))
            return;

        $payload = $this->validateToken(); // Reuse validation logic

        $userRole = $payload['role'] ?? 'guest';

        if (!in_array($userRole, $requiredRoles)) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Forbidden: Insufficient permissions"]);
            exit;
        }
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
