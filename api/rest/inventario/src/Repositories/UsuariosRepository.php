<?php

namespace Inventario\Repositories;

use PDO;
use Exception;

class UsuariosRepository
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database->getConnection();
    }

    // --- TRANSACCIONES ---
    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }
    public function commit()
    {
        $this->db->commit();
    }
    public function rollBack()
    {
        $this->db->rollBack();
    }
    public function inTransaction()
    {
        return $this->db->inTransaction();
    }

    // --- CONSULTAS ---

    public function obtenerTodos()
    {
        // Solo traemos usuarios activos
        $sql = "SELECT usuarios_id, usuarios_nombre, usuarios_correo, usuarios_rol, usuarios_estado, usuarios_creado
                FROM usuarios 
                WHERE usuarios_estado = 1
                ORDER BY usuarios_nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT * FROM usuarios WHERE usuarios_id = :id AND usuarios_estado = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtener usuario por correo (incluye password para autenticación)
     */
    public function obtenerPorCorreo($correo)
    {
        $sql = "SELECT * FROM usuarios WHERE usuarios_correo = :correo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function existePorCorreo($correo, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE usuarios_correo = :correo AND usuarios_estado = 1";
        if ($excludeId !== null) {
            $sql .= " AND usuarios_id != :exclude_id";
        }

        $stmt = $this->db->prepare($sql);
        $params = [':correo' => $correo];
        if ($excludeId !== null) $params[':exclude_id'] = $excludeId;

        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res['total'] > 0;
    }

    // --- ESCRITURA ---

    public function crear($datos)
    {
        // Nota: La contraseña YA debe venir hasheada desde el Service
        $sql = "INSERT INTO usuarios 
                (usuarios_nombre, usuarios_correo, usuarios_password, usuarios_rol, usuarios_estado, usuarios_apikey, usuarios_creado)
                VALUES (:nombre, :correo, :pass, :rol, 1, :apikey, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => $datos['usuarios_nombre'],
            ':correo' => $datos['usuarios_correo'],
            ':pass'   => $datos['usuarios_password'], // Hash
            ':rol'    => $datos['usuarios_rol'],
            ':apikey' => $datos['usuarios_apikey'] ?? null // Opcional si se genera aquí o en service
        ]);

        return $this->db->lastInsertId();
    }

    public function actualizar($id, $datos)
    {
        $campos = [];
        $params = [':id' => $id];

        if (isset($datos['usuarios_nombre'])) {
            $campos[] = "usuarios_nombre = :nombre";
            $params[':nombre'] = $datos['usuarios_nombre'];
        }
        if (isset($datos['usuarios_correo'])) {
            $campos[] = "usuarios_correo = :correo";
            $params[':correo'] = $datos['usuarios_correo'];
        }
        if (isset($datos['usuarios_password'])) {
            $campos[] = "usuarios_password = :pass";
            $params[':pass'] = $datos['usuarios_password'];
        }
        if (isset($datos['usuarios_rol'])) {
            $campos[] = "usuarios_rol = :rol";
            $params[':rol'] = $datos['usuarios_rol'];
        }

        if (empty($campos)) return true;

        $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE usuarios_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function cambiarEstado($id, $estado)
    {
        $sql = "UPDATE usuarios SET usuarios_estado = :estado WHERE usuarios_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':estado' => $estado]);
    }
}
