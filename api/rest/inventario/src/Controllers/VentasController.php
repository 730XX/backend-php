<?php

namespace Inventario\Controllers;

use Inventario\Services\VentasService;
use Inventario\Smart\VentasSmart;
use Inventario\Utils\ResponseHelper;
use Slim\Slim;
use Exception;

class VentasController
{
    private $service;
    private $smart;
    private $app;

    public function __construct(VentasService $service, VentasSmart $smart, Slim $app)
    {
        $this->service = $service;
        $this->smart = $smart;
        $this->app = $app;
    }

    /**
     * POST /ventas
     * Recibe: { items: [ {productos_id: 1, cantidad: 2, precio: 10.50}, ... ] }
     * Header obligatorio: X-User-Id (ID del usuario que realiza la venta)
     */
    public function create()
    {
        try {
            // 1. VALIDAR BODY JSON
            $body = $this->app->request->getBody();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON inválido: " . json_last_error_msg());
            }

            if (!$data) {
                throw new Exception("El cuerpo de la petición está vacío.");
            }

            // 2. OBTENER Y VALIDAR USUARIO DEL HEADER
            $headers = $this->app->request->headers;
            $usuarioId = $headers->get('X-User-Id');

            // CRÍTICO: En producción, esto debería venir del JWT o sesión
            // Por ahora, validamos que venga el header
            if (!$usuarioId || !is_numeric($usuarioId) || $usuarioId <= 0) {
                throw new Exception("Header X-User-Id es obligatorio y debe ser un número válido.");
            }

            // 3. VALIDAR ESTRUCTURA DE DATOS (Capa Smart)
            $this->smart->validarCreacion($data);

            // 4. PROCESAR VENTA + MOVIMIENTOS AUTOMÁTICOS
            $ventaId = $this->service->registrarVenta($data, $usuarioId);

            // 5. RESPUESTA EXITOSA
            $response = ResponseHelper::success(
                [
                    'venta_id' => $ventaId,
                    'mensaje' => 'Venta registrada correctamente',
                    'timestamp' => date('Y-m-d H:i:s')
                ],
                ['Venta procesada exitosamente', 'Inventario actualizado automáticamente']
            );
            ResponseHelper::send($this->app, $response, 201);

        } catch (Exception $e) {
            $this->responderError($e);
        }
    }

    /**
     * Manejo centralizado de errores con códigos HTTP apropiados
     */
    private function responderError($exception)
    {
        $msg = $exception->getMessage();
        $status = 500; // Error interno por defecto

        // Determinar código de estado según el tipo de error
        if (strpos($msg, 'JSON inválido') !== false || 
            strpos($msg, 'cuerpo de la petición') !== false) {
            $status = 400; // Bad Request
        }
        elseif (strpos($msg, 'X-User-Id') !== false) {
            $status = 401; // Unauthorized
        }
        elseif (strpos($msg, 'Stock insuficiente') !== false ||
                strpos($msg, 'cantidad') !== false ||
                strpos($msg, 'precio') !== false ||
                strpos($msg, 'no existe') !== false ||
                strpos($msg, 'inactivo') !== false ||
                strpos($msg, 'duplicado') !== false ||
                strpos($msg, 'debe') !== false) {
            $status = 400; // Bad Request - Validación de negocio
        }
        elseif (strpos($msg, 'no puede') !== false) {
            $status = 403; // Forbidden - Regla de negocio
        }

        $response = ResponseHelper::error([$msg]);
        ResponseHelper::send($this->app, $response, $status);
    }
}
