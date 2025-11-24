<?php
/**
 * Rutas para Movimientos (Kardex)
 * Endpoints: /kardex
 */

use Inventario\Controllers\MovimientosController;
use Inventario\Repositories\MovimientosRepository;
use Inventario\Services\MovimientosService;
use Inventario\Smart\MovimientosSmart;
use Inventario\Utils\ResponseHelper;

return function($app, $db, $logger, $protegerRutas) {
    
    // Inicializar dependencias para Movimientos
    $movimientosRepository = new MovimientosRepository($db);
    $movimientosService = new MovimientosService($movimientosRepository, $logger);
    $movimientosSmart = new MovimientosSmart();
    $movimientosController = new MovimientosController($movimientosService, $movimientosSmart, $app);

    // GET: Listar todos los movimientos (PROTEGIDO)
    $app->get('/kardex', $protegerRutas, function() use ($movimientosController, $app) {
        if ($movimientosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $movimientosController->getAll();
    });

    // GET: Obtener movimiento por ID (PROTEGIDO)
    $app->get('/kardex/:id', $protegerRutas, function($id) use ($movimientosController, $app) {
        if ($movimientosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $movimientosController->getById($id);
    });

    // POST: Registrar movimiento (PROTEGIDO)
    $app->post('/kardex', $protegerRutas, function() use ($movimientosController, $app) {
        if ($movimientosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $movimientosController->create();
    });

    // PUT: Actualizar movimiento (PROTEGIDO)
    $app->put('/kardex/:id', $protegerRutas, function($id) use ($movimientosController, $app) {
        if ($movimientosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $movimientosController->update($id);
    });
};
