<?php

namespace Inventario\Smart;

use Exception;

class VentasSmart
{
    // Constantes de validación
    const MAX_ITEMS = 100;
    const MAX_CANTIDAD = 999999.999;
    const MIN_CANTIDAD = 0.001;
    const MAX_PRECIO = 999999.99;
    const MIN_PRECIO = 0.01;

    public function validarCreacion($data)
    {
        // 1. VALIDAR ESTRUCTURA BÁSICA
        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            throw new Exception("La venta debe contener al menos un producto.");
        }

        // 2. VALIDAR LÍMITE DE ITEMS (Seguridad)
        if (count($data['items']) > self::MAX_ITEMS) {
            throw new Exception("La venta no puede contener más de " . self::MAX_ITEMS . " productos.");
        }

        // 3. VALIDAR CADA ITEM
        $productosIds = [];
        foreach ($data['items'] as $index => $item) {
            $posicion = $index + 1;

            // 3.1 Validar ID de Producto
            if (!isset($item['productos_id']) || !is_numeric($item['productos_id']) || $item['productos_id'] <= 0) {
                throw new Exception("El item #{$posicion} no tiene un ID de producto válido.");
            }

            // 3.2 Validar duplicados en la misma venta
            if (in_array($item['productos_id'], $productosIds)) {
                throw new Exception("El producto con ID {$item['productos_id']} está duplicado en la venta (item #{$posicion}).");
            }
            $productosIds[] = $item['productos_id'];

            // 3.3 Validar CANTIDAD (OBLIGATORIA y con rango)
            if (!isset($item['cantidad']) || !is_numeric($item['cantidad'])) {
                throw new Exception("El item #{$posicion} no tiene una cantidad válida.");
            }

            $cantidad = (float)$item['cantidad'];
            if ($cantidad < self::MIN_CANTIDAD || $cantidad > self::MAX_CANTIDAD) {
                throw new Exception("La cantidad del item #{$posicion} debe estar entre " . self::MIN_CANTIDAD . " y " . self::MAX_CANTIDAD . ".");
            }

            // 3.4 Validar PRECIO (OBLIGATORIO - no confiar solo en el frontend)
            if (!isset($item['precio']) || !is_numeric($item['precio'])) {
                throw new Exception("El item #{$posicion} no tiene un precio válido. El precio es obligatorio.");
            }

            $precio = (float)$item['precio'];
            if ($precio < self::MIN_PRECIO || $precio > self::MAX_PRECIO) {
                throw new Exception("El precio del item #{$posicion} debe estar entre " . self::MIN_PRECIO . " y " . self::MAX_PRECIO . ".");
            }

            // 3.5 Validar que el subtotal no cause overflow
            $subtotal = $cantidad * $precio;
            if (!is_finite($subtotal) || $subtotal > 999999999.99) {
                throw new Exception("El subtotal del item #{$posicion} excede el límite permitido.");
            }
        }

        // 4. VALIDAR CAMPOS OPCIONALES SI EXISTEN
        if (isset($data['cliente_nombre'])) {
            $data['cliente_nombre'] = trim($data['cliente_nombre']);
            if (strlen($data['cliente_nombre']) > 200) {
                throw new Exception("El nombre del cliente no puede exceder 200 caracteres.");
            }
        }

        if (isset($data['observaciones'])) {
            $data['observaciones'] = trim($data['observaciones']);
            if (strlen($data['observaciones']) > 500) {
                throw new Exception("Las observaciones no pueden exceder 500 caracteres.");
            }
        }

        return true;
    }
}
