<?php

class Routes {
    private static $routes = [];
    
    public static function add($path, $page) {
        self::$routes[$path] = $page;
    }
    
    public static function route($requestUri) {
        // Remove query string if present
        $path = parse_url($requestUri, PHP_URL_PATH);
        
        // Remove leading slash
        $path = ltrim($path, '/');
        
        // If empty path, default to home
        if (empty($path)) {
            $path = 'home';
        }
        
        // Check if route exists
        if (isset(self::$routes[$path])) {
            return self::$routes[$path];
        }
        
        // Default to the path itself (for pages directory)
        return $path;
    }
    
    public static function redirect($url) {
        header("Location: " . $url);
        exit();
    }
    
    public static function getCurrentUrl() {
        return $_SERVER['REQUEST_URI'];
    }
    
    public static function baseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $path = dirname($script);
        return $protocol . '://' . $host . ($path === '/' ? '' : $path);
    }
}

// Define routes
Routes::add('home', 'register');
Routes::add('register', 'register');
Routes::add('admin', 'admin/dashboard');

?> 