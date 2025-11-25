<?php
/**
 * Rutas de Reportes
 * Exportación de datos a Excel
 */

use Inventario\Controllers\ReportesController;

return function($app, $db, $logger, $protegerRutas) {
    
    // Instanciar controlador
    $reportesController = new ReportesController($db, $logger);

    /**
     * GET /reportes/movimientos
     * Exportar movimientos a Excel
     * 
     * Query Params opcionales:
     * - fecha_inicio: Filtrar desde fecha (YYYY-MM-DD)
     * - fecha_fin: Filtrar hasta fecha (YYYY-MM-DD)
     * - tipo: Filtrar por tipo (ENTRADA, SALIDA)
     * - producto_id: Filtrar por producto específico
     * 
     * Headers requeridos:
     * - X-API-Key: API Key para autenticación
     * 
     * Response: Archivo Excel (.xlsx)
     */
    $app->get('/reportes/movimientos', $protegerRutas, function() use ($app, $reportesController) {
        $reportesController->exportarMovimientos($app);
    });

    /**
     * GET /reportes/productos
     * Exportar productos a Excel
     * 
     * Query Params opcionales:
     * - stock_minimo: Filtrar productos con stock <= al valor indicado
     * - estado: Filtrar por estado (0 o 1)
     * 
     * Headers requeridos:
     * - X-API-Key: API Key para autenticación
     * 
     * Response: Archivo Excel (.xlsx)
     */
    $app->get('/reportes/productos', $protegerRutas, function() use ($app, $reportesController) {
        $reportesController->exportarProductos($app);
    });

    /**
     * GET /reportes/ventas
     * Exportar ventas a Excel
     * 
     * Query Params opcionales:
     * - fecha_inicio: Filtrar desde fecha (YYYY-MM-DD)
     * - fecha_fin: Filtrar hasta fecha (YYYY-MM-DD)
     * 
     * Headers requeridos:
     * - X-API-Key: API Key para autenticación
     * - X-User-Id: ID del usuario (para auditoría)
     * 
     * Response: Archivo Excel (.xlsx)
     */
    $app->get('/reportes/ventas', $protegerRutas, function() use ($app, $reportesController) {
        $reportesController->exportarVentas($app);
    });
};
