<?php

namespace Inventario\Controllers;

use Inventario\Services\UsuariosService;
use Inventario\Smart\UsuariosSmart;
use Inventario\Utils\ResponseHelper; // Usamos tu helper
use Slim\Slim;
use Exception;

class UsuariosController
{
    private $service;
    private $smart;
    private $app;

    public function __construct(UsuariosService $service, UsuariosSmart $smart, Slim $app)
    {
        $this->service = $service;
        $this->smart = $smart;
        $this->app = $app;
    }

    public function getAll()
    {
        try {
            $usuarios = $this->service->obtenerTodos();

            // Limpieza extra: Asegurar que no vayan passwords en la lista
            foreach ($usuarios as &$u) {
                unset($u['usuarios_password'], $u['usuarios_apikey']);
            }

            $response = ResponseHelper::success(['usuarios' => $usuarios], ['Usuarios listados']);
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    public function getById($id)
    {
        try {
            $usuario = $this->service->obtenerPorId($id);
            // El servicio ya quitó el password, pero el apikey sigue siendo sensible
            unset($usuario['usuarios_apikey']);

            $response = ResponseHelper::success(['usuario' => $usuario], ['Usuario encontrado']);
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    public function create()
    {
        try {
            $body = $this->app->request->getBody();
            $datos = json_decode($body, true);
            if (!$datos) throw new Exception("JSON inválido.");

            $this->smart->validarCreacion($datos);
            $id = $this->service->crear($datos);

            $response = ResponseHelper::success(['usuarios_id' => $id], ['Usuario creado correctamente']);
            ResponseHelper::send($this->app, $response, 201);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    public function update($id)
    {
        try {
            $body = $this->app->request->getBody();
            $datos = json_decode($body, true);
            if (!$datos) throw new Exception("JSON inválido.");

            $this->smart->validarActualizacion($datos);
            $this->service->actualizar($id, $datos);

            $response = ResponseHelper::success(['usuarios_id' => $id], ['Usuario actualizado']);
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    public function cambiarEstado($id)
    {
        try {
            $this->service->cambiarEstado($id, 0); // Soft delete
            $response = ResponseHelper::success(
                ['usuarios_id' => $id, 'estado' => 0],
                ['Usuario desactivado exitosamente']
            );
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    private function responderError($exception)
    {
        // Replicamos la lógica de tu ProductosController
        $msg = $exception->getMessage();
        $status = 500;

        // Errores conocidos (Bad Request)
        if (
            strpos($msg, 'obligatorio') !== false ||
            strpos($msg, 'válido') !== false ||
            strpos($msg, 'registrado') !== false ||
            strpos($msg, 'caracteres') !== false ||
            strpos($msg, 'formato') !== false ||
            strpos($msg, 'debe ser') !== false ||
            strpos($msg, 'debe tener') !== false ||
            strpos($msg, 'inválido') !== false ||
            strpos($msg, 'ya está') !== false
        ) {
            $status = 400;
        } elseif (strpos($msg, 'no encontrado') !== false) {
            $status = 404;
        }

        // Solo mostrar debug en errores 500 si está activado
        $debug = null;
        if ($status === 500 && getenv('DISPLAY_ERROR_DETAILS') === 'true') {
            $debug = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        $response = ResponseHelper::error([$msg], $debug);
        ResponseHelper::send($this->app, $response, $status);
    }

    /**
     * Login - Autenticar usuario
     */
    public function login()
    {
        try {
            $body = json_decode($this->app->request->getBody(), true);

            // Validar que vengan los datos
            if (empty($body['correo']) || empty($body['password'])) {
                $response = ResponseHelper::error(
                    ['Correo y contraseña son obligatorios'],
                    null
                );
                ResponseHelper::send($this->app, $response, 400);
                return;
            }

            // Intentar autenticar
            $resultado = $this->service->autenticar($body['correo'], $body['password']);

            if ($resultado === null) {
                $response = ResponseHelper::error(
                    ['Credenciales incorrectas'],
                    null
                );
                ResponseHelper::send($this->app, $response, 401);
                return;
            }

            // Login exitoso
            $response = ResponseHelper::success(
                ['usuario' => $resultado],
                ['Login exitoso', 'Bienvenido ' . $resultado['usuarios_nombre']]
            );
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }
}
