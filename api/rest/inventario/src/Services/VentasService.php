<?php

namespace Inventario\Services;

use Inventario\Repositories\VentasRepository;
use Inventario\Services\MovimientosService; // <--- INYECCIÓN CLAVE
use Inventario\Utils\Logger;
use Exception;

class VentasService
{
    private $repoVentas;
    private $movimientosService; // Reutilizamos tu lógica existente
    private $logger;
    private $dbConnection; // Necesario para la transacción global

    public function __construct(
        VentasRepository $repoVentas,
        MovimientosService $movimientosService,
        Logger $logger,
        $dbConnection
    ) {
        $this->repoVentas = $repoVentas;
        $this->movimientosService = $movimientosService;
        $this->logger = $logger;
        $this->dbConnection = $dbConnection;
    }

    public function registrarVenta($datos, $usuarioId)
    {
        // 0. VALIDAR USUARIO EXISTE Y ESTÁ ACTIVO
        if (!is_numeric($usuarioId) || $usuarioId <= 0) {
            throw new Exception("ID de usuario inválido para realizar la venta.");
        }

        // 1. PRE-VALIDACIONES ANTES DE INICIAR TRANSACCIÓN (CRÍTICO)
        // Esto evita iniciar transacciones que fallarán, mejorando performance y logs
        $itemsProcesados = [];
        $totalVenta = 0.0;

        foreach ($datos['items'] as $index => $item) {
            $productoId = (int)$item['productos_id'];
            $cantidad = (float)$item['cantidad'];
            $precioEnviado = (float)$item['precio'];

            // 1.1 VERIFICAR QUE EL PRODUCTO EXISTE
            $producto = $this->repoVentas->obtenerProductoCompleto($productoId);
            if (!$producto) {
                throw new Exception("El producto con ID {$productoId} no existe (item #" . ($index + 1) . ").");
            }

            // 1.2 VERIFICAR QUE EL PRODUCTO ESTÁ ACTIVO
            if ($producto['productos_estado'] == 0) {
                throw new Exception("El producto '{$producto['productos_nombre']}' está inactivo y no puede venderse (item #" . ($index + 1) . ").");
            }

            // 1.3 VALIDAR PRECIO CONTRA BASE DE DATOS (SEGURIDAD CRÍTICA)
            $precioReal = (float)$producto['productos_precio'];
            if (abs($precioEnviado - $precioReal) > 0.01) { // Tolerancia de 1 centavo
                $this->logger->warning("Intento de venta con precio manipulado", [
                    'producto_id' => $productoId,
                    'precio_real' => $precioReal,
                    'precio_enviado' => $precioEnviado,
                    'usuario_id' => $usuarioId
                ]);
                throw new Exception("El precio del producto '{$producto['productos_nombre']}' no coincide con el registrado en el sistema (item #" . ($index + 1) . ").");
            }

            // 1.4 VERIFICAR STOCK DISPONIBLE ANTES DE INICIAR TRANSACCIÓN
            $stockActual = (float)$producto['productos_stock'];
            if ($stockActual < $cantidad) {
                throw new Exception("Stock insuficiente para '{$producto['productos_nombre']}'. Disponible: {$stockActual}, Solicitado: {$cantidad} (item #" . ($index + 1) . ").");
            }

            // 1.5 CALCULAR SUBTOTAL CON PRECISIÓN DECIMAL
            $subtotal = round($precioReal * $cantidad, 2);
            $totalVenta = round($totalVenta + $subtotal, 2);

            // Guardar datos procesados y validados
            $itemsProcesados[] = [
                'productos_id' => $productoId,
                'cantidad' => $cantidad,
                'precio' => $precioReal, // Usar precio REAL, no el del frontend
                'subtotal' => $subtotal,
                'producto_nombre' => $producto['productos_nombre']
            ];
        }

        // 1.6 VALIDAR TOTAL FINAL
        if ($totalVenta <= 0) {
            throw new Exception("El total de la venta debe ser mayor a 0.");
        }

        if ($totalVenta > 999999999.99) {
            throw new Exception("El total de la venta excede el límite permitido.");
        }

        // 2. INICIAR TRANSACCIÓN GLOBAL (ahora sí, tras pre-validaciones)
        $this->dbConnection->beginTransaction();

        try {
            // A. Insertar Cabecera de Venta
            $ventaId = $this->repoVentas->crearCabecera($usuarioId, $totalVenta);

            if (!$ventaId || $ventaId <= 0) {
                throw new Exception("Error al crear la cabecera de la venta.");
            }

            // B. Procesar Detalles y MOVER INVENTARIO
            foreach ($itemsProcesados as $index => $item) {

                // B1. Guardar detalle venta
                $this->repoVentas->crearDetalle($ventaId, $item);

                // B2. DISPARAR MOVIMIENTO AUTOMÁTICO (SALIDA)
                $datosMovimiento = [
                    'productos_id' => $item['productos_id'],
                    'movimientos_tipo' => 'SALIDA',
                    'movimientos_motivo' => 'VENTA #' . $ventaId,
                    'movimientos_cantidad' => $item['cantidad'],
                    'movimientos_comentario' => 'Salida automática por punto de venta'
                ];

                // ¡CLAVE! Llamamos al servicio existente con false para NO commitear todavía
                // El MovimientosService NO iniciará su propia transacción
                $this->movimientosService->registrarMovimiento($datosMovimiento, $usuarioId, false);
            }

            // C. Si todo salió bien (Venta + N Movimientos), hacemos COMMIT
            $this->dbConnection->commit();

            // D. Log de Auditoría Completo
            $this->logger->info("Venta registrada y stock actualizado correctamente", [
                'venta_id' => $ventaId,
                'usuario_id' => $usuarioId,
                'total' => $totalVenta,
                'items' => count($itemsProcesados),
                'productos' => array_column($itemsProcesados, 'producto_nombre'),
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            return $ventaId;

        } catch (Exception $e) {
            // E. Si falla algo (ej: Stock insuficiente en MovimientosService), ROLLBACK TODO
            $this->dbConnection->rollBack();
            
            // Log detallado del error
            $this->logger->error("Error al procesar venta", [
                'usuario_id' => $usuarioId,
                'total_intentado' => $totalVenta,
                'items_count' => count($itemsProcesados),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-lanzar para que el Controller responda error
            throw $e;
        }
    }
}
