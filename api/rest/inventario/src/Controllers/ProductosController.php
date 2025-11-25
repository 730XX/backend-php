<?php
namespace Inventario\Controllers;

use Inventario\Services\ProductosService;
use Inventario\Smart\ProductosSmart;
use Inventario\Utils\ResponseHelper;
use Slim\Slim;
use Exception;

/**
 * Controlador para gestionar Productos
 * Maneja las peticiones HTTP y coordina entre capas
 */
class ProductosController
{
    private $service;
    private $smart;
    private $app;

    public function __construct(ProductosService $service, ProductosSmart $smart, Slim $app)
    {
        $this->service = $service;
        $this->smart = $smart;
        $this->app = $app;
    }

    /**
     * GET: Listar todos los productos
     * Endpoint: GET /productos
     */
    public function getAll()
    {
        try {
            $productos = $this->service->obtenerTodos();

            $response = ResponseHelper::success(
                ['productos' => $productos, 'total' => count($productos)],
                ['Productos obtenidos exitosamente']
            );
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    /**
     * GET: Obtener un producto por ID
     * Endpoint: GET /productos/:id
     */
    public function getById($id)
    {
        try {
            // Validación básica del ID
            if (!is_numeric($id) || $id <= 0) {
                $response = ResponseHelper::error(
                    ['El ID debe ser un número válido mayor a cero'],
                    null
                );
                ResponseHelper::send($this->app, $response, 400);
                return;
            }

            $producto = $this->service->obtenerPorId($id);

            $response = ResponseHelper::success(
                ['producto' => $producto],
                ['Producto obtenido exitosamente']
            );
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    /**
     * POST: Crear un nuevo producto
     * Endpoint: POST /productos
     */
    public function create()
    {
        try {
            // 1. Obtener el JSON del cuerpo de la petición
            $body = $this->app->request->getBody();
            $datos = json_decode($body, true);

            if (!$datos) {
                $response = ResponseHelper::error(
                    ['El cuerpo de la petición no es un JSON válido'],
                    null
                );
                ResponseHelper::send($this->app, $response, 400);
                return;
            }

            // 2. VALIDACIÓN (Capa Smart)
            $this->smart->validarCreacion($datos);

            // 3. PROCESO DE NEGOCIO (Capa Service)
            $productoId = $this->service->crear($datos);

            // 4. Respuesta Exitosa (201 Created)
            $response = ResponseHelper::success(
                ['producto_id' => $productoId],
                ['Producto creado exitosamente']
            );
            ResponseHelper::send($this->app, $response, 201);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    /**
     * PUT: Actualizar un producto
     * Endpoint: PUT /productos/:id
     */
    public function update($id)
    {
        try {
            // 1. Obtener el JSON del cuerpo de la petición
            $body = $this->app->request->getBody();
            $datos = json_decode($body, true);

            if (!$datos) {
                $response = ResponseHelper::error(
                    ['El cuerpo de la petición no es un JSON válido'],
                    null
                );
                ResponseHelper::send($this->app, $response, 400);
                return;
            }

            // 2. VALIDACIÓN (Capa Smart)
            $this->smart->validarActualizacion($datos);

            // 3. PROCESO DE NEGOCIO (Capa Service)
            $this->service->actualizar($id, $datos);

            // 4. Respuesta Exitosa (200 OK)
            $response = ResponseHelper::success(
                ['producto_id' => $id],
                ['Producto actualizado exitosamente']
            );
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    /**
     * PUT: Cambiar estado del producto (activar/desactivar)
     * Endpoint: PUT /productos/:id/estado
     * Body: { "productos_estado": 0 | 1 }
     */
    public function cambiarEstado($id)
    {
        try {
            // 1. Validar el ID (básico)
            if (!is_numeric($id) || $id <= 0) {
                $response = ResponseHelper::error(
                    ['El ID debe ser un número válido mayor a cero'],
                    null
                );
                ResponseHelper::send($this->app, $response, 400);
                return;
            }

            // 2. Obtener datos del body
            $body = $this->app->request->getBody();
            $data = json_decode($body, true);

            if (!$data || !isset($data['productos_estado'])) {
                $response = ResponseHelper::error(
                    ['El campo productos_estado es obligatorio'],
                    null
                );
                ResponseHelper::send($this->app, $response, 400);
                return;
            }

            $nuevoEstado = (int)$data['productos_estado'];

            // Validar que el estado sea 0 o 1
            if ($nuevoEstado !== 0 && $nuevoEstado !== 1) {
                $response = ResponseHelper::error(
                    ['El estado debe ser 0 (inactivo) o 1 (activo)'],
                    null
                );
                ResponseHelper::send($this->app, $response, 400);
                return;
            }

            // 3. PROCESO DE NEGOCIO (Capa Service)
            $this->service->cambiarEstado($id, $nuevoEstado);

            // 4. Respuesta Exitosa (200 OK)
            $accion = $nuevoEstado === 1 ? 'activado' : 'desactivado';
            $response = ResponseHelper::success(
                ['producto_id' => $id, 'estado' => $nuevoEstado],
                ["Producto {$accion} exitosamente"]
            );
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    // --- MÉTODOS PRIVADOS DE AYUDA (Helpers) ---

    /**
     * Maneja la respuesta de error diferenciando errores de cliente vs servidor.
     * Usa la estructura estándar: { tipo, mensajes[], data }
     */
    private function responderError($exception)
    {
        // Por defecto asumimos error interno (500)
        $status = 500;
        $mensajes = ['Error interno del servidor'];
        $debugData = null;

        $msg = $exception->getMessage();

        // Detectar errores de negocio conocidos (Smart/Service)
        if (
            strpos($msg, 'obligatorio') !== false ||
            strpos($msg, 'válido') !== false ||
            strpos($msg, 'cantidad') !== false ||
            strpos($msg, 'negativo') !== false ||
            strpos($msg, 'existe') !== false ||
            strpos($msg, 'debe ser') !== false ||
            strpos($msg, 'debe tener') !== false ||
            strpos($msg, 'exceder') !== false ||
            strpos($msg, 'duplicado') !== false ||
            strpos($msg, 'Ya existe') !== false ||
            strpos($msg, 'vacío') !== false ||
            strpos($msg, 'movimientos asociados') !== false ||
            strpos($msg, 'permitida') !== false ||
            strpos($msg, 'inválid') !== false
        ) {
            $status = 400; // Bad Request
            $mensajes = [$msg];
        } elseif (strpos($msg, 'no encontrado') !== false || strpos($msg, 'No existe') !== false) {
            $status = 404; // Not Found
            $mensajes = [$msg];
        } else {
            // Error de servidor - mostrar detalles solo si está habilitado
            if (getenv('DISPLAY_ERROR_DETAILS') === 'true') {
                $mensajes = ['Error interno del servidor', $msg];
                $debugData = [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ];
            }
        }

        $response = ResponseHelper::error($mensajes, $debugData);
        ResponseHelper::send($this->app, $response, $status);
    }
}
