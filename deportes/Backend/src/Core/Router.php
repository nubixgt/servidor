<?php
namespace Core;

class Router {
    private $routes = [];

    public function add($method, $path, $controller, $action) {
        // Convert route params like {id} into regex
        $routeRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $path);
        $routeRegex = '#^' . $routeRegex . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'regex' => $routeRegex,
            'controller' => "\\Controllers\\{$controller}",
            'action' => $action
        ];
    }

    public function dispatch($method, $uri) {
        $uri = rtrim($uri, '/');
        if (empty($uri)) $uri = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $uri, $matches)) {
                // Get named params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $controllerName = $route['controller'];
                $action = $route['action'];

                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    if (method_exists($controller, $action)) {
                        call_user_func_array([$controller, $action], [$params]);
                        return;
                    }
                }
            }
        }
        Response::error("Ruta no encontrada: $method $uri", 404);
    }
}
