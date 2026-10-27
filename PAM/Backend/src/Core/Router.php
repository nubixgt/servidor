<?php
namespace App\Core;

use App\Attributes\Route;
use App\Attributes\Authorize;
use App\Attributes\HasPrivilege;
use App\Services\AuthService;
use App\Utils\Database;
use App\Utils\Response;
use App\Utils\JwtUtils;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

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

        // Una barra final no cambia la ruta
        $uri = rtrim($uri, '/') ?: '/';

        try {
            foreach ($this->controllers as $controllerClass) {
                $reflection = new ReflectionClass($controllerClass);

                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $methodRef) {
                    $attributes = $methodRef->getAttributes(Route::class);

                    foreach ($attributes as $attribute) {
                        $route = $attribute->newInstance();

                        // Check if Method and URI match
                        // This supports parameters like /users/{id} via regex
                        if ($route->method === $method && preg_match($this->compile($route->path), $uri, $matches)) {
                            array_shift($matches); // Remove full match

                            $this->authorize($reflection, $methodRef);

                            // Instantiate Controller and Call Method
                            $controllerInstance = new $controllerClass();
                            call_user_func_array([$controllerInstance, $methodRef->getName()], $matches);
                            return;
                        }
                    }
                }
            }
        } catch (HttpException $e) {
            Response::error($e->getMessage(), $e->status, $e->errors);
        } catch (Throwable $e) {
            error_log($e);
            $produccion = (require __DIR__ . '/../../config/app.php')['entorno'] === 'produccion';
            Response::error($produccion ? 'Error interno del servidor' : $e->getMessage(), 500);
        }

        // Default: 404 Endpoint Not Found
        Response::error('Endpoint not found', 404);
    }

    /** /expedientes/{id}/dpi -> expresión regular con un grupo por parámetro. */
    private function compile(string $path): string
    {
        $parts = preg_split('/(\{[a-zA-Z0-9_]+\})/', $path, -1, PREG_SPLIT_DELIM_CAPTURE);
        $pattern = '';
        foreach ($parts as $part) {
            $pattern .= preg_match('/^\{[a-zA-Z0-9_]+\}$/', $part) ? '([a-zA-Z0-9_]+)' : preg_quote($part, '@');
        }
        return "@^" . $pattern . "$@D";
    }

    /**
     * #[Authorize] (clase o método): sesión válida y, si indica roles, uno de ellos.
     * #[HasPrivilege] (clase o método): al menos uno de los permisos; además bloquea a quien debe cambiar su contraseña.
     * El atributo del método tiene prioridad sobre el de la clase.
     */
    private function authorize(ReflectionClass $class, ReflectionMethod $method): void
    {
        $authAttrs = $method->getAttributes(Authorize::class) ?: $class->getAttributes(Authorize::class);
        $privAttrs = $method->getAttributes(HasPrivilege::class) ?: $class->getAttributes(HasPrivilege::class);

        if (empty($authAttrs) && empty($privAttrs)) {
            return;
        }

        $user = $this->authenticate();

        if (!empty($authAttrs)) {
            $roles = $authAttrs[0]->newInstance()->roles;
            if (!empty($roles) && !in_array($user->rol, $roles, true)) {
                throw new HttpException('Forbidden: Insufficient permissions', 403);
            }
        }

        if (!empty($privAttrs)) {
            if ($user->debeCambiarClave) {
                throw new HttpException('Debe cambiar su contraseña antes de continuar', 403);
            }
            $privileges = $privAttrs[0]->newInstance()->privileges;
            foreach ($privileges as $privilege) {
                if ($user->puede($privilege)) {
                    return;
                }
            }
            throw new HttpException('No tiene permiso para esta acción', 403);
        }
    }

    private function authenticate()
    {
        if (!preg_match('/^Bearer\s+(\S+)$/i', Request::header('Authorization'), $matches)) {
            throw new HttpException('Sesión requerida', 401);
        }

        $payload = JwtUtils::validate($matches[1]);

        if (!$payload || ($payload['typ'] ?? '') !== 'access') {
            throw new HttpException('Token inválido', 401);
        }

        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            throw new HttpException('La sesión expiró', 401);
        }

        $user = (new AuthService())->usuarioDesdeSesion((int) $payload['sub'], $payload['sid'] ?? null);
        if (!$user) {
            throw new HttpException('Sesión cerrada o usuario inactivo', 401);
        }

        $user->ip = Request::ip();
        $user->requestId = Request::requestId();
        Auth::set($user);
        Database::getInstance()->setAuditContext($user->id, $user->usuario, $user->ip, $user->requestId);

        return $user;
    }
}
