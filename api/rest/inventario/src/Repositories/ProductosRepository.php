<?php
namespace Inventario\Repositories;

use PDO;
use Exception;

/**
 * Repositorio para gestionar productos en la base de datos
 */
class ProductosRepository
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

    // --- MÉTODOS DE CONSULTA ---

    /**
     * Obtener todos los productos activos
     * @return array Lista de productos
     */
    public function obtenerTodos()
    {
        $sql = "SELECT productos_id, productos_nombre, productos_codigo, productos_unidad,
                       productos_precio, productos_stock, productos_estado, productos_creado
                FROM productos 
                WHERE productos_estado = 1
                ORDER BY productos_nombre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un producto por ID
     * @param int $productoId ID del producto
     * @return array|null Datos del producto o null si no existe
     */
    public function obtenerPorId($productoId)
    {
        $sql = "SELECT productos_id, productos_nombre, productos_codigo, productos_unidad,
                       productos_precio, productos_stock, productos_estado, productos_creado
                FROM productos 
                WHERE productos_id = :id AND productos_estado = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $productoId]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Verificar si un producto existe por nombre (para evitar duplicados)
     * @param string $nombre Nombre del producto
     * @param int|null $excludeId ID a excluir (para actualización)
     * @return bool
     */
    public function existePorNombre($nombre, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM productos 
                WHERE productos_nombre = :nombre 
                AND productos_estado = 1";
        
        if ($excludeId !== null) {
            $sql .= " AND productos_id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [':nombre' => $nombre];
        
        if ($excludeId !== null) {
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['total'] > 0;
    }

    // --- MÉTODOS DE ESCRITURA ---

    /**
     * Crear un nuevo producto
     * @param array $datos Datos del producto
     * @return int ID del producto creado
     */
    public function crear($datos)
    {
        $sql = "INSERT INTO productos 
                (productos_nombre, productos_codigo, productos_unidad, productos_precio, productos_stock, productos_estado)
                VALUES (:nombre, :codigo, :unidad, :precio, :stock, 1)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => $datos['productos_nombre'],
            ':codigo' => $datos['productos_codigo'] ?? null,
            ':unidad' => $datos['productos_unidad'] ?? 'UND',
            ':precio' => $datos['productos_precio'],
            ':stock' => $datos['productos_stock'] ?? 0
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * Actualizar un producto existente
     * @param int $productoId ID del producto
     * @param array $datos Datos a actualizar
     * @return bool
     */
    public function actualizar($productoId, $datos)
    {
        // Construir UPDATE dinámico con solo los campos que vienen en $datos
        $campos = [];
        $parametros = [':id' => $productoId];
        
        if (isset($datos['productos_nombre'])) {
            $campos[] = "productos_nombre = :nombre";
            $parametros[':nombre'] = $datos['productos_nombre'];
        }
        
        if (isset($datos['productos_codigo'])) {
            $campos[] = "productos_codigo = :codigo";
            $parametros[':codigo'] = $datos['productos_codigo'];
        }
        
        if (isset($datos['productos_unidad'])) {
            $campos[] = "productos_unidad = :unidad";
            $parametros[':unidad'] = $datos['productos_unidad'];
        }
        
        if (isset($datos['productos_precio'])) {
            $campos[] = "productos_precio = :precio";
            $parametros[':precio'] = $datos['productos_precio'];
        }
        
        if (isset($datos['productos_stock'])) {
            $campos[] = "productos_stock = :stock";
            $parametros[':stock'] = $datos['productos_stock'];
        }
        
        if (empty($campos)) {
            return true; // No hay nada que actualizar
        }
        
        $sql = "UPDATE productos SET " . implode(', ', $campos) . " WHERE productos_id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($parametros);
    }

    /**
     * Cambiar estado de un producto
     * @param int $productoId ID del producto
     * @param int $nuevoEstado Nuevo estado (0 = inactivo, 1 = activo)
     * @return bool
     */
    public function cambiarEstado($productoId, $nuevoEstado)
    {
        $sql = "UPDATE productos 
                SET productos_estado = :estado 
                WHERE productos_id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $productoId,
            ':estado' => $nuevoEstado
        ]);
    }

    /**
     * Verificar si un producto tiene movimientos asociados
     * @param int $productoId ID del producto
     * @return bool
     */
    public function tieneMovimientos($productoId)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM movimientos 
                WHERE productos_id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $productoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['total'] > 0;
    }
}
