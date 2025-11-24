# 📬 Colección de Postman - Inventario API CRUD Completo

Esta carpeta contiene la colección completa de Postman con **38 requests** para probar todos los endpoints de la API de Inventario (Productos, Movimientos y Usuarios).

## 📦 Archivos Incluidos

- **Inventario_API.postman_collection.json**: Colección con 38 requests organizados
- **GUIA_POSTMAN.md**: Este archivo con instrucciones de uso

## 🚀 Cómo Importar la Colección

### Método 1: Importar desde Archivo

1. Abre **Postman Desktop** o **Postman Web**
2. Haz clic en **Import** (botón en la esquina superior izquierda)
3. Selecciona la pestaña **File** o arrastra el archivo
4. Selecciona `Inventario_API.postman_collection.json`
5. Haz clic en **Import**
6. ✅ La colección aparecerá como **"Inventario API - CRUD Completo"**

### Método 2: Importar desde Raw JSON

1. Abre el archivo JSON y copia todo su contenido
2. En Postman, haz clic en **Import**
3. Selecciona **Raw text**
4. Pega el contenido JSON completo
5. Haz clic en **Continue** → **Import**

## 📋 Estructura de la Colección (38 Requests)

### 🔓 **Público** (1)
- `01` Health Check - Verificar estado de la API

### 🛡️ **Seguridad - Middleware** (5)
- `02` GET Kardex - Sin API Key (401)
- `03` GET Kardex - API Key Inválida (401)
- `04` GET Kardex - API Key MASTER (200)
- `05` GET Kardex - API Key ADMIN (200)
- `06` GET Kardex - API Key CLIENT (200)

### 📦 **Movimientos (Kardex) - CRUD** (10)
- `07` GET /kardex - Listar todos
- `08` GET /kardex/:id - Obtener por ID (200)
- `09` GET /kardex/:id - ID inexistente (404)
- `10` GET /kardex/:id - ID inválido (400)
- `11` POST /kardex - Crear movimiento (201)
- `12` POST /kardex - Validación: falta campo (400)
- `13` POST /kardex - Validación: cantidad negativa (400)
- `14` PUT /kardex/:id - Actualizar completo (200)
- `15` PUT /kardex/:id - Actualizar parcial (200)
- `16` PUT /kardex/:id - ID inexistente (404)

### 🏷️ **Productos - CRUD Completo** (11)
- `17` GET /productos - Listar todos (200)
- `18` GET /productos/:id - Obtener por ID (200)
- `19` GET /productos/:id - ID inexistente (404)
- `20` POST /productos - Crear producto (201)
- `21` POST /productos - Validación: nombre corto (400)
- `22` POST /productos - Validación: precio negativo (400)
- `23` POST /productos - Validación: unidad inválida (400)
- `24` PUT /productos/:id - Actualizar (200)
- `25` PUT /productos/:id - ID inexistente (404)
- `26` PUT /productos/:id/estado - Desactivar sin movimientos (200)
- `27` PUT /productos/:id/estado - Con movimientos (400)

### 👤 **Usuarios - CRUD Completo** (11)
- `28` GET /usuarios - Listar todos (200)
- `29` GET /usuarios/:id - Obtener por ID (200)
- `30` GET /usuarios/:id - ID inexistente (404)
- `31` POST /usuarios - Crear usuario (201)
- `32` POST /usuarios - Validación: nombre corto (400)
- `33` POST /usuarios - Validación: correo inválido (400)
- `34` POST /usuarios - Validación: password corto (400)
- `35` POST /usuarios - Validación: rol inválido (400)
- `36` POST /usuarios - Validación: correo duplicado (400)
- `37` PUT /usuarios/:id - Actualizar (200)
- `38` PUT /usuarios/:id/estado - Desactivar usuario (200)

## 🔑 Variables Pre-configuradas

La colección incluye 4 variables que puedes usar con `{{variable}}`:

| Variable | Valor | Uso |
|----------|-------|-----|
| `{{base_url}}` | `http://localhost/Proyecto-final/backend-inventario/api/rest/inventario` | URL base de la API |
| `{{api_key_master}}` | `sk_live_master_2024_XyZ123AbC456` | API Key nivel MASTER |
| `{{api_key_admin}}` | `sk_live_admin_2024_DeF789GhI012` | API Key nivel ADMIN |
| `{{api_key_client}}` | `sk_live_client_2024_JkL345MnO678` | API Key nivel CLIENT |

## 🎯 Cómo Usar la Colección

### 1️⃣ Test Rápido - Health Check
```
Request: 01 - Health Check
Método: GET
URL: {{base_url}}/
Headers: (ninguno)
Resultado esperado: 200 OK con info de la API
```

### 2️⃣ Test de Seguridad
```
Request: 02 - GET Kardex - Sin API Key
Método: GET
URL: {{base_url}}/kardex
Headers: (ninguno)
Resultado esperado: 401 Unauthorized
```

### 3️⃣ Listar Productos
```
Request: 17 - GET Productos - Listar Todos
Método: GET
URL: {{base_url}}/productos
Headers: X-API-Key: {{api_key_master}}
Resultado esperado: 200 OK con array de productos
```

### 4️⃣ Crear Producto
```
Request: 20 - POST Productos - Crear
Método: POST
URL: {{base_url}}/productos
Headers: 
  - X-API-Key: {{api_key_master}}
  - Content-Type: application/json
Body: {
  "productos_nombre": "Café Molido Premium",
  "productos_codigo": "CAF001",
  "productos_unidad": "KG",
  "productos_precio": 12.50,
  "productos_stock": 50
}
Resultado esperado: 201 Created con producto_id
```

