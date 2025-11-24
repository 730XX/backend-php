<?php

namespace Inventario\Middleware;

use Inventario\Utils\ResponseHelper;

/**
 * Middleware de Autenticación por API Key
 * Protege todos los endpoints verificando que la petición incluya una API Key válida.
 * 
 * La API Key debe enviarse en el header: X-API-Key
 */
class ApiKeyMiddleware
{
    private $app;
    private $validApiKeys;

    /**
     * Constructor
     * @param \Slim\Slim $app Instancia de Slim
     */
    public function __construct($app)
    {
        $this->app = $app;
        
        // Cargar las API Keys válidas desde el .env
        // En producción podrías tener múltiples keys almacenadas en BD
        $this->validApiKeys = [
            getenv('API_KEY_MASTER'),
            getenv('API_KEY_ADMIN'),
            getenv('API_KEY_CLIENT')
        ];
        
        // Filtrar valores vacíos o null
        $this->validApiKeys = array_filter($this->validApiKeys);
    }

    /**
     * Verifica si la petición tiene una API Key válida
     * @return bool
     */
    public function verificar()
    {
        // 1. Obtener la API Key del header (intentar múltiples formatos)
        $headers = $this->app->request->headers;
        $apiKeyRecibida = $headers->get('X-API-Key') 
                       ?? $headers->get('x-api-key')
                       ?? $_SERVER['HTTP_X_API_KEY']
                       ?? null;

        // 2. Si no hay API Key, rechazar
        if (empty($apiKeyRecibida)) {
            $this->rechazar('API Key requerida. Envíe el header X-API-Key');
            return false;
        }

        // 3. Verificar si la API Key es válida
        if (!in_array($apiKeyRecibida, $this->validApiKeys)) {
            $this->rechazar('API Key inválida. Acceso denegado');
            return false;
        }

        // 4. API Key válida - permitir continuar
        return true;
    }

    /**
     * Rechaza la petición con error 401 Unauthorized
     * @param string $mensaje Mensaje de error
     */
    private function rechazar($mensaje)
    {
        $response = ResponseHelper::error(
            [$mensaje, 'Acceso no autorizado'],
            null
        );
        
        ResponseHelper::send($this->app, $response, 401);
        
        // Detener la ejecución de Slim
        $this->app->stop();
    }

    /**
     * Método estático para usar como middleware en Slim 2
     * @param \Slim\Slim $app
     * @return callable
     */
    public static function create($app)
    {
        return function() use ($app) {
            $middleware = new ApiKeyMiddleware($app);
            $middleware->verificar();
        };
    }
}
