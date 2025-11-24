<?php

namespace Inventario\Smart;

use Exception;

/**
 * Capa Smart: Responsable EXCLUSIVAMENTE de la validación de datos de entrada.
 * No realiza consultas a BD ni lógica de negocio compleja.
 */
class MovimientosSmart
{

    /**
     * Valida los datos recibidos del frontend para registrar un movimiento.
     * * @param array $data Los datos del cuerpo JSON (decodificados)
     * @throws Exception Si alguna validación falla.
     * @return bool True si todo es correcto.
     */
    public function validarCreacion($data)
    {

        // 1. Validar Campos Obligatorios
        // Deben coincidir con los names de tu formulario Angular y la BD
        $camposObligatorios = ['productos_id', 'movimientos_tipo', 'movimientos_cantidad', 'movimientos_motivo'];

        foreach ($camposObligatorios as $campo) {
            if (!isset($data[$campo]) || trim($data[$campo]) === '') {
                throw new Exception("El campo '{$campo}' es obligatorio y no puede estar vacío.");
            }
        }

        // 2. Validar ID del Producto
        if (!is_numeric($data['productos_id']) || $data['productos_id'] <= 0) {
            throw new Exception("El ID del producto no es válido.");
        }

        // 3. Validar Tipo de Movimiento (ENUM)
        // Solo permitimos lo que la base de datos soporta
        $tiposPermitidos = ['ENTRADA', 'SALIDA'];
        if (!in_array($data['movimientos_tipo'], $tiposPermitidos)) {
            throw new Exception("El tipo de movimiento debe ser 'ENTRADA' o 'SALIDA'. Valor recibido: " . $data['movimientos_tipo']);
        }

        // 4. Validar Cantidad (CRÍTICO: Rúbrica Punto 6)
        // No permitimos ceros ni negativos, ni letras.
        if (!is_numeric($data['movimientos_cantidad'])) {
            throw new Exception("La cantidad debe ser un valor numérico.");
        }

        // Convertimos a float para comparar
        $cantidad = (float) $data['movimientos_cantidad'];

        if ($cantidad <= 0) {
            throw new Exception("La cantidad del movimiento debe ser mayor a 0.");
        }

        // 5. Validar Longitud de Motivo
        // La BD tiene un VARCHAR(50), si mandan 200 caracteres, la BD explota. Validamos aquí.
        if (strlen($data['movimientos_motivo']) > 50) {
            throw new Exception("El motivo no puede exceder los 50 caracteres.");
        }

        // Si pasa todo esto, los datos son higiénicos.
        return true;
    }

    /**
     * Validar datos para actualizar un movimiento
     * @param array $data Datos a validar
     * @return bool true si es válido
     * @throws Exception si hay errores de validación
     */
    public function validarActualizacion($data) {
        // Validar tipo de movimiento
        if (isset($data['movimientos_tipo'])) {
            if (!in_array($data['movimientos_tipo'], ['ENTRADA', 'SALIDA'], true)) {
                throw new Exception("El tipo de movimiento debe ser 'ENTRADA' o 'SALIDA'.");
            }
        }

        // Validar cantidad
        if (isset($data['movimientos_cantidad'])) {
            if (!is_numeric($data['movimientos_cantidad'])) {
                throw new Exception("La cantidad debe ser un número válido.");
            }
            
            $cantidad = floatval($data['movimientos_cantidad']);
            if ($cantidad <= 0) {
                throw new Exception("La cantidad debe ser mayor a cero.");
            }
        }

        // Validar motivo
        if (isset($data['movimientos_motivo'])) {
            if (empty(trim($data['movimientos_motivo']))) {
                throw new Exception("El motivo es requerido.");
            }
            
            if (strlen($data['movimientos_motivo']) > 50) {
                throw new Exception("El motivo no puede exceder los 50 caracteres.");
            }
        }

        // Validar comentario (opcional)
        if (isset($data['movimientos_comentario']) && !empty($data['movimientos_comentario'])) {
            if (strlen($data['movimientos_comentario']) > 200) {
                throw new Exception("El comentario no puede exceder los 200 caracteres.");
            }
        }

        return true;
    }
}