### 5️⃣ Crear Movimiento
```
Request: 11 - POST Kardex - Crear
Método: POST
URL: {{base_url}}/kardex
Headers: 
  - X-API-Key: {{api_key_master}}
  - Content-Type: application/json
Body: {
  "productos_id": 1,
  "movimientos_tipo": "ENTRADA",
  "movimientos_cantidad": 20,
  "movimientos_motivo": "Compra nueva",
  "movimientos_comentario": "Proveedor principal"
}
Resultado esperado: 201 Created con movimiento registrado
```

## 📊 Campos de Productos

| Campo | Tipo | Requerido | Validación |
|-------|------|-----------|------------|
| `productos_nombre` | string | Sí | Min 3, Max 100 caracteres |
| `productos_codigo` | string | No | Max 50 caracteres, único |
| `productos_unidad` | enum | No | UND, KG, LT, MTS (default: UND) |
| `productos_precio` | decimal | Sí | >= 0, max 9999999.99 |
| `productos_stock` | decimal | No | >= 0 (default: 0) |

## 📊 Campos de Movimientos

| Campo | Tipo | Requerido | Validación |
|-------|------|-----------|------------|
| `productos_id` | int | Sí | Debe existir en productos |
| `movimientos_tipo` | enum | Sí | ENTRADA o SALIDA |
| `movimientos_cantidad` | decimal | Sí | > 0 |
| `movimientos_motivo` | string | Sí | Max 50 caracteres |
| `movimientos_comentario` | string | No | Max 200 caracteres |

## 📊 Campos de Usuarios

| Campo | Tipo | Requerido | Validación |
|-------|------|-----------|------------|
| `usuarios_nombre` | string | Sí | Min 3 caracteres |
| `usuarios_correo` | string | Sí | Formato email válido, único |
| `usuarios_password` | string | Sí | Min 6 caracteres (se hashea automáticamente) |
| `usuarios_rol` | enum | Sí | ADMIN o ALMACENERO |

## 🔄 Códigos de Respuesta HTTP

| Código | Significado | Ejemplo |
|--------|-------------|---------|
| **200** | OK - Operación exitosa | GET, PUT exitoso |
| **201** | Created - Recurso creado | POST exitoso |
| **400** | Bad Request - Datos inválidos | Validaciones fallidas |
| **401** | Unauthorized - Sin/mala API Key | Falta X-API-Key |
| **404** | Not Found - Recurso no existe | ID inexistente |
| **500** | Internal Server Error | Error del servidor |

## 📝 Estructura de Respuesta Estándar

Todas las respuestas siguen este formato:

```json
{
  "tipo": 1,
  "mensajes": ["Mensaje descriptivo"],
  "data": {
    // Datos de la respuesta
  }
}
```

Donde `tipo` puede ser:
- **1** = SUCCESS (verde)
- **2** = WARNING (amarillo)
- **3** = ERROR (rojo)

## 🧪 Orden Recomendado para Pruebas

### Fase 1: Verificación Inicial
1. **Health Check** (request 01) - Verificar conexión
2. **Seguridad** (requests 02-06) - Probar middleware

### Fase 2: Productos
3. **Productos GET** (requests 17-19) - Listar productos
4. **Productos POST** (requests 20-23) - Crear y validar
5. **Productos PUT** (requests 24-25) - Actualizar
6. **Productos Estado** (requests 26-27) - Desactivar

### Fase 3: Movimientos
7. **Movimientos POST** (request 11) - Crear movimientos
8. **Movimientos GET** (requests 07-10) - Consultar
9. **Movimientos PUT** (requests 14-16) - Actualizar

### Fase 4: Usuarios
10. **Usuarios GET** (requests 28-30) - Listar y consultar
11. **Usuarios POST** (requests 31-36) - Crear y validar
12. **Usuarios PUT** (request 37) - Actualizar
13. **Usuarios Estado** (request 38) - Desactivar

## ⚠️ Notas Importantes

1. **API Keys**: Las 3 keys funcionan igual actualmente. Puedes expandir la lógica en el middleware para roles.

2. **Base URL**: Si tu proyecto está en otra ruta, edita la variable `{{base_url}}` en la colección:
   - Click derecho en la colección → Edit
   - Tab "Variables"
   - Modifica el valor de `base_url`

3. **Movimientos**: Al crear/actualizar movimientos, el stock del producto se recalcula automáticamente.

4. **Soft Delete**: Los recursos desactivados mantienen su estado en 0, no se borran físicamente.
   - Productos: `productos_estado = 0`
   - Usuarios: `usuarios_estado = 0`

5. **Integridad**: No puedes desactivar productos que tienen movimientos asociados.

6. **Seguridad de Passwords**: Las contraseñas se hashean automáticamente con `password_hash()` en el backend.

7. **Datos Sensibles**: Las respuestas nunca incluyen passwords ni API keys de usuarios.

## 🐛 Solución de Problemas

**Error: "Could not get response"**
- Verifica que XAMPP esté corriendo
- Confirma la ruta: `http://localhost/Proyecto-final/backend-inventario/api/rest/inventario`

**Error 401 siempre**
- Verifica que el header `X-API-Key` esté presente
- Confirma que el valor coincide con las keys del `.env`

**Error 500 "Database connection failed"**
- Verifica MySQL en XAMPP
- Confirma credenciales en `.env`
- Asegúrate de que la base de datos `proyecto_final` existe

## 📚 Recursos Adicionales

- **Documentación API**: Ver `README.md` en la raíz del proyecto
- **Middleware**: Ver `MIDDLEWARE_APIKEY.md` para detalles de seguridad
- **Logs**: Revisar `/logs/app.log` para debug

---

✨ **¡Listo para probar!** Importa la colección y comienza a testear todos los endpoints.
