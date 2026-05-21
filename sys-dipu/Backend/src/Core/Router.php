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
        // Strip query string
        $uri = strtok($uri, '?');

        // Strip the base path only when running under Apache/Nginx (not PHP built-in server).
        // In PHP's built-in server, SCRIPT_NAME equals REQUEST_URI, so dirname()
        // would incorrectly strip a real path segment (e.g. /auth from /auth/login).
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // Only strip base path when SCRIPT_NAME ends with .php (real script, not spoofed by built-in server)
        if (str_ends_with($scriptName, '.php') && $scriptName !== $requestUri) {
            $scriptDir = dirname(str_replace('\\', '/', $scriptName));
            if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
                $uri = substr($uri, strlen($scriptDir));
            }
        }

        // Ensure URI starts with /
        if ($uri === '' || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }


        foreach ($this->controllers as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);
            $classAuth = $reflection->getAttributes(Authorize::class);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $methodRef) {
                $attributes = $methodRef->getAttributes(Route::class);

                foreach ($attributes as $attribute) {
                    $route = $attribute->newInstance();
                    $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_]+)', $route->path);
                    $pattern = "@^" . $pattern . "$@D";

                    // 🔍 DEBUG TEMPORAL
                    error_log("Checking route: [{$route->method}] {$route->path} | Pattern: {$pattern} | URI: {$uri} | Method: {$method}");

                    if ($route->method === $method && preg_match($pattern, $uri, $matches)) {
                        array_shift($matches);
                        $authAttrs = $methodRef->getAttributes(Authorize::class);
                        if (!empty($authAttrs)) {
                            $auth = $authAttrs[0]->newInstance();
                            $this->checkPermissions($auth->roles);
                        }
                        $privAttrs = $methodRef->getAttributes(HasPrivilege::class);
                        if (!empty($privAttrs)) {
                            $priv = $privAttrs[0]->newInstance();
                            $this->checkPrivilege($priv->privilege);
                        }
                        $controllerInstance = new $controllerClass();
                        call_user_func_array([$controllerInstance, $methodRef->getName()], $matches);
                        return;
                    }
                }
            }
        }

        // Default: 404 Endpoint Not Found
        error_log(">>> 404 - No route matched for [{$method}] {$uri}");
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
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader)) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            }
        }

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
