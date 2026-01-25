<?php
// Kelas untuk memetakan URL ke Controller dan Action.

class Router {
    protected $routes = [
        'GET' => [],
        'POST' => []
    ];

    public function get($uri, $controllerAction) {
        $this->routes['GET'][$uri] = $controllerAction;
    }

    public function post($uri, $controllerAction) {
        $this->routes['POST'][$uri] = $controllerAction;
    }

    public function getRoutes() {
        return $this->routes;
    }

    public function dispatch($uri, $method) {
        // Prioritas 1: Cek rute statis (tanpa parameter) untuk kecocokan persis.
        if (array_key_exists($uri, $this->routes[$method])) {
            $this->callAction(
                ...explode('@', $this->routes[$method][$uri])
            );
            return; // Hentikan jika rute statis ditemukan.
        }

        // Prioritas 2: Jika tidak ada rute statis yang cocok, cek rute dinamis (dengan parameter).
        foreach ($this->routes[$method] as $route => $controllerAction) {
            // Hanya proses rute yang benar-benar mengandung parameter dinamis
            if (strpos($route, '{') === false) {
                continue; // Lewati rute statis karena sudah diperiksa
            }
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^\/]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                // Hapus elemen pertama ($matches[0]) yang merupakan seluruh string yang cocok
                array_shift($matches);
                $this->callAction(
                    ...explode('@', $controllerAction),
                    ...$matches // Teruskan parameter yang cocok ke action
                );
                return; // Hentikan pencarian setelah rute ditemukan
            }
        }

        // Jika tidak ada rute yang cocok
        // Hapus baris debugging jika masih ada
        $this->serveNotFound("<h1>404 Not Found</h1><p>Halaman tidak ditemukan.</p><hr><small>Debug Info: Router menerima URI <strong>" . htmlspecialchars($uri) . "</strong> tetapi tidak ada rute yang cocok.</small>");
    }

    protected function callAction($controller, $action, ...$params) {
        $controllerClass = "app\\Controllers\\{$controller}";

        if (!class_exists($controllerClass)) {
            $this->serveNotFound("Controller {$controllerClass} not found.");
            return;
        }

        $controllerInstance = new $controllerClass();

        if (!method_exists($controllerInstance, $action)) {
            $this->serveNotFound("Action {$action} not found on controller {$controllerClass}.");
            return;
        }

        $controllerInstance->$action(...$params);
    }

    protected function serveNotFound($message = "404 Not Found") {
        http_response_code(404);
        echo $message;
    }
}
