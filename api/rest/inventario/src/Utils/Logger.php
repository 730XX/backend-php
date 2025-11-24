<?php

namespace Inventario\Utils;

use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;

/**
 * Wrapper para Monolog.
 * Centraliza la configuración de logs para no repetir código en los servicios.
 * Escribe en: /backend/logs/app.log
 */
class Logger
{

    private $logger;

    public function __construct()
    {
        // 1. Definir la ruta del archivo de log
        // Estamos en: /backend/api/rest/inventario/src/Utils
        // Subimos 5 niveles para llegar a /backend/logs/
        $logPath = __DIR__ . '/../../../../../logs/app.log';

        // 2. Crear instancia de Monolog (Canal: 'inventario_api')
        $this->logger = new Monolog('inventario_api');

        // 3. Crear el manejador de archivos (StreamHandler)
        // DEBUG significa que guardará todo (Info, Warning, Error)
        try {
            $this->logger->pushHandler(new StreamHandler($logPath, Monolog::DEBUG));
        } catch (\Exception $e) {
            // Si falla el log (permisos de carpeta), no podemos detener la app,
            // pero podríamos usar error_log de PHP como fallback.
            error_log("FALLO CRÍTICO MONOLOG: " . $e->getMessage());
        }
    }

    /**
     * Registra información de flujo normal (Auditoría Funcional)
     * Ej: "Usuario X creó un movimiento", "Stock actualizado"
     */
    public function info($mensaje, $contexto = [])
    {
        $this->logger->info($mensaje, $contexto);
    }

    /**
     * Registra advertencias de negocio (No son errores de código, pero sí anomalías)
     * Ej: "Intento de stock negativo", "Intento de acceso sin API Key"
     */
    public function warning($mensaje, $contexto = [])
    {
        $this->logger->warning($mensaje, $contexto);
    }

    /**
     * Registra errores técnicos graves (Excepciones)
     * Ej: "Base de datos caída", "SQL Syntax Error"
     */
    public function error($mensaje, $contexto = [])
    {
        $this->logger->error($mensaje, $contexto);
    }
}
