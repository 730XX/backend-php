<?php
namespace Inventario\Services;

use Inventario\Repositories\ProductosRepository;
use Inventario\Utils\Logger;
use Exception;

/**
 * Servicio de lógica de negocio para Productos
 */
class ProductosService
{
    private $repository;
    private $logger;

    public function __construct(ProductosRepository $repository, Logger $logger)
    {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Obtener todos los productos activos
     * @return array Lista de productos
     */
    public function obtenerTodos()
    {
        $this->logger->info("Obteniendo lista de productos");
        return $this->repository->obtenerTodos();
    }

    /**
     * Obtener un producto por ID
     * @param int $productoId ID del producto
     * @return array Datos del producto
     * @throws Exception Si el producto no existe
     */
    public function obtenerPorId($productoId)
    {
        if (!is_numeric($productoId) || $productoId <= 0) {
            throw new Exception("ID de producto inválido.");
        }

        $producto = $this->repository->obtenerPorId($productoId);
        
        if ($producto === null) {
            throw new Exception("Producto con ID {$productoId} no encontrado.");
        }

        $this->logger->info("Producto obtenido", ['producto_id' => $productoId]);
        return $producto;
    }

    /**
     * Crear un nuevo producto
     * @param array $datos Datos del producto
     * @return int ID del producto creado
     * @throws Exception Si hay errores de validación o negocio
     */
    public function crear($datos)
    {
        // Validar que el nombre no esté duplicado
        if ($this->repository->existePorNombre($datos['productos_nombre'])) {
            throw new Exception("Ya existe un producto con el nombre '{$datos['productos_nombre']}'.");
        }

        try {
            $this->repository->beginTransaction();

            // Crear el producto
            $productoId = $this->repository->crear($datos);

            $this->repository->commit();

            $this->logger->info("Producto creado", [
                'producto_id' => $productoId,
                'nombre' => $datos['productos_nombre'],
                'precio' => $datos['productos_precio'],
                'stock_inicial' => $datos['productos_stock'] ?? 0
            ]);

            return $productoId;

        } catch (Exception $e) {
            if ($this->repository->inTransaction()) {
                $this->repository->rollBack();
            }

            $this->logger->error("Error al crear producto", [
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);

            throw $e;
        }
    }

    /**
     * Actualizar un producto existente
     * @param int $productoId ID del producto
     * @param array $datos Datos a actualizar
     * @return bool
     * @throws Exception Si hay errores de validación o negocio
     */
    public function actualizar($productoId, $datos)
    {
        if (!is_numeric($productoId) || $productoId <= 0) {
            throw new Exception("ID de producto inválido.");
        }

        // Verificar que el producto existe
        $producto = $this->repository->obtenerPorId($productoId);
        if ($producto === null) {
            throw new Exception("Producto con ID {$productoId} no encontrado.");
        }

        // Validar nombre duplicado (si se está actualizando el nombre)
        if (isset($datos['productos_nombre'])) {
            if ($this->repository->existePorNombre($datos['productos_nombre'], $productoId)) {
                throw new Exception("Ya existe otro producto con el nombre '{$datos['productos_nombre']}'.");
            }
        }

        try {
            $this->repository->beginTransaction();

            // Actualizar el producto
            $this->repository->actualizar($productoId, $datos);

            $this->repository->commit();

            $this->logger->info("Producto actualizado", [
                'producto_id' => $productoId,
                'cambios' => $datos
            ]);

            return true;

        } catch (Exception $e) {
            if ($this->repository->inTransaction()) {
                $this->repository->rollBack();
            }

            $this->logger->error("Error al actualizar producto", [
                'producto_id' => $productoId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Cambiar estado de un producto
     * @param int $productoId ID del producto
     * @param int $nuevoEstado Nuevo estado (0 = inactivo, 1 = activo)
     * @return bool
     * @throws Exception Si hay errores de validación o negocio
     */
    public function cambiarEstado($productoId, $nuevoEstado)
    {
        if (!is_numeric($productoId) || $productoId <= 0) {
            throw new Exception("ID de producto inválido.");
        }

        // Verificar que el producto existe
        $producto = $this->repository->obtenerPorId($productoId);
        if ($producto === null) {
            throw new Exception("Producto con ID {$productoId} no encontrado.");
        }

        // Si se está desactivando (estado = 0), validar que no tenga movimientos
        if ($nuevoEstado == 0 && $this->repository->tieneMovimientos($productoId)) {
            throw new Exception("No se puede desactivar el producto porque tiene movimientos asociados.");
        }

        try {
            $this->repository->beginTransaction();

            // Cambiar estado del producto
            $this->repository->cambiarEstado($productoId, $nuevoEstado);

            $this->repository->commit();

            $this->logger->info("Estado del producto actualizado", [
                'producto_id' => $productoId,
                'nombre' => $producto['productos_nombre'],
                'nuevo_estado' => $nuevoEstado
            ]);

            return true;

        } catch (Exception $e) {
            if ($this->repository->inTransaction()) {
                $this->repository->rollBack();
            }

            $this->logger->error("Error al cambiar estado del producto", [
                'producto_id' => $productoId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
