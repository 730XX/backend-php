-- ==================================================
-- ESTRUCTURA DE BASE DE DATOS REQUERIDA
-- Módulo de Ventas - Sistema de Inventario
-- ==================================================

-- TABLA: ventas (Cabecera de venta)
-- Si no existe, crear con:

CREATE TABLE IF NOT EXISTS `ventas` (
  `ventas_id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuarios_id` INT(11) NOT NULL,
  `ventas_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ventas_estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activa, 0=Anulada',
  PRIMARY KEY (`ventas_id`),
  INDEX `idx_ventas_usuario` (`usuarios_id`),
  INDEX `idx_ventas_fecha` (`ventas_fecha`),
  INDEX `idx_ventas_estado` (`ventas_estado`),
  CONSTRAINT `fk_ventas_usuario` 
    FOREIGN KEY (`usuarios_id`) 
    REFERENCES `usuarios` (`usuarios_id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLA: ventas_detalle (Detalle de productos vendidos)
-- Si no existe, crear con:

CREATE TABLE IF NOT EXISTS `ventas_detalle` (
  `detalle_id` INT(11) NOT NULL AUTO_INCREMENT,
  `ventas_id` INT(11) NOT NULL,
  `productos_id` INT(11) NOT NULL,
  `detalle_cantidad` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  `detalle_precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`detalle_id`),
  INDEX `idx_detalle_venta` (`ventas_id`),
  INDEX `idx_detalle_producto` (`productos_id`),
  CONSTRAINT `fk_detalle_venta` 
    FOREIGN KEY (`ventas_id`) 
    REFERENCES `ventas` (`ventas_id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_producto` 
    FOREIGN KEY (`productos_id`) 
    REFERENCES `productos` (`productos_id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================
-- VERIFICACIÓN DE TABLAS EXISTENTES
-- ==================================================

-- Las siguientes tablas DEBEN existir previamente:

-- TABLA: productos
SELECT 'productos' AS tabla, COUNT(*) AS registros FROM productos;
-- Campos requeridos:
-- - productos_id (INT, PK)
-- - productos_nombre (VARCHAR)
-- - productos_codigo (VARCHAR)
-- - productos_precio (DECIMAL(10,2))
-- - productos_stock (DECIMAL(10,3))
-- - productos_estado (TINYINT, 1=activo, 0=inactivo)
-- - productos_unidad (VARCHAR)

-- TABLA: usuarios
SELECT 'usuarios' AS tabla, COUNT(*) AS registros FROM usuarios;
-- Campos requeridos:
-- - usuarios_id (INT, PK)
-- - usuarios_nombre (VARCHAR)
-- - usuarios_estado (TINYINT, 1=activo, 0=inactivo)

-- TABLA: kardex (Movimientos de inventario)
SELECT 'kardex' AS tabla, COUNT(*) AS registros FROM kardex;
-- Campos requeridos:
-- - movimientos_id (INT, PK)
-- - productos_id (INT, FK)
-- - usuarios_id (INT, FK)
-- - movimientos_tipo (ENUM 'ENTRADA' o 'SALIDA')
-- - movimientos_cantidad (DECIMAL(10,3))
-- - movimientos_motivo (VARCHAR)
-- - movimientos_comentario (TEXT, nullable)
-- - movimientos_stock_historico (DECIMAL(10,3))
-- - movimientos_fecha (DATETIME)
-- - movimientos_estado (TINYINT)

-- ==================================================
-- ÍNDICES RECOMENDADOS (Si no existen)
-- ==================================================

-- Mejorar performance de consultas de ventas
CREATE INDEX IF NOT EXISTS idx_ventas_fecha ON ventas(ventas_fecha);
CREATE INDEX IF NOT EXISTS idx_ventas_usuario ON ventas(usuarios_id);
CREATE INDEX IF NOT EXISTS idx_ventas_estado ON ventas(ventas_estado);

-- Mejorar performance de consultas de detalles
CREATE INDEX IF NOT EXISTS idx_detalle_venta ON ventas_detalle(ventas_id);
CREATE INDEX IF NOT EXISTS idx_detalle_producto ON ventas_detalle(productos_id);

-- Mejorar performance de búsqueda de movimientos por venta
CREATE INDEX IF NOT EXISTS idx_kardex_motivo ON kardex(movimientos_motivo);

-- ==================================================
-- CONSULTAS DE VERIFICACIÓN POST-IMPLEMENTACIÓN
-- ==================================================

-- Verificar que una venta se creó correctamente:
SELECT 
    v.ventas_id,
    v.ventas_fecha,
    v.ventas_total,
    u.usuarios_nombre,
    COUNT(vd.detalle_id) AS productos_vendidos,
    SUM(vd.detalle_cantidad * vd.detalle_precio_unitario) AS total_calculado
FROM ventas v
INNER JOIN usuarios u ON v.usuarios_id = u.usuarios_id
LEFT JOIN ventas_detalle vd ON v.ventas_id = vd.ventas_id
WHERE v.ventas_id = ? -- Reemplazar ? con ID de venta
GROUP BY v.ventas_id;

-- Verificar detalles de una venta:
SELECT 
    vd.detalle_id,
    p.productos_nombre,
    p.productos_codigo,
    vd.detalle_cantidad,
    vd.detalle_precio_unitario,
    (vd.detalle_cantidad * vd.detalle_precio_unitario) AS subtotal
FROM ventas_detalle vd
INNER JOIN productos p ON vd.productos_id = p.productos_id
WHERE vd.ventas_id = ?; -- Reemplazar ? con ID de venta

-- Verificar movimientos generados automáticamente:
SELECT 
    k.movimientos_id,
    k.movimientos_fecha,
    p.productos_nombre,
    k.movimientos_tipo,
    k.movimientos_cantidad,
    k.movimientos_motivo,
    k.movimientos_stock_historico AS stock_despues
FROM kardex k
INNER JOIN productos p ON k.productos_id = p.productos_id
WHERE k.movimientos_motivo LIKE 'VENTA%'
ORDER BY k.movimientos_id DESC
LIMIT 10;

-- Verificar que stock se actualizó:
SELECT 
    p.productos_id,
    p.productos_nombre,
    p.productos_stock AS stock_actual,
    SUM(CASE WHEN k.movimientos_tipo = 'ENTRADA' THEN k.movimientos_cantidad ELSE 0 END) AS total_entradas,
    SUM(CASE WHEN k.movimientos_tipo = 'SALIDA' THEN k.movimientos_cantidad ELSE 0 END) AS total_salidas,
    (
        SUM(CASE WHEN k.movimientos_tipo = 'ENTRADA' THEN k.movimientos_cantidad ELSE 0 END) -
        SUM(CASE WHEN k.movimientos_tipo = 'SALIDA' THEN k.movimientos_cantidad ELSE 0 END)
    ) AS stock_calculado
FROM productos p
LEFT JOIN kardex k ON p.productos_id = k.productos_id
WHERE k.movimientos_estado = 1
GROUP BY p.productos_id
HAVING ABS(p.productos_stock - stock_calculado) > 0.001; -- Debe retornar 0 filas si está correcto

-- ==================================================
-- DATOS DE PRUEBA (OPCIONAL)
-- ==================================================

-- Insertar productos de prueba si no existen:
INSERT IGNORE INTO productos 
(productos_id, productos_nombre, productos_codigo, productos_precio, productos_stock, productos_unidad, productos_estado) 
VALUES
(1, 'Producto Test A', 'TEST-001', 15.50, 100.000, 'und', 1),
(2, 'Producto Test B', 'TEST-002', 8.00, 50.000, 'kg', 1),
(3, 'Producto Test C', 'TEST-003', 25.00, 75.500, 'L', 1);

-- Insertar usuario de prueba si no existe:
INSERT IGNORE INTO usuarios 
(usuarios_id, usuarios_nombre, usuarios_email, usuarios_estado) 
VALUES
(1, 'Usuario Test', 'test@example.com', 1);

-- ==================================================
-- SCRIPT DE LIMPIEZA (SOLO PARA PRUEBAS)
-- ==================================================

-- ⚠️ CUIDADO: Este script elimina TODAS las ventas de prueba
-- Solo ejecutar en entorno de desarrollo

-- DELETE FROM ventas_detalle WHERE ventas_id IN (
--     SELECT ventas_id FROM ventas WHERE usuarios_id = 1 AND ventas_fecha >= '2025-11-25'
-- );
-- 
-- DELETE FROM kardex WHERE movimientos_motivo LIKE 'VENTA%' AND movimientos_fecha >= '2025-11-25';
-- 
-- DELETE FROM ventas WHERE usuarios_id = 1 AND ventas_fecha >= '2025-11-25';
