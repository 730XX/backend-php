<?php

namespace Inventario\Smart;

use Exception;

/**
 * Capa Smart: Validaciones higiénicas para Usuarios
 */
class UsuariosSmart
{
    /**
     * Validar datos para crear un usuario
     */
    public function validarCreacion($data)
    {
        // 1. Campos obligatorios
        if (empty($data['usuarios_nombre'])) throw new Exception("El nombre es obligatorio.");
        if (empty($data['usuarios_correo'])) throw new Exception("El correo es obligatorio.");
        if (empty($data['usuarios_password'])) throw new Exception("La contraseña es obligatoria.");
        if (empty($data['usuarios_rol'])) throw new Exception("El rol es obligatorio.");

        // 2. Validar Nombre
        if (strlen(trim($data['usuarios_nombre'])) < 3) throw new Exception("El nombre debe tener al menos 3 caracteres.");

        // 3. Validar Correo
        if (!filter_var($data['usuarios_correo'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del correo electrónico no es válido.");
        }

        // 4. Validar Password (mínimo 6 caracteres)
        if (strlen($data['usuarios_password']) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres.");
        }

        // 5. Validar Rol
        $rolesValidos = ['ADMIN', 'ALMACENERO'];
        if (!in_array($data['usuarios_rol'], $rolesValidos, true)) {
            throw new Exception("El rol debe ser: ADMIN o ALMACENERO.");
        }

        return true;
    }

    /**
     * Validar datos para actualizar un usuario
     */
    public function validarActualizacion($data)
    {
        if (empty($data)) throw new Exception("No hay datos para actualizar.");

        // Validar Nombre si viene
        if (isset($data['usuarios_nombre'])) {
            if (strlen(trim($data['usuarios_nombre'])) < 3) throw new Exception("El nombre debe tener al menos 3 caracteres.");
        }

        // Validar Correo si viene
        if (isset($data['usuarios_correo'])) {
            if (!filter_var($data['usuarios_correo'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El formato del correo electrónico no es válido.");
            }
        }

        // Validar Password si viene (Opcional en update)
        if (isset($data['usuarios_password'])) {
            if (strlen($data['usuarios_password']) < 6) {
                throw new Exception("La contraseña debe tener al menos 6 caracteres.");
            }
        }

        // Validar Rol si viene
        if (isset($data['usuarios_rol'])) {
            $rolesValidos = ['ADMIN', 'ALMACENERO'];
            if (!in_array($data['usuarios_rol'], $rolesValidos, true)) {
                throw new Exception("El rol debe ser: ADMIN o ALMACENERO.");
            }
        }

        return true;
    }
}
