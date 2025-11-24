<?php

namespace Inventario\Models;

/**
 * Modelo de datos para Usuario
 * Representa la estructura de la tabla usuarios
 */
class Usuario
{
    public $usuarios_id;
    public $usuarios_nombre;
    public $usuarios_correo;
    public $usuarios_password; // Hash
    public $usuarios_apikey;
    public $usuarios_rol;      // ADMIN, ALMACENERO
    public $usuarios_estado;   // 1, 0
    public $usuarios_creado;

    public function __construct($data = [])
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function estaActivo()
    {
        return $this->usuarios_estado == 1;
    }

    public function esAdmin()
    {
        return $this->usuarios_rol === 'ADMIN';
    }

    /**
     * Convierte el objeto a array (Ocultando datos sensibles)
     */
    public function toArray()
    {
        return [
            'usuarios_id' => $this->usuarios_id,
            'usuarios_nombre' => $this->usuarios_nombre,
            'usuarios_correo' => $this->usuarios_correo,
            'usuarios_rol' => $this->usuarios_rol,
            'usuarios_estado' => $this->usuarios_estado,
            'usuarios_creado' => $this->usuarios_creado
            // NO devolvemos password ni apikey por seguridad
        ];
    }
}
