<?php

namespace Inventario\Repositories;

use PDO;
use Exception;

/**
 * Repositorio de Movimientos
 * Encargado EXCLUSIVAMENTE de la interacción SQL con la base de datos.
 * Utiliza PDO con Sentencias Preparadas para evitar Inyección SQL.
 */
class MovimientosRepository
{

    private $db;

    // Inyección de dependencia: Recibe la conexión lista de Database.php
    public function __construct($database)
    {
        $this->db = $database->getConnection();
    }

    // --- GESTIÓN DE TRANSACCIONES (Vital para mantener la consistencia) ---

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
     * Obtener todos los movimientos
     * @return array Lista de movimientos con información de producto
     */
    public function obtenerTodos()
    {
        $sql = "SELECT m.movimientos_id, m.productos_id, m.usuarios_id,
                       m.movimientos_tipo, m.movimientos_cantidad,
                       m.movimientos_motivo, m.movimientos_comentario, m.movimientos_fecha,
                       p.productos_nombre, p.productos_codigo
                FROM movimientos m
                INNER JOIN productos p ON m.productos_id = p.productos_id
                ORDER BY m.movimientos_fecha DESC, m.movimientos_id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un producto para conocer su stock actual y estado.
     * Se usa antes de registrar cualquier movimiento.
     */
    public function obtenerDatosProducto($productoId)
    {
        $sql = "SELECT productos_id, productos_nombre, productos_stock, productos_estado 
                FROM productos 
                WHERE productos_id = :id 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $productoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza el stock físico en la tabla maestra de productos.
     */
    public function actualizarStockProducto($productoId, $nuevoStock)
    {
        $sql = "UPDATE productos 
                SET productos_stock = :nuevo_stock 
                WHERE productos_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nuevo_stock', $nuevoStock); // PDO detecta decimal/string
        $stmt->bindValue(':id', $productoId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Inserta el registro de auditoría en la tabla movimientos.
     * Guarda la 'foto' del stock resultante (stock_historico).
     */
    public function guardarMovimiento($datos, $stockResultante, $usuarioId)
    {
        $sql = "INSERT INTO movimientos (
                    productos_id, 
                    usuarios_id, 
                    movimientos_tipo, 
                    movimientos_motivo, 
                    movimientos_cantidad, 
                    movimientos_stock_historico, 
                    movimientos_comentario,
                    movimientos_fecha
                ) VALUES (
                    :prod_id,
                    :user_id,
                    :tipo,
                    :motivo,
                    :cantidad,
                    :historico,
                    :comentario,
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);

        // Mapeo de valores (Binding)
        $stmt->bindValue(':prod_id',    $datos['productos_id'], PDO::PARAM_INT);
        $stmt->bindValue(':user_id',    $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo',       $datos['movimientos_tipo']);
        $stmt->bindValue(':motivo',     $datos['movimientos_motivo']);
        $stmt->bindValue(':cantidad',   $datos['movimientos_cantidad']);
        $stmt->bindValue(':historico',  $stockResultante);
        $stmt->bindValue(':comentario', isset($datos['movimientos_comentario']) ? $datos['movimientos_comentario'] : null);

        $stmt->execute();

        // Retornamos el ID del movimiento creado para confirmar
        return $this->db->lastInsertId();
    }

    /**
     * Obtiene el historial completo (Kardex) con nombres de productos y usuarios.
     * Útil para el GET /kardex
     */
    public function listarMovimientos()
    {
        $sql = "SELECT 
                    m.movimientos_id,
                    m.movimientos_fecha,
                    p.productos_nombre,
                    u.usuarios_nombre,
                    m.movimientos_tipo,
                    m.movimientos_cantidad,
                    m.movimientos_stock_historico,
                    m.movimientos_motivo
                FROM movimientos m
                INNER JOIN productos p ON m.productos_id = p.productos_id
                INNER JOIN usuarios u ON m.usuarios_id = u.usuarios_id
                ORDER BY m.movimientos_fecha DESC
                LIMIT 100"; // Limitamos por seguridad/rendimiento inicial

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un movimiento específico por su ID
     * @param int $movimientoId ID del movimiento a buscar
     * @return array|null Datos del movimiento o null si no existe
     */
    public function obtenerMovimientoPorId($movimientoId)
    {
        $sql = "SELECT 
                    m.movimientos_id,
                    m.movimientos_fecha,
                    m.productos_id,
                    p.productos_nombre,
                    p.productos_codigo,
                    p.productos_unidad,
                    m.usuarios_id,
                    u.usuarios_nombre,
                    m.movimientos_tipo,
                    m.movimientos_cantidad,
                    m.movimientos_stock_historico,
                    m.movimientos_motivo,
                    m.movimientos_comentario
                FROM movimientos m
                INNER JOIN productos p ON m.productos_id = p.productos_id
                INNER JOIN usuarios u ON m.usuarios_id = u.usuarios_id
                WHERE m.movimientos_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $movimientoId]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no existe, retornar null
        return $resultado ? $resultado : null;
    }

    /**
     * Actualiza un movimiento existente
     * @param int $movimientoId ID del movimiento a actualizar
     * @param array $datos Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function actualizarMovimiento($movimientoId, $datos)
    {
        // Construir UPDATE dinámico con solo los campos que vienen en $datos
        $campos = [];
        $parametros = [':id' => $movimientoId];
        
        if (isset($datos['movimientos_tipo'])) {
            $campos[] = "movimientos_tipo = :tipo";
            $parametros[':tipo'] = $datos['movimientos_tipo'];
        }
        
        if (isset($datos['movimientos_cantidad'])) {
            $campos[] = "movimientos_cantidad = :cantidad";
            $parametros[':cantidad'] = $datos['movimientos_cantidad'];
        }
        
        if (isset($datos['movimientos_motivo'])) {
            $campos[] = "movimientos_motivo = :motivo";
            $parametros[':motivo'] = $datos['movimientos_motivo'];
        }
        
        if (isset($datos['movimientos_comentario'])) {
            $campos[] = "movimientos_comentario = :comentario";
            $parametros[':comentario'] = $datos['movimientos_comentario'];
        }
        
        if (empty($campos)) {
            return true; // No hay nada que actualizar
        }
        
        $sql = "UPDATE movimientos SET " . implode(', ', $campos) . " WHERE movimientos_id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($parametros);
    }

    /**
     * Recalcula y actualiza el stock de un producto basado en todos sus movimientos
     * @param int $productoId ID del producto
     * @return bool
     */
    public function recalcularStockProducto($productoId)
    {
        // Calcular el stock total basado en todos los movimientos
        $sqlCalcular = "SELECT 
                            SUM(CASE WHEN movimientos_tipo = 'ENTRADA' THEN movimientos_cantidad ELSE 0 END) -
                            SUM(CASE WHEN movimientos_tipo = 'SALIDA' THEN movimientos_cantidad ELSE 0 END) as stock_calculado
                        FROM movimientos
                        WHERE productos_id = :producto_id";
        
        $stmt = $this->db->prepare($sqlCalcular);
        $stmt->execute([':producto_id' => $productoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $stockCalculado = $resultado['stock_calculado'] ?? 0;

        // Actualizar el stock del producto
        $sqlActualizar = "UPDATE productos SET productos_stock = :stock WHERE productos_id = :id";
        $stmt = $this->db->prepare($sqlActualizar);
        
        return $stmt->execute([
            ':stock' => $stockCalculado,
            ':id' => $productoId
        ]);
    }
}
