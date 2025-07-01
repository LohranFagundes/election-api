<?php
// src/utils/Router.php

class Router
{
    private $routes = [];
    private $middlewares = [];
    private $groupStack = [];
    
    public function get($uri, $callback, $options = [])
    {
        $this->addRoute('GET', $uri, $callback, $options);
    }
    
    public function post($uri, $callback, $options = [])
    {
        $this->addRoute('POST', $uri, $callback, $options);
    }
    
    public function put($uri, $callback, $options = [])
    {
        $this->addRoute('PUT', $uri, $callback, $options);
    }
    
    public function delete($uri, $callback, $options = [])
    {
        $this->addRoute('DELETE', $uri, $callback, $options);
    }
    
    public function patch($uri, $callback, $options = [])
    {
        $this->addRoute('PATCH', $uri, $callback, $options);
    }
    
    public function options($uri, $callback, $options = [])
    {
        $this->addRoute('OPTIONS', $uri, $callback, $options);
    }
    
    public function group($attributes, $callback)
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }
    
    private function addRoute($method, $uri, $callback, $options = [])
    {
        $uri = $this->applyGroupAttributes($uri);
        $middleware = array_merge($this->getGroupMiddleware(), $options['middleware'] ?? []);
        
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'callback' => $callback,
            'middleware' => $middleware
        ];
    }
    
    private function applyGroupAttributes($uri)
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        
        return rtrim($prefix . '/' . ltrim($uri, '/'), '/') ?: '/';
    }
    
    private function getGroupMiddleware()
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, $group['middleware']);
            }
        }
        return $middleware;
    }
    
    public function resolve()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchUri($route['uri'], $uri)) {
                $params = $this->extractParams($route['uri'], $uri);
                return $this->handleRoute($route, $params);
            }
        }
        
        return Response::error(['message' => 'Route not found'], 404);
    }
    
    private function matchUri($routeUri, $requestUri)
    {
        $routePattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routeUri);
        $routePattern = '#^' . $routePattern . '$#';
        
        return preg_match($routePattern, $requestUri);
    }
    
    private function extractParams($routeUri, $requestUri)
    {
        $routePattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routeUri);
        $routePattern = '#^' . $routePattern . '$#';
        
        preg_match($routePattern, $requestUri, $matches);
        array_shift($matches);
        
        preg_match_all('/\{([^}]+)\}/', $routeUri, $paramNames);
        $paramNames = $paramNames[1];
        
        return array_combine($paramNames, $matches);
    }
    
    private function handleRoute($route, $params)
    {
        try {
            $request = new Request();
            $callback = $route['callback'];
            
            $next = function($request) use ($callback, $params) {
                if (is_callable($callback)) {
                    return call_user_func_array($callback, array_values($params));
                }
                
                if (is_string($callback) && strpos($callback, '@') !== false) {
                    list($controller, $method) = explode('@', $callback);
                    $controllerInstance = new $controller();
                    return call_user_func_array([$controllerInstance, $method], array_values($params));
                }
                
                return $callback;
            };
            
            foreach (array_reverse($route['middleware']) as $middleware) {
                $next = $this->wrapMiddleware($middleware, $next);
            }
            
            return $next($request);
            
        } catch (Exception $e) {
            error_log("Route handling error: " . $e->getMessage());
            return Response::error(['message' => 'Internal server error'], 500);
        }
    }
    
    private function wrapMiddleware($middleware, $next)
    {
        return function($request) use ($middleware, $next) {
            if (is_string($middleware)) {
                $middlewareInstance = new $middleware();
            } else {
                $middlewareInstance = $middleware;
            }
            
            return $middlewareInstance->handle($request, $next);
        };
    }
    
    public function getRoutes()
    {
        return $this->routes;
    }
    
    public function hasRoute($method, $uri)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchUri($route['uri'], $uri)) {
                return true;
            }
        }
        return false;
    }
    
    public function redirect($uri, $statusCode = 302)
    {
        http_response_code($statusCode);
        header("Location: $uri");
        exit;
    }
    
    public function abort($statusCode, $message = '')
    {
        http_response_code($statusCode);
        echo $message;
        exit;
    }
}