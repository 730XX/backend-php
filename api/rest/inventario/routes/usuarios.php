<?php
/**
 * Rutas para Usuarios
 * Endpoints: /usuarios
 */

use Inventario\Utils\ResponseHelper;
use Inventario\Repositories\UsuariosRepository;
use Inventario\Services\UsuariosService;
use Inventario\Smart\UsuariosSmart;
use Inventario\Controllers\UsuariosController;

return function($app, $db, $logger, $protegerRutas) {
    
    // Inicializar capas
    $usuariosRepository = new UsuariosRepository($db);
    $usuariosService = new UsuariosService($usuariosRepository, $logger);
    $usuariosSmart = new UsuariosSmart();
    $usuariosController = new UsuariosController($usuariosService, $usuariosSmart, $app);

    // POST: Login - Autenticar usuario (PÚBLICO - sin API Key)
    $app->post('/usuarios/login', function() use ($usuariosController, $app) {
        if ($usuariosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $usuariosController->login();
    });

    // GET: Listar todos los usuarios activos (PROTEGIDO)
    $app->get('/usuarios', $protegerRutas, function() use ($usuariosController, $app) {
        if ($usuariosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $usuariosController->getAll();
    });

    // GET: Obtener usuario por ID (PROTEGIDO)
    $app->get('/usuarios/:id', $protegerRutas, function($id) use ($usuariosController, $app) {
        if ($usuariosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $usuariosController->getById($id);
    });

    // POST: Crear nuevo usuario (PROTEGIDO)
    $app->post('/usuarios', $protegerRutas, function() use ($usuariosController, $app) {
        if ($usuariosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $usuariosController->create();
    });

    // PUT: Actualizar usuario existente (PROTEGIDO)
    $app->put('/usuarios/:id', $protegerRutas, function($id) use ($usuariosController, $app) {
        if ($usuariosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $usuariosController->update($id);
    });

    // PUT: Cambiar estado del usuario (PROTEGIDO) - Soft delete
    $app->put('/usuarios/:id/estado', $protegerRutas, function($id) use ($usuariosController, $app) {
        if ($usuariosController === null) {
            $response = ResponseHelper::error(
                ['Controller no inicializado', 'Error interno del servidor'],
                null
            );
            ResponseHelper::send($app, $response, 500);
            return;
        }
        $usuariosController->cambiarEstado($id);
    });
};
