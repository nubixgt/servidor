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

                        // Como se usa la app. Este es el unico punto por el que
                        // pasa CADA peticion de la API, asi que medir aqui
                        // cuesta quince lineas en un sitio en vez de una linea
                        // en cuarenta y tres rutas.
                        //
                        // Se anota con la ruta DECLARADA (`/plan/history/{id}`)
                        // y no con la pedida (`/plan/history/7`): asi las
                        // filas se agrupan solas y no se guarda el id de nadie.
                        //
                        // Va DESPUES de comprobar permisos -- una peticion
                        // rechazada por 401 no es uso -- y DENTRO de un
                        // try/catch vacio: si la analitica falla, se pierde el
                        // evento y la peticion del atleta continua. Pasa de
                        // verdad mientras la migracion 012 no este aplicada,
                        // que en produccion es un paso manual.
                        $this->recordUsage($route->method, $route->path);

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

    /**
     * Anota la peticion si es de las que se miden. NUNCA lanza.
     *
     * El `catch (\Throwable)` vacio es deliberado y es la regla entera de esta
     * funcion: una analitica que puede tumbar una peticion del atleta es peor
     * que no tener analitica. Si falla, se pierde el evento y ya esta.
     *
     * El usuario sale del token cuando lo hay, y sin romper nada cuando no:
     * `/auth/google` se mide y todavia no trae Bearer, asi que ahi el evento
     * se anota sin cuenta. Quien la rellena entonces es el propio
     * AuthController, que es el unico que sabe quien acaba de entrar.
     */
    private function recordUsage(string $method, string $path): void
    {
        try {
            if (\App\Services\EventService::eventFor($method, $path) === null) {
                return;
            }
            $userId = null;
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
                $payload = \App\Utils\JwtUtils::validate($m[1]);
                if (is_array($payload) && isset($payload['sub'])) {
                    $userId = (int) $payload['sub'];
                }
            }
            (new \App\Services\EventService())->recordRequest($userId, $method, $path);
        } catch (\Throwable $e) {
            error_log('Router::recordUsage: ' . $e->getMessage());
        }
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
