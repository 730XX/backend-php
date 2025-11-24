<?php
namespace Inventario\Models;

/**
 * Modelo de datos para Producto
 * Representa la estructura de la tabla productos
 */
class Producto
{
    // ID único del producto
    public $productos_id;
    
    // Nombre del producto
    public $productos_nombre;
    
    // Código del producto
    public $productos_codigo;
    
    // Unidad de medida (UND, KG, LT, MTS)
    public $productos_unidad;
    
    // Precio del producto
    public $productos_precio;
    
    // Stock actual del producto
    public $productos_stock;
    
    // Estado: 1 = Activo, 0 = Eliminado (Soft Delete)
    public $productos_estado;
    
    /**
     * Constructor
     */
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
    
    /**
     * Verifica si el producto está activo
     */
    public function estaActivo()
    {
        return $this->productos_estado == 1;
    }
    
    /**
     * Verifica si hay stock disponible
     */
    public function tieneStock($cantidadRequerida = 1)
    {
        return $this->productos_stock >= $cantidadRequerida;
    }
    
    /**
     * Convierte el objeto a array
     */
    public function toArray()
    {
        return [
            'productos_id' => $this->productos_id,
            'productos_nombre' => $this->productos_nombre,
            'productos_codigo' => $this->productos_codigo,
            'productos_unidad' => $this->productos_unidad,
            'productos_precio' => $this->productos_precio,
            'productos_stock' => $this->productos_stock,
            'productos_estado' => $this->productos_estado
        ];
    }
}
