<?php

class Router
{
    private array $routes = [];
    private string $groupPrefix = '';

    public function get($path, $handler, $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function put($path, $handler, $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    private function addRoute($method, $path, $handler, $middlewares): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->convertPathToRegex($this->groupPrefix . $path),
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    private function convertPathToRegex($path): string
    {
        if (!str_contains($path, '{')) {
            return $path;
        }

        // Проебразуем путь в регулярку, чтоб в run() в preg_match проверить совпадение  path,
        // Необходимо только для динамических маршрутов, по этому выше просто возвращаем путь, если он статичный
        return preg_replace('/\{([a-zA-Z0-9_]+)\}/', '/?P<\1>[a-zA-Z0-9_]+/', $path);
    }

    public function group($prefix, $callback): void
    {
        // Тримим слэши справа, чтоб не задвоились
        $this->groupPrefix = rtrim($prefix, '/');
        // Обрабатываем то, что внутри группы, исплользуя этот же экземпляр роутера
        $callback($this);
        $this->groupPrefix = '';
    }

    public function run()
    {
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
        $requestUri = $_SERVER['REQUEST_URI'];

        foreach ($this->routes as $route) {
            if ($requestMethod === $route['method'] && preg_match('#^' . $route['path'] . '$#', $requestUri, $matches)) {
                foreach ($route['middlewares'] as $middleware) {
                    $middlewareClass = new $middleware();
                    $result = $middlewareClass->handle();
                    if ($result !== true) {
                        echo $result;
                        return;
                    }
                }

                // Поскольку preg_match в $matches записывает не только динамические переменные
                // Но и под индексом 0 будет идти путь маршрута, который нам в дальнейшем не нужен,
                // по этому дропаем числовые ключи и остается (в нашем случае) $matches = ['category_name' => 'категория']
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Поддержка различных вариантов хэндлера. Как ларовского канона [Controller::class, 'func'], так
                // и простоой анонимной функции
                if (is_array($route['handler'])) {
                    [$controller, $method] = $route['handler'];
                    $controller = new $controller();
                    echo call_user_func([$controller, $method], $params);
                } else {
                    echo call_user_func($route['handler'], $params);
                }
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }
}