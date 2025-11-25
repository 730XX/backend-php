<?php

use Inventario\Controllers\VentasController;
use Inventario\Services\VentasService;
use Inventario\Services\MovimientosService;
use Inventario\Repositories\VentasRepository;
use Inventario\Repositories\MovimientosRepository;
use Inventario\Smart\VentasSmart;

/**
 * Rutas de Ventas
 * Punto de Venta con actualización automática de inventario
 */
return function($app, $db, $logger, $protegerRutas) {
    
    // Instanciar dependencias
    $repoVentas = new VentasRepository($db);
    $repoMovimientos = new MovimientosRepository($db);
    $movimientosService = new MovimientosService($repoMovimientos, $logger);
    $ventasSmart = new VentasSmart();
    
    // Obtener conexión PDO para transacciones globales
    $dbConnection = $db->getConnection();
    
    $ventasService = new VentasService(
        $repoVentas, 
        $movimientosService, 
        $logger,
        $dbConnection
    );
    
    $controller = new VentasController($ventasService, $ventasSmart, $app);

    /**
     * POST /ventas
     * Registrar una nueva venta
     * - Crea la venta (cabecera + detalles)
     * - Genera movimientos de SALIDA automáticos
     * - Actualiza el stock de productos
     * 
     * Body JSON:
     * {
     *   "items": [
     *     {
     *       "productos_id": 1,
     *       "cantidad": 2.5,
     *       "precio": 15.50
     *     },
     *     ...
     *   ],
     *   "cliente_nombre": "Juan Pérez" (opcional),
     *   "observaciones": "Entrega urgente" (opcional)
     * }
     * 
     * Headers:
     * - X-User-Id: ID del usuario que realiza la venta (obligatorio)
     * - X-API-Key: Clave de autenticación (obligatorio)
     */
    $app->post('/ventas', $protegerRutas, function() use ($controller) {
        $controller->create();
    });

    /**
     * Rutas futuras (comentadas, para implementar después):
     * 
     * GET /ventas
     * Listar todas las ventas con paginación
     * 
     * GET /ventas/:id
     * Obtener detalles de una venta específica
     * 
     * PUT /ventas/:id/cancelar
     * Anular una venta (reversa de movimientos)
     * 
     * GET /ventas/reporte
     * Generar reportes de ventas por fecha
     */
};
