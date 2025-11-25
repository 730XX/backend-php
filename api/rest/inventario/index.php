<?php
/**
 * API REST - Inventario
 * Punto de entrada principal
 */

require '../../../vendor/autoload.php';

// Cargar variables de entorno
try {
    $dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
    $dotenv->load();
} catch (Exception $e) {
    $response = \Inventario\Utils\ResponseHelper::error(
        ['Error cargando variables de entorno', 'Configuración del servidor incorrecta'],
        null
    );
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Instanciar Slim 2
$app = new \Slim\Slim([
    'debug' => getenv('DISPLAY_ERROR_DETAILS') === 'true'
]);

// --- CONFIGURACIÓN CORS ---
// Manejar peticiones OPTIONS (preflight) ANTES de cualquier ruta
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key, x-api-key, X-User-Id, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

// Configurar headers CORS en Slim - Hook que se ejecuta ANTES de cada respuesta
$app->hook('slim.before', function() use ($app) {
    $app->response->headers->set('Access-Control-Allow-Origin', '*');
    $app->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $app->response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-API-Key, x-api-key, X-User-Id, Authorization');
    $app->response->headers->set('Access-Control-Expose-Headers', 'Content-Type, X-API-Key, X-User-Id');
});

// Configuración de respuesta JSON
$app->response->headers->set('Content-Type', 'application/json');

// Inicializar dependencias compartidas
try {
    $db = new \Inventario\Config\Database();
    $logger = new \Inventario\Utils\Logger();
} catch (Exception $e) {
    error_log("Error instanciando dependencias compartidas: " . $e->getMessage());
    $db = null;
    $logger = null;
}

// --- RUTA PÚBLICA (Health Check) ---

$app->get('/', function() use ($app, $db, $logger) {
    $response = \Inventario\Utils\ResponseHelper::success(
        [
            'api' => 'Inventario API',
            'version' => '1.0',
            'status' => 'online',
            'database' => $db !== null ? 'connected' : 'disconnected',
            'logger' => $logger !== null ? 'active' : 'inactive',
            'php_version' => phpversion(),
            'endpoints' => [
                'productos' => '/productos (GET, POST, PUT, DELETE)',
                'movimientos' => '/kardex (GET, POST, PUT)',
                'usuarios' => '/usuarios (GET)',
                'ventas' => '/ventas (POST) - Punto de Venta con actualización automática de inventario'
            ],
            'seguridad' => 'API Key requerida en header X-API-Key',
            'documentacion' => 'Ver DOCUMENTACION_VENTAS.md para detalles del módulo de ventas'
        ],
        ['API funcionando correctamente', 'Use X-API-Key para acceder a los endpoints protegidos']
    );
    \Inventario\Utils\ResponseHelper::send($app, $response, 200);
});

// --- MIDDLEWARE DE SEGURIDAD ---

$protegerRutas = function() use ($app) {
    $middleware = new \Inventario\Middleware\ApiKeyMiddleware($app);
    $middleware->verificar();
};

// --- CARGAR RUTAS DESDE ARCHIVOS EXTERNOS ---

// Cargar rutas de Productos
$rutasProductos = require __DIR__ . '/routes/productos.php';
$rutasProductos($app, $db, $logger, $protegerRutas);

// Cargar rutas de Movimientos (Kardex)
$rutasMovimientos = require __DIR__ . '/routes/movimientos.php';
$rutasMovimientos($app, $db, $logger, $protegerRutas);

// Cargar rutas de Usuarios
$rutasUsuarios = require __DIR__ . '/routes/usuarios.php';
$rutasUsuarios($app, $db, $logger, $protegerRutas);

// Cargar rutas de Ventas
$rutasVentas = require __DIR__ . '/routes/ventas.php';
$rutasVentas($app, $db, $logger, $protegerRutas);

// Ejecutar la aplicación
$app->run();
