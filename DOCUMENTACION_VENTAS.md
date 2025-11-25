# 📦 Módulo de Ventas - Documentación Técnica

## 🎯 Resumen Ejecutivo

Se ha implementado un **módulo de ventas completo** que:
- ✅ Registra ventas con múltiples productos
- ✅ Actualiza automáticamente el inventario (genera movimientos SALIDA)
- ✅ Garantiza integridad transaccional (todo o nada)
- ✅ Valida stock, precios, productos activos y más
- ✅ Incluye auditoría completa con logs estructurados

---

## 🏗️ Arquitectura

### Capas Implementadas
```
┌─────────────────────────────────────┐
│   Controller (VentasController)     │  ← Recibe HTTP POST /ventas
├─────────────────────────────────────┤
│   Smart (VentasSmart)               │  ← Valida estructura JSON
├─────────────────────────────────────┤
│   Service (VentasService)           │  ← Lógica de negocio + transacción
│                                      │    ├─ Valida precios vs BD
│                                      │    ├─ Verifica stock disponible
│                                      │    └─ Llama MovimientosService
├─────────────────────────────────────┤
│   Repository (VentasRepository)     │  ← SQL: INSERT ventas + detalles
└─────────────────────────────────────┘
           ↓ (reutiliza)
┌─────────────────────────────────────┐
│   MovimientosService                │  ← Genera SALIDA automática
├─────────────────────────────────────┤
│   MovimientosRepository             │  ← UPDATE productos.stock
│                                      │    INSERT kardex
└─────────────────────────────────────┘
```

### Flujo Transaccional
```sql
BEGIN TRANSACTION;
  -- 1. Insertar cabecera venta
  INSERT INTO ventas (...);
  
  -- 2. Por cada producto:
  INSERT INTO ventas_detalle (...);
  UPDATE productos SET productos_stock = stock - cantidad;
  INSERT INTO kardex (tipo='SALIDA', motivo='VENTA #X');
  
  -- 3. Si todo OK:
COMMIT;
  -- Si falla algo:
ROLLBACK;
```

---

## 📋 Validaciones Implementadas

### VentasSmart (Capa de Validación de Entrada)
✅ **Estructura básica**
- Items es obligatorio y debe ser array no vacío
- Máximo 100 items por venta (seguridad)

✅ **Por cada item**
- `productos_id`: Obligatorio, numérico, mayor a 0
- `cantidad`: Obligatoria, entre 0.001 y 999,999.999
- `precio`: Obligatorio, entre 0.01 y 999,999.99
- Subtotal no puede exceder límite (overflow protection)

✅ **Validaciones adicionales**
- Detecta productos duplicados en la misma venta
- Valida longitud de campos opcionales (cliente_nombre, observaciones)
- Sanitización de strings

### VentasService (Capa de Negocio)
✅ **Pre-validaciones antes de transacción**
1. Usuario válido (ID numérico > 0)
2. Producto existe en BD
3. Producto está activo (estado = 1)
4. **CRÍTICO**: Precio enviado coincide con BD (tolerancia 1 centavo)
5. Stock disponible >= cantidad solicitada
6. Total de venta entre 0.01 y 999,999,999.99

✅ **Durante la transacción**
- Usa precio REAL de BD (ignora precio del frontend si no coincide)
- Redondeo correcto a 2 decimales para montos
- Control de precisión decimal (float -> round)

---

## 🔒 Seguridad Implementada

### 1. Validación de Precios (Anti-Hackeo)
```php
// Frontend envía: precio: 1.00
// Backend verifica contra BD: productos_precio = 15.50
// Si no coinciden → ERROR + LOG de advertencia
```
**Motivo**: Evita que modifiquen precios desde el frontend (DevTools, Postman, etc.)

### 2. Validación de Stock Pre-Transacción
```php
// ANTES de iniciar BEGIN TRANSACTION
foreach ($productos as $p) {
    if (stock_actual < cantidad_solicitada) {
        throw Exception("Stock insuficiente");
    }
}
// Evita transacciones destinadas a fallar
```
**Beneficio**: Mejor performance, menos locks en BD

### 3. Header X-User-Id Obligatorio
```php
if (!$usuarioId || $usuarioId <= 0) {
    return 401 Unauthorized
}
```
**Nota**: En producción, esto debería venir de un JWT validado.

### 4. Límites de Datos
- Máximo 100 items por venta
- Validación de rangos numéricos
- Longitud de strings controlada
- Protección contra overflow de subtotales

---

## 🧪 Casos de Prueba

### ✅ Caso 1: Venta Exitosa
```bash
POST http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/ventas
Headers:
  X-API-Key: sk_live_master_2024_XyZ123AbC456
  X-User-Id: 1
  Content-Type: application/json

Body:
{
  "items": [
    {
      "productos_id": 1,
      "cantidad": 2,
      "precio": 15.50
    },
    {
      "productos_id": 3,
      "cantidad": 1.5,
      "precio": 8.00
    }
  ],
  "cliente_nombre": "Juan Pérez",
  "observaciones": "Entrega urgente"
}

Respuesta Esperada: 201 Created
{
  "tipo": 1,
  "mensajes": ["Venta procesada exitosamente", "Inventario actualizado automáticamente"],
  "data": {
    "venta_id": 5,
    "mensaje": "Venta registrada correctamente",
    "timestamp": "2025-11-25 10:30:45"
  }
}
```

