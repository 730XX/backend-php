<?php

namespace Inventario\Controllers;

use Exception;
use Inventario\Utils\ResponseHelper;

/**
 * Controlador de Movimientos
 * Orquesta el flujo HTTP -> Validación -> Negocio -> Respuesta.
 * Framework: Slim 2 (Legacy)
 * 
 * Todas las respuestas siguen el formato:
 * { tipo: 1|2|3, mensajes: [], data: {} }
 */
class MovimientosController
{

    private $app;     // La instancia de Slim
    private $service; // La lógica de negocio
    private $smart;   // El validador

    // Inyección de Dependencias
    public function __construct($service, $smart, $app)
    {
        $this->service = $service;
        $this->smart = $smart;
        $this->app = $app;
    }

    /**
     * GET /kardex
     * Lista el historial de movimientos.
     */
    public function getAll()
    {
        try {
            // 1. Llamamos al servicio (nunca al repo directo)
            $movimientos = $this->service->obtenerTodos();

            // 2. Respuesta Exitosa Estándar
            $response = ResponseHelper::success(
                ['movimientos' => $movimientos, 'total' => count($movimientos)],
                ['Movimientos obtenidos exitosamente']
            );
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            // Error interno (500)
            $this->responderError($e);
        }
    }

    /**
     * POST /kardex
     * Registra un nuevo movimiento (Entrada/Salida).
     */
    public function create()
    {
        try {
            // 1. Obtener el JSON del cuerpo de la petición (Raw Body)
            $body = $this->app->request->getBody();
            $data = json_decode($body, true);

            if (!$data) {
                $response = ResponseHelper::error(
                    ['El cuerpo de la petición no es un JSON válido'],
                    null
                );
                ResponseHelper::send($this->app, $response, 400);
                return;
            }

            // 2. Simulamos obtener el usuario de la sesión/token
            // En producción, esto viene del Middleware de Auth.
            // Para tu entrega, usaremos un header o un valor por defecto.
            $headers = $this->app->request->headers;
            $usuarioId = $headers->get('X-User-Id', 1); // Default: 1 (Admin)

            // 3. VALIDACIÓN (Capa Smart) - El portero
            // Si esto falla, lanza Excepción y salta al catch
            $this->smart->validarCreacion($data);

            // 4. PROCESO DE NEGOCIO (Capa Service) - El cerebro
            $nuevoId = $this->service->registrarMovimiento($data, $usuarioId);

            // 5. Respuesta Exitosa (201 Created)
            $response = ResponseHelper::success(
                ['movimiento_id' => $nuevoId],
                ['Movimiento registrado correctamente', 'Stock actualizado']
            );
            ResponseHelper::send($this->app, $response, 201);
        } catch (Exception $e) {
            // 6. Manejo Centralizado de Errores
            $this->responderError($e);
        }
    }

    /**
     * GET /kardex/{id}
     * Obtiene un movimiento específico por su ID
     */
    public function getById($id)
    {
        try {
            // 1. Llamar al servicio para obtener el movimiento
            $movimiento = $this->service->obtenerMovimientoPorId($id);

            // 2. Respuesta Exitosa
            $response = ResponseHelper::success(
                $movimiento,
                ['Movimiento obtenido correctamente']
            );
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            // 3. Manejo de errores (404 si no existe, 500 si es otro error)
            $this->responderError($e);
        }
    }

    /**
     * PUT /kardex/{id}
     * Actualiza un movimiento existente
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
            $this->service->actualizarMovimiento($id, $datos);

            // 4. Respuesta Exitosa (200 OK)
            $response = ResponseHelper::success(
                ['movimiento_id' => $id],
                ['Movimiento actualizado correctamente', 'Stock recalculado']
            );
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            // 5. Manejo Centralizado de Errores
            $this->responderError($e);
        }
    }

    /**
     * DELETE: Eliminar (soft delete) un movimiento por ID
     * Endpoint: DELETE /kardex/:id
     */
    public function delete($id)
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

            // 2. PROCESO DE NEGOCIO (Capa Service)
            $this->service->eliminarMovimiento($id);

            // 3. Respuesta Exitosa (200 OK)
            $response = ResponseHelper::success(
                ['movimiento_id' => $id],
                ['Movimiento eliminado correctamente', 'Stock recalculado']
            );
            ResponseHelper::send($this->app, $response, 200);
        } catch (Exception $e) {
            // 4. Manejo Centralizado de Errores
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

        // Si es un error de validación o negocio (usualmente mensajes controlados)
        // En un sistema real usaríamos clases de Excepción personalizadas,
        // aquí filtramos por lógica simple para Slim 2.
        $msg = $exception->getMessage();

        // Detectar errores de negocio conocidos (Smart/Service)
        if (
            strpos($msg, 'obliatorio') !== false ||
            strpos($msg, 'válido') !== false ||
            strpos($msg, 'Stock insuficiente') !== false ||
            strpos($msg, 'cantidad') !== false ||
            strpos($msg, 'negativo') !== false ||
            strpos($msg, 'existe') !== false ||
            strpos($msg, 'debe ser') !== false ||
            strpos($msg, 'exceder') !== false
        ) {
            $status = 400; // Bad Request (Culpa del cliente)
            $mensajes = [$msg]; // Mostramos el mensaje porque es seguro
        } elseif (strpos($msg, 'no encontrado') !== false || strpos($msg, 'No existe') !== false) {
            // Error 404 - Recurso no encontrado
            $status = 404;
            $mensajes = [$msg];
        } else {
            // Si es error de SQL o Código, NO mostramos el detalle al usuario (Seguridad)
            // Solo logueamos internamente (ya lo hizo el Service/Logger)
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
