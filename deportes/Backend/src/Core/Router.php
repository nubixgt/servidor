<?php
namespace Core;

class Router {
    private $routes = [];

    public function add($method, $path, $controller, $action) {
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[0-9]+)', $path);
        $this->routes[] = ['method' => $method, 'regex' => '#^' . $regex . '$#', 'controller' => "\\Controllers\\$controller", 'action' => $action];
    }

    public function dispatch($method, $uri) {
        $uri = rtrim($uri, '/') ?: '/';
        foreach ($this->routes as $r) {
            if ($r['method'] === $method && preg_match($r['regex'], $uri, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                $controller = new $r['controller']();
                $controller->{$r['action']}($params);
                return;
            }
        }
        Response::error("Ruta no encontrada: $method $uri", 404);
    }
}
