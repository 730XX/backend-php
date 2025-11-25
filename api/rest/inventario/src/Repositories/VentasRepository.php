<?php

namespace Inventario\Repositories;

use PDO;

class VentasRepository
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database->getConnection();
    }

    // Nota: No necesitamos beginTransaction aquí porque usaremos la del repositorio de Movimientos
    // o inyectaremos la DB en el servicio. Pero para mantener consistencia, el Service orquestará.

    public function crearCabecera($usuarioId, $total)
    {
        $sql = "INSERT INTO ventas (usuarios_id, ventas_total, ventas_fecha, ventas_estado) 
                VALUES (:user_id, :total, NOW(), 1)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':total', $total);
        $stmt->execute();

        return $this->db->lastInsertId();
    }

    public function crearDetalle($ventaId, $item)
    {
        $sql = "INSERT INTO ventas_detalle (ventas_id, productos_id, detalle_cantidad, detalle_precio_unitario) 
                VALUES (:venta_id, :prod_id, :cant, :precio)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':venta_id', $ventaId, PDO::PARAM_INT);
        $stmt->bindValue(':prod_id', $item['productos_id'], PDO::PARAM_INT);
        $stmt->bindValue(':cant', $item['cantidad']);
        $stmt->bindValue(':precio', $item['precio']);
        $stmt->execute();
    }

    // Método extra para obtener precio actual del producto (seguridad backend)
    public function obtenerPrecioProducto($productoId)
    {
        $sql = "SELECT productos_precio FROM productos WHERE productos_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $productoId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? $res['productos_precio'] : 0;
    }

    /**
     * Obtener información completa del producto para validaciones
     * Incluye precio, stock, estado y nombre
     * @param int $productoId ID del producto
     * @return array|null Datos del producto o null si no existe
     */
    public function obtenerProductoCompleto($productoId)
    {
        $sql = "SELECT 
                    productos_id,
                    productos_nombre,
                    productos_codigo,
                    productos_precio,
                    productos_stock,
                    productos_estado
                FROM productos 
                WHERE productos_id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $productoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado ? $resultado : null;
    }
}
