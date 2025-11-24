<?php
namespace Inventario\Smart;

use Exception;

/**
 * Capa Smart: Validaciones higiénicas para Productos
 * Verifica la limpieza y formato de datos ANTES de llegar al Service
 */
class ProductosSmart
{
    /**
     * Validar datos para crear un producto
     * @param array $data Datos a validar
     * @return bool true si es válido
     * @throws Exception si hay errores de validación
     */
    public function validarCreacion($data)
    {
        // 1. Validar que vengan los campos obligatorios
        if (empty($data['productos_nombre'])) {
            throw new Exception("El nombre del producto es obligatorio.");
        }

        if (!isset($data['productos_precio'])) {
            throw new Exception("El precio del producto es obligatorio.");
        }

        // 2. Validar el nombre
        if (strlen(trim($data['productos_nombre'])) < 3) {
            throw new Exception("El nombre del producto debe tener al menos 3 caracteres.");
        }

        if (strlen($data['productos_nombre']) > 100) {
            throw new Exception("El nombre del producto no puede exceder los 100 caracteres.");
        }

        // 3. Validar código (opcional)
        if (isset($data['productos_codigo']) && !empty($data['productos_codigo'])) {
            if (strlen($data['productos_codigo']) > 50) {
                throw new Exception("El código no puede exceder los 50 caracteres.");
            }
        }

        // 4. Validar unidad (opcional, por defecto UND)
        if (isset($data['productos_unidad'])) {
            $unidadesValidas = ['UND', 'KG', 'LT', 'MTS'];
            if (!in_array($data['productos_unidad'], $unidadesValidas, true)) {
                throw new Exception("La unidad debe ser: UND, KG, LT o MTS.");
            }
        }

        // 4. Validar precio
        if (!is_numeric($data['productos_precio'])) {
            throw new Exception("El precio debe ser un número válido.");
        }

        $precio = floatval($data['productos_precio']);
        if ($precio < 0) {
            throw new Exception("El precio no puede ser negativo.");
        }

        if ($precio > 9999999.99) {
            throw new Exception("El precio excede el límite permitido.");
        }

        // 5. Validar stock inicial (opcional, por defecto será 0)
        if (isset($data['productos_stock'])) {
            if (!is_numeric($data['productos_stock'])) {
                throw new Exception("El stock debe ser un número válido.");
            }

            $stock = floatval($data['productos_stock']);
            if ($stock < 0) {
                throw new Exception("El stock no puede ser negativo.");
            }
        }

        return true;
    }

    /**
     * Validar datos para actualizar un producto
     * @param array $data Datos a validar
     * @return bool true si es válido
     * @throws Exception si hay errores de validación
     */
    public function validarActualizacion($data)
    {
        // Al menos un campo debe venir para actualizar
        if (empty($data)) {
            throw new Exception("No hay datos para actualizar.");
        }

        // Validar nombre (si viene)
        if (isset($data['productos_nombre'])) {
            if (empty(trim($data['productos_nombre']))) {
                throw new Exception("El nombre del producto no puede estar vacío.");
            }

            if (strlen(trim($data['productos_nombre'])) < 3) {
                throw new Exception("El nombre del producto debe tener al menos 3 caracteres.");
            }

            if (strlen($data['productos_nombre']) > 100) {
                throw new Exception("El nombre del producto no puede exceder los 100 caracteres.");
            }
        }

        // Validar código (si viene)
        if (isset($data['productos_codigo']) && !empty($data['productos_codigo'])) {
            if (strlen($data['productos_codigo']) > 50) {
                throw new Exception("El código no puede exceder los 50 caracteres.");
            }
        }

        // Validar unidad (si viene)
        if (isset($data['productos_unidad'])) {
            $unidadesValidas = ['UND', 'KG', 'LT', 'MTS'];
            if (!in_array($data['productos_unidad'], $unidadesValidas, true)) {
                throw new Exception("La unidad debe ser: UND, KG, LT o MTS.");
            }
        }

        // Validar precio (si viene)
        if (isset($data['productos_precio'])) {
            if (!is_numeric($data['productos_precio'])) {
                throw new Exception("El precio debe ser un número válido.");
            }

            $precio = floatval($data['productos_precio']);
            if ($precio < 0) {
                throw new Exception("El precio no puede ser negativo.");
            }

            if ($precio > 9999999.99) {
                throw new Exception("El precio excede el límite permitido.");
            }
        }

        // Validar stock (si viene) - NO se recomienda actualizar directamente el stock
        // Se debe usar movimientos de entrada/salida
        if (isset($data['productos_stock'])) {
            if (!is_numeric($data['productos_stock'])) {
                throw new Exception("El stock debe ser un número válido.");
            }

            $stock = floatval($data['productos_stock']);
            if ($stock < 0) {
                throw new Exception("El stock no puede ser negativo.");
            }
        }

        return true;
    }
}
