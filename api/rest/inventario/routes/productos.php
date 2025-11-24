<?php
/**
 * Rutas para Productos
 * Endpoints: /productos
 */

use Inventario\Controllers\ProductosController;
use Inventario\Repositories\ProductosRepository;
use Inventario\Services\ProductosService;
use Inventario\Smart\ProductosSmart;
use Inventario\Utils\ResponseHelper;

return function($app, $db, $logger, $protegerRutas) {
    
    // Inicializar dependencias para Productos
    $productosRepository = new ProductosRepository($db);
    $productosService = new ProductosService($productosRepository, $logger);
    $productosSmart = new ProductosSmart();
    $productosController = new ProductosController($productosService, $productosSmart, $app);

    // GET: Listar todos los productos (PROTEGIDO)
    $app->get('/productos', $protegerRutas, function() use ($productosController, $app) {
        if ($productosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $productosController->getAll();
    });

    // GET: Obtener producto por ID (PROTEGIDO)
    $app->get('/productos/:id', $protegerRutas, function($id) use ($productosController, $app) {
        if ($productosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $productosController->getById($id);
    });

    // POST: Crear producto (PROTEGIDO)
    $app->post('/productos', $protegerRutas, function() use ($productosController, $app) {
        if ($productosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $productosController->create();
    });

    // PUT: Actualizar producto (PROTEGIDO)
    $app->put('/productos/:id', $protegerRutas, function($id) use ($productosController, $app) {
        if ($productosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $productosController->update($id);
    });

    // PUT: Cambiar estado del producto (PROTEGIDO) - Soft delete
    $app->put('/productos/:id/estado', $protegerRutas, function($id) use ($productosController, $app) {
        if ($productosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $productosController->cambiarEstado($id);
    });
};