**Verificaciones en BD**:
```sql
-- 1. Se creó la venta
SELECT * FROM ventas WHERE ventas_id = 5;
-- ventas_total = 43.00 (2*15.50 + 1.5*8.00)

-- 2. Se crearon los detalles
SELECT * FROM ventas_detalle WHERE ventas_id = 5;
-- 2 registros

-- 3. Se actualizó el stock
SELECT productos_id, productos_stock FROM productos WHERE productos_id IN (1,3);
-- Stock disminuyó correctamente

-- 4. Se generaron movimientos SALIDA
SELECT * FROM kardex 
WHERE movimientos_motivo LIKE 'VENTA #5%' 
  AND movimientos_tipo = 'SALIDA';
-- 2 registros
```

---

### ❌ Caso 2: Stock Insuficiente (Rollback)
```json
{
  "items": [
    {
      "productos_id": 1,
      "cantidad": 1000,  // Más de lo disponible
      "precio": 15.50
    }
  ]
}

Respuesta: 400 Bad Request
{
  "tipo": 3,
  "mensajes": ["Stock insuficiente para 'Producto X'. Disponible: 50, Solicitado: 1000 (item #1)."],
  "data": null
}
```

**Verificación**: NO se creó venta, NO se afectó el stock.

---

### ❌ Caso 3: Precio Manipulado
```json
{
  "items": [
    {
      "productos_id": 1,
      "cantidad": 2,
      "precio": 1.00  // Precio real en BD: 15.50
    }
  ]
}

Respuesta: 400 Bad Request
{
  "tipo": 3,
  "mensajes": ["El precio del producto 'Producto X' no coincide con el registrado en el sistema (item #1)."],
  "data": null
}
```

**Log de Advertencia**:
```
[WARNING] Intento de venta con precio manipulado
{
  "producto_id": 1,
  "precio_real": 15.50,
  "precio_enviado": 1.00,
  "usuario_id": 1,
  "timestamp": "..."
}
```

---

### ❌ Caso 4: Producto Inactivo
```json
{
  "items": [
    {
      "productos_id": 5,  // Producto con estado = 0
      "cantidad": 1,
      "precio": 10.00
    }
  ]
}

Respuesta: 400 Bad Request
{
  "tipo": 3,
  "mensajes": ["El producto 'Producto Descontinuado' está inactivo y no puede venderse (item #1)."],
  "data": null
}
```

---

### ❌ Caso 5: Producto Duplicado
```json
{
  "items": [
    {
      "productos_id": 1,
      "cantidad": 2,
      "precio": 15.50
    },
    {
      "productos_id": 1,  // Duplicado
      "cantidad": 1,
      "precio": 15.50
    }
  ]
}

Respuesta: 400 Bad Request
{
  "tipo": 3,
  "mensajes": ["El producto con ID 1 está duplicado en la venta (item #2)."],
  "data": null
}
```

---

### ❌ Caso 6: Validaciones de Estructura
```json
// Sin header X-User-Id
Respuesta: 401 Unauthorized

// Items vacío
{ "items": [] }
Respuesta: 400 "La venta debe contener al menos un producto."

// Cantidad negativa
{ "items": [{ "productos_id": 1, "cantidad": -5, "precio": 10 }] }
Respuesta: 400 "La cantidad del item #1 debe estar entre 0.001 y 999999.999."

// Sin precio
{ "items": [{ "productos_id": 1, "cantidad": 2 }] }
Respuesta: 400 "El item #1 no tiene un precio válido. El precio es obligatorio."
```

---

## 📊 Auditoría y Logs

### Logs de Éxito
```json
[INFO] Venta registrada y stock actualizado correctamente
{
  "venta_id": 5,
  "usuario_id": 1,
  "total": 43.00,
  "items": 2,
  "productos": ["Producto A", "Producto B"],
  "timestamp": "2025-11-25 10:30:45"
}
```

### Logs de Error
```json
[ERROR] Error al procesar venta
{
  "usuario_id": 1,
  "total_intentado": 15500.00,
  "items_count": 1,
  "error": "Stock insuficiente para 'Producto X'. Disponible: 50, Solicitado: 1000 (item #1).",
  "trace": "..."
}
```

### Logs de Advertencia (Seguridad)
```json
[WARNING] Intento de venta con precio manipulado
{
  "producto_id": 1,
  "precio_real": 15.50,
  "precio_enviado": 1.00,
  "usuario_id": 1
}
```

---

## 🔧 Integración con MovimientosService

