<?php

namespace Inventario\Services;

use Exception;
use InvalidArgumentException;

/**
 * Servicio de Negocio: Movimientos
 * Orquestador de la lógica transaccional.
 * Responsabilidades:
 * 1. Verificar reglas de negocio (Stock suficiente, Producto activo).
 * 2. Calcular nuevos saldos.
 * 3. Ejecutar la transacción atómica (Todo o Nada).
 * 4. Registrar Logs de Auditoría.
 */
class MovimientosService
{

    private $repository;
    private $logger;

    // Inyectamos el Repositorio (BD) y el Logger (Monolog)
    public function __construct($repository, $logger)
    {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Obtener todos los movimientos
     * @return array Lista de movimientos
     */
    public function obtenerTodos()
    {
        $this->logger->info("Obteniendo lista de movimientos");
        return $this->repository->obtenerTodos();
    }

    /**
     * Procesa la creación de un movimiento de inventario.
     * * @param array $datos Datos validados previamente por la capa Smart.
     * @param int $usuarioId ID del usuario que realiza la acción (extraído del Token/Session).
     * @return int ID del movimiento creado.
     * @throws Exception Si hay error de negocio o base de datos.
     */
    public function registrarMovimiento($datos, $usuarioId, $usarTransaccion = true)
    {
        // 1. Iniciamos la Transacción SOLO si nos lo piden
        if ($usarTransaccion) {
            $this->repository->beginTransaction();
        }

        try {
            // A. Obtener estado actual del producto (Snapshot)
            $producto = $this->repository->obtenerDatosProducto($datos['productos_id']);

            // Regla de Negocio 1: El producto debe existir
            if (!$producto) {
                throw new Exception("El producto solicitado no existe.");
            }

            // Regla de Negocio 2: El producto debe estar activo
            if ($producto['productos_estado'] == 0) {
                throw new Exception("No se pueden realizar movimientos en un producto inactivo.");
            }

            $stockActual = (float) $producto['productos_stock'];
            $cantidadMovimiento = (float) $datos['movimientos_cantidad'];
            $nuevoStock = 0.0;

            // B. Calcular Matemáticas según el tipo
            if ($datos['movimientos_tipo'] === 'ENTRADA') {
                $nuevoStock = $stockActual + $cantidadMovimiento;
            } else { // SALIDA
                // Regla de Negocio 3: No se puede sacar más de lo que hay
                if ($stockActual < $cantidadMovimiento) {
                    // Logueamos el intento fallido como advertencia de negocio
                    $this->logger->warning("Intento de stock negativo", [
                        'producto' => $producto['productos_nombre'],
                        'stock_actual' => $stockActual,
                        'solicitado' => $cantidadMovimiento,
                        'usuario_id' => $usuarioId
                    ]);

                    throw new Exception("Stock insuficiente. Disponible: {$stockActual}, Solicitado: {$cantidadMovimiento}");
                }
                $nuevoStock = $stockActual - $cantidadMovimiento;
            }

            // C. Actualizar el Maestro de Productos
            $this->repository->actualizarStockProducto($datos['productos_id'], $nuevoStock);

            // D. Insertar el Historial (Kardex)
            // Pasamos el $nuevoStock para que quede grabado el saldo histórico
            $idMovimiento = $this->repository->guardarMovimiento($datos, $nuevoStock, $usuarioId);

            // E. Si llegamos aquí sin errores Y manejamos nuestra propia transacción, CONFIRMAMOS
            if ($usarTransaccion) {
                $this->repository->commit();
            }

            // F. Log de Auditoría Exitoso
            $this->logger->info("Movimiento Exitoso", [
                'id_movimiento' => $idMovimiento,
                'tipo' => $datos['movimientos_tipo'],
                'producto' => $producto['productos_nombre'],
                'usuario' => $usuarioId,
                'transaccion_propia' => $usarTransaccion
            ]);

            return $idMovimiento;
        } catch (Exception $e) {
            // G. SI ALGO FALLA Y manejamos nuestra propia transacción: Revertimos
            // Si usarTransaccion=false, dejamos que el caller maneje el rollback
            if ($usarTransaccion) {
                $this->repository->rollBack();
            }

            // Logueamos el error técnico
            $this->logger->error("Error en transacción de inventario", [
                'error' => $e->getMessage(),
                'producto_id' => isset($datos['productos_id']) ? $datos['productos_id'] : 'N/A',
                'usuario_id' => $usuarioId,
                'transaccion_propia' => $usarTransaccion
            ]);

            // Relanzamos la excepción para que el Controller o el caller la maneje
            throw $e;
        }
    }

    /**
     * Obtener listado para el frontend
     */
    public function obtenerHistorial()
    {
        return $this->repository->listarMovimientos();
    }

    /**
     * Obtener un movimiento específico por su ID
     * @param int $movimientoId ID del movimiento
     * @return array Datos del movimiento
     * @throws Exception Si el movimiento no existe
     */
    public function obtenerMovimientoPorId($movimientoId)
    {
        // Validar que el ID sea un número válido
        if (!is_numeric($movimientoId) || $movimientoId <= 0) {
            throw new Exception("ID de movimiento inválido. Debe ser un número positivo.");
        }

        // Buscar el movimiento
        $movimiento = $this->repository->obtenerMovimientoPorId($movimientoId);

        // Si no existe, lanzar excepción
        if ($movimiento === null) {
            throw new Exception("Movimiento con ID {$movimientoId} no encontrado.");
        }

        // Registrar en log (auditoría)
        $this->logger->info("Movimiento consultado", [
            'movimiento_id' => $movimientoId,
            'producto' => $movimiento['productos_nombre']
        ]);

        return $movimiento;
    }

    /**
     * Actualizar un movimiento existente
     * @param int $movimientoId ID del movimiento a actualizar
     * @param array $datos Datos nuevos del movimiento
     * @return bool
     * @throws Exception Si hay errores de validación o negocio
     */
    public function actualizarMovimiento($movimientoId, $datos)
    {
        // 1. Validar que el ID sea válido
        if (!is_numeric($movimientoId) || $movimientoId <= 0) {
            throw new Exception("ID de movimiento inválido.");
        }

        // 2. Verificar que el movimiento existe ANTES de iniciar transacción
        $movimientoActual = $this->repository->obtenerMovimientoPorId($movimientoId);
        if ($movimientoActual === null) {
            throw new Exception("Movimiento con ID {$movimientoId} no encontrado.");
        }

        try {
            // 3. Iniciar transacción
            $this->repository->beginTransaction();

            // 4. Determinar si se está cambiando tipo o cantidad (afecta stock)
            $cambiaStock = isset($datos['movimientos_tipo']) || isset($datos['movimientos_cantidad']);

            if ($cambiaStock) {
                // 5. Obtener datos del producto
                $producto = $this->repository->obtenerDatosProducto($movimientoActual['productos_id']);
                if (!$producto) {
                    throw new Exception("Producto asociado al movimiento no encontrado.");
                }

                // 6. Usar valores actuales si no se envían en $datos
                $tipoActual = $movimientoActual['movimientos_tipo'];
                $cantidadActual = (float)$movimientoActual['movimientos_cantidad'];
                $tipoNuevo = isset($datos['movimientos_tipo']) ? $datos['movimientos_tipo'] : $tipoActual;
                $cantidadNueva = isset($datos['movimientos_cantidad']) ? (float)$datos['movimientos_cantidad'] : $cantidadActual;

                // 7. Recalcular el stock simulando reversa del movimiento actual y aplicación del nuevo
                $stockActual = (float)$producto['productos_stock'];
                
                // Revertir el movimiento actual
                if ($tipoActual === 'ENTRADA') {
                    $stockTemporal = $stockActual - $cantidadActual;
                } else {
                    $stockTemporal = $stockActual + $cantidadActual;
                }

                // Aplicar el nuevo movimiento
                if ($tipoNuevo === 'ENTRADA') {
                    $stockNuevo = $stockTemporal + $cantidadNueva;
                } else {
                    $stockNuevo = $stockTemporal - $cantidadNueva;
                }

                // 8. Validar que el stock no sea negativo
                if ($stockNuevo < 0) {
                    throw new Exception("Stock insuficiente. La actualización resultaría en stock negativo ({$stockNuevo}).");
                }
            }

            // 9. Actualizar el movimiento
            $this->repository->actualizarMovimiento($movimientoId, $datos);

            // 10. Si cambió stock, recalcular y actualizar el stock del producto
            if ($cambiaStock) {
                $this->repository->recalcularStockProducto($movimientoActual['productos_id']);
            }

            // 11. Confirmar transacción
            $this->repository->commit();

            // 12. Log de auditoría
            $logData = [
                'movimiento_id' => $movimientoId,
                'producto_id' => $movimientoActual['productos_id'],
                'cambios' => $datos
            ];
            
            if ($cambiaStock) {
                $logData['tipo_anterior'] = $tipoActual;
                $logData['tipo_nuevo'] = $tipoNuevo;
                $logData['cantidad_anterior'] = $cantidadActual;
                $logData['cantidad_nueva'] = $cantidadNueva;
                $logData['stock_nuevo'] = $stockNuevo;
            }
            
            $this->logger->info("Movimiento actualizado", $logData);

            return true;

        } catch (Exception $e) {
            // Revertir transacción solo si está activa
            if ($this->repository->inTransaction()) {
                $this->repository->rollBack();
            }
            
            // Log del error
            $this->logger->error("Error al actualizar movimiento", [
                'movimiento_id' => $movimientoId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Eliminar un movimiento (Soft Delete)
     * @param int $movimientoId ID del movimiento a eliminar
     * @return bool
     * @throws Exception Si hay errores de validación o negocio
     */
    public function eliminarMovimiento($movimientoId)
    {
        // 1. Validar que el ID sea válido
        if (!is_numeric($movimientoId) || $movimientoId <= 0) {
            throw new Exception("ID de movimiento inválido.");
        }

        // 2. Verificar que el movimiento existe y está activo
        $movimiento = $this->repository->obtenerMovimientoPorId($movimientoId);
        if ($movimiento === null) {
            throw new Exception("Movimiento con ID {$movimientoId} no encontrado.");
        }

        if ($movimiento['movimientos_estado'] == 0) {
            throw new Exception("El movimiento ya está eliminado.");
        }

        try {
            // 3. Iniciar transacción
            $this->repository->beginTransaction();

            // 4. Revertir el movimiento del stock antes de eliminarlo
            $producto = $this->repository->obtenerDatosProducto($movimiento['productos_id']);
            if (!$producto) {
                throw new Exception("Producto asociado al movimiento no encontrado.");
            }

            // Calcular el nuevo stock revirtiendo el movimiento
            $stockActual = (float)$producto['productos_stock'];
            $cantidad = (float)$movimiento['movimientos_cantidad'];
            
            if ($movimiento['movimientos_tipo'] === 'ENTRADA') {
                // Si era ENTRADA, restamos del stock
                $stockNuevo = $stockActual - $cantidad;
            } else {
                // Si era SALIDA, sumamos al stock
                $stockNuevo = $stockActual + $cantidad;
            }

            // Validar que el stock no sea negativo
            if ($stockNuevo < 0) {
                throw new Exception("No se puede eliminar. El stock resultaría negativo ({$stockNuevo}).");
            }

            // 5. Eliminar el movimiento (Soft Delete)
            $this->repository->eliminarMovimiento($movimientoId);

            // 6. Recalcular el stock del producto
            $this->repository->recalcularStockProducto($movimiento['productos_id']);

            // 7. Confirmar transacción
            $this->repository->commit();

            // 8. Log de auditoría
            $this->logger->info("Movimiento eliminado (soft delete)", [
                'movimiento_id' => $movimientoId,
                'producto_id' => $movimiento['productos_id'],
                'tipo' => $movimiento['movimientos_tipo'],
                'cantidad' => $cantidad,
                'stock_resultante' => $stockNuevo
            ]);

            return true;

        } catch (Exception $e) {
            // Revertir transacción solo si está activa
            if ($this->repository->inTransaction()) {
                $this->repository->rollBack();
            }
            
            // Log del error
            $this->logger->error("Error al eliminar movimiento", [
                'movimiento_id' => $movimientoId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}