### Parámetro `usarTransaccion`
```php
// MovimientosService
public function registrarMovimiento($datos, $usuarioId, $usarTransaccion = true)
```

**Comportamiento**:
- `usarTransaccion = true` (default): MovimientosService maneja su propia transacción
  - Inicia `BEGIN`
  - Ejecuta `COMMIT` si éxito
  - Ejecuta `ROLLBACK` si error

- `usarTransaccion = false`: VentasService es el orquestador
  - MovimientosService NO inicia transacción
  - NO hace commit
  - NO hace rollback
  - Solo ejecuta las operaciones
  - Deja control al caller (VentasService)

**Ventaja**: Un solo COMMIT/ROLLBACK para toda la operación de venta.

---

## 🚨 Posibles Mejoras Futuras

### 1. Autenticación JWT
```php
// En lugar de X-User-Id, usar JWT
$token = $headers->get('Authorization');
$payload = JWT::decode($token);
$usuarioId = $payload->user_id;
```

### 2. Método de Anulación
```php
POST /ventas/:id/cancelar
// Revierte movimientos y devuelve stock
```

### 3. Listar Ventas
```php
GET /ventas?page=1&per_page=20&fecha_desde=2025-11-01
```

### 4. Reportes
```php
GET /ventas/reporte?tipo=diario&fecha=2025-11-25
```

### 5. Validar Usuario contra BD
```php
// En VentasService
$usuario = $this->repoUsuarios->obtenerPorId($usuarioId);
if (!$usuario || $usuario['usuarios_estado'] == 0) {
    throw new Exception("Usuario inválido o inactivo");
}
```

### 6. Descuentos y Promociones
```json
{
  "items": [...],
  "descuento_porcentaje": 10,
  "cupon": "PROMO2025"
}
```

### 7. Métodos de Pago
```json
{
  "items": [...],
  "metodo_pago": "efectivo|tarjeta|transferencia"
}
```

---

## ✅ Checklist de Validación para el Ingeniero

### Funcionalidad
- [ ] Venta exitosa crea registro en `ventas`
- [ ] Venta exitosa crea N registros en `ventas_detalle`
- [ ] Venta exitosa actualiza `productos.productos_stock`
- [ ] Venta exitosa crea N movimientos en `kardex` con tipo=SALIDA
- [ ] Total calculado en backend coincide con suma de subtotales
- [ ] Stock insuficiente causa rollback completo
- [ ] Precio manipulado es detectado y rechazado
- [ ] Producto inactivo no puede venderse
- [ ] Producto inexistente causa error 400

### Seguridad
- [ ] Precio se valida contra BD (no confía en frontend)
- [ ] Header X-User-Id es obligatorio
- [ ] Límites de cantidad y precio están configurados
- [ ] Validación de productos duplicados funciona
- [ ] Logs de advertencia se generan en intentos sospechosos

### Transaccionalidad
- [ ] Rollback funciona si falla en el 3er producto de 5
- [ ] No quedan registros huérfanos tras rollback
- [ ] Stock no se descuadra nunca
- [ ] Movimientos del kardex coinciden con el total vendido

### Performance
- [ ] Pre-validaciones evitan transacciones destinadas a fallar
- [ ] Consultas preparadas (prepared statements) previenen SQL injection
- [ ] No hay N+1 queries en el loop de items

### Auditoría
- [ ] Logs INFO registran ventas exitosas con detalles
- [ ] Logs ERROR registran fallos con contexto completo
- [ ] Logs WARNING registran intentos de manipulación
- [ ] Timestamp incluido en todos los logs

---

## 📝 Notas Finales

### Lo que SÍ hace este módulo:
✅ Registra ventas con múltiples productos  
✅ Actualiza inventario automáticamente  
✅ Garantiza consistencia con transacciones  
✅ Valida todo en backend (no confía en frontend)  
✅ Audita operaciones con logs estructurados  
✅ Maneja errores con códigos HTTP apropiados  
✅ Reutiliza lógica existente (MovimientosService)  

### Lo que NO hace (por ahora):
❌ Autenticación JWT (usa header simple X-User-Id)  
❌ Anular ventas  
❌ Listar ventas  
❌ Reportes de ventas  
❌ Gestión de clientes  
❌ Descuentos o cupones  
❌ Métodos de pago  

### Recomendaciones para Producción:
1. Implementar JWT para autenticación
2. Validar usuario contra BD
3. Agregar campo `metodo_pago` a tabla ventas
4. Implementar soft delete para anulaciones
5. Agregar índices en BD:
   ```sql
   CREATE INDEX idx_ventas_fecha ON ventas(ventas_fecha);
   CREATE INDEX idx_ventas_usuario ON ventas(usuarios_id);
   CREATE INDEX idx_detalle_venta ON ventas_detalle(ventas_id);
   ```

---

**Implementado por**: Sistema de Inventario v1.0  
**Fecha**: 25 de noviembre de 2025  
**Tecnologías**: PHP 7.1.33 + Slim 2 + PDO + Monolog
