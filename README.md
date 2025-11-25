# 📦 API REST - Sistema de Inventario

## 📋 Descripción

Sistema backend profesional de gestión de inventario desarrollado con arquitectura en capas, implementando estándares de seguridad, validación y manejo de errores a nivel empresarial.

El sistema permite la gestión completa de:
- **Productos**: CRUD con control de stock
- **Movimientos de Inventario**: Registro de entradas/salidas con recálculo automático de stock
- **Usuarios**: Gestión de usuarios con roles y autenticación

## 🛠️ Tecnologías

- **PHP** 7.1.33 (compatible con XAMPP)
- **Slim Framework** 2.x - Micro-framework para APIs REST
- **MySQL** 5.7+ - Base de datos relacional
- **PDO** - Capa de abstracción de base de datos
- **Monolog** 1.25 - Sistema de logging profesional
- **Composer** - Gestor de dependencias

## 📁 Arquitectura del Proyecto

```
backend-inventario/
├── api/rest/inventario/
│   ├── index.php              # Punto de entrada principal
│   ├── routes/                # Definición de rutas por módulo
│   │   ├── productos.php
│   │   ├── movimientos.php
│   │   └── usuarios.php
│   ├── src/
│   │   ├── Config/            # Configuración de BD
│   │   ├── Controllers/       # Capa de presentación
│   │   ├── Services/          # Lógica de negocio
│   │   ├── Repositories/      # Acceso a datos
│   │   ├── Smart/             # Validadores
│   │   ├── Models/            # Entidades
│   │   ├── Middleware/        # Seguridad y filtros
│   │   └── Utils/             # Utilidades (Logger, ResponseHelper)
│   └── logs/                  # Archivos de log
├── .env                       # Variables de entorno (NO COMMITEAR)
├── .env.example               # Plantilla de configuración
├── composer.json              # Dependencias PHP
└── postman/                   # Colección de pruebas

```

### Separación de Responsabilidades

| Capa | Responsabilidad |
|------|-----------------|
| **Controllers** | Manejo de peticiones HTTP, validación básica |
| **Services** | Lógica de negocio, transacciones, orquestación |
| **Repositories** | Consultas SQL, acceso a BD |
| **Smart** | Validaciones de datos, reglas de negocio |
| **Middleware** | Autenticación, autorización, CORS |
| **Utils** | Funciones auxiliares (Logger, ResponseHelper) |

## 📦 Instalación

### 1. Requisitos Previos

- XAMPP o servidor con PHP 7.1+
- MySQL 5.7+
- Composer instalado globalmente

### 2. Clonar o Descargar el Proyecto

```bash
cd /opt/lampp/htdocs/Proyecto-final
```

### 3. Instalar Dependencias

```bash
cd backend-inventario
composer install
```

### 4. Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Editar .env con tus credenciales
nano .env
```

Configuración mínima requerida:
```dotenv
DB_HOST=localhost
DB_NAME=proyecto_final
DB_USER=root
DB_PASS=

API_KEY_MASTER=sk_live_master_2024_XyZ123AbC456
API_KEY_ADMIN=sk_live_admin_2024_DeF789GhI012
API_KEY_CLIENT=sk_live_client_2024_JkL345MnO678

DISPLAY_ERROR_DETAILS=true
```

### 5. Importar Base de Datos

```bash
# Importar esquema SQL desde phpMyAdmin o terminal
mysql -u root -p proyecto_final < database/schema.sql
```

**Tablas principales:**
- `productos` - Inventario de productos
- `movimientos` - Kardex de entradas/salidas
- `usuarios` - Usuarios del sistema

### 6. Verificar Instalación

```bash
# Health check (no requiere API Key)
curl http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/
```

Respuesta esperada:
```json
{
    "tipo": 1,
    "mensajes": ["API REST Inventario v1.0.0", "Sistema operativo"],
    "data": {...}
}
```

## 🔐 Seguridad

### API Key Middleware

**Todas las rutas están protegidas** excepto el health check (`GET /`).

Cada petición debe incluir el header:
```
X-API-Key: sk_live_master_2024_XyZ123AbC456
```

### Niveles de API Keys

| Key | Uso | Configuración en .env |
|-----|-----|----------------------|
| MASTER | Acceso total | `API_KEY_MASTER` |
| ADMIN | Operaciones administrativas | `API_KEY_ADMIN` |
| CLIENT | Operaciones de consulta | `API_KEY_CLIENT` |

> ⚠️ **IMPORTANTE**: Cambia las API Keys en producción. Nunca las publiques en repositorios públicos.

### Características de Seguridad

- ✅ Validación de API Key en cada request
- ✅ Ocultamiento de passwords (hash con `password_hash`)
- ✅ Ocultamiento de API Keys en respuestas
- ✅ Manejo seguro de errores (no expone stack traces en producción)
- ✅ SQL preparado (prevención de SQL Injection)
- ✅ Validación estricta de tipos

## 🚀 Endpoints

### Base URL
```
http://localhost/Proyecto-final/backend-inventario/api/rest/inventario
```

### 📦 Productos

| Método | Endpoint | Descripción | Protegido |
|--------|----------|-------------|-----------|
| `GET` | `/productos` | Listar productos activos | ✅ |
| `GET` | `/productos/:id` | Obtener producto por ID | ✅ |
| `POST` | `/productos` | Crear nuevo producto | ✅ |
| `PUT` | `/productos/:id` | Actualizar producto | ✅ |
| `PUT` | `/productos/:id/estado` | Desactivar producto (soft delete) | ✅ |

**Estructura de datos:**
```json
{
    "productos_nombre": "Arroz Costeño",
    "productos_codigo": "ARR001",
    "productos_unidad": "KG",
    "productos_precio": 3.50,
    "productos_stock": 100
}
```

**Validaciones:**
- Nombre: 3-100 caracteres, único
- Código: máximo 50 caracteres, único
- Unidad: UND, KG, LT, MTS
- Precio: ≥ 0
- Stock: numérico

### 📋 Movimientos (Kardex)

| Método | Endpoint | Descripción | Protegido |
|--------|----------|-------------|-----------|
| `GET` | `/kardex` | Listar movimientos | ✅ |
| `GET` | `/kardex/:id` | Obtener movimiento por ID | ✅ |
| `POST` | `/kardex` | Registrar movimiento (actualiza stock) | ✅ |
| `PUT` | `/kardex/:id` | Actualizar movimiento (recalcula stock) | ✅ |

**Estructura de datos:**
```json
{
    "productos_id": 1,
    "usuarios_id": 1,
    "movimientos_tipo": "ENTRADA",
    "movimientos_cantidad": 50,
    "movimientos_motivo": "Compra",
    "movimientos_comentario": "Proveedor ABC"
}
```

**Validaciones:**
- Tipo: ENTRADA, SALIDA
- Cantidad: > 0
- Motivo: COMPRA, VENTA, AJUSTE, MERMA, DEVOLUCION
- Validación de stock suficiente para salidas

### 👤 Usuarios

| Método | Endpoint | Descripción | Protegido |
|--------|----------|-------------|-----------|
| `GET` | `/usuarios` | Listar usuarios activos | ✅ |
| `GET` | `/usuarios/:id` | Obtener usuario por ID | ✅ |
| `POST` | `/usuarios` | Crear nuevo usuario | ✅ |
| `PUT` | `/usuarios/:id` | Actualizar usuario | ✅ |
| `PUT` | `/usuarios/:id/estado` | Desactivar usuario (soft delete) | ✅ |

**Estructura de datos:**
```json
{
    "usuarios_nombre": "Juan Pérez",
    "usuarios_correo": "juan@example.com",
    "usuarios_password": "password123",
    "usuarios_rol": "ALMACENERO"
}
```

**Validaciones:**
- Nombre: mínimo 3 caracteres
- Correo: formato válido, único
- Password: mínimo 6 caracteres (se hashea automáticamente)
- Rol: ADMIN, ALMACENERO

## 📄 Formato de Respuestas

Todas las respuestas siguen el formato institucional:

### Respuesta Exitosa
```json
{
    "tipo": 1,
    "mensajes": ["Operación exitosa"],
    "data": { ... }
}
```

### Error Funcional (400, 404)
```json
{
    "tipo": 3,
    "mensajes": ["El producto no fue encontrado"],
    "data": null
}
```

### Error Interno (500)
```json
{
    "tipo": 2,
    "mensajes": ["Error interno del servidor"],
    "data": {
        "file": "/path/to/file.php",
        "line": 123,
        "trace": "..."
    }
}
```

### Códigos HTTP

| Código | Significado |
|--------|-------------|
| `200` | OK - Operación exitosa |
| `201` | Created - Recurso creado |
| `400` | Bad Request - Validación fallida |
| `401` | Unauthorized - API Key inválida |
| `404` | Not Found - Recurso no encontrado |
| `500` | Internal Server Error - Error del servidor |

## 📝 Logging

### Ubicación
```
/api/rest/inventario/logs/app.log
```

### Tipos de Logs

**Eventos Funcionales:**
```
[INFO] Producto creado {"producto_id": 5, "nombre": "Leche Entera"}
[INFO] Stock actualizado {"producto_id": 1, "stock_anterior": 100, "stock_nuevo": 150}
```

**Errores Técnicos:**
```
[ERROR] Error al crear producto {"error": "Duplicate entry 'ARR001' for key 'productos_codigo'"}
[ERROR] Conexión a BD fallida {"host": "localhost", "db": "proyecto_final"}
```

### Configuración

El sistema usa **Monolog** con nivel DEBUG en desarrollo.

Para producción, modificar en `src/Utils/Logger.php`:
```php
$this->logger->pushHandler(new StreamHandler($logPath, Monolog::WARNING));
```

## 🧪 Pruebas con Postman

### Importar Colección

1. Abrir Postman
2. Click en **Import**
3. Seleccionar archivo: `/postman/Inventario_API.postman_collection.json`
4. Configurar variable de entorno `apiKey` con tu API Key

### Colección Incluye

- ✅ Health Check (sin autenticación)
- ✅ 5 pruebas de seguridad (API Key)
- ✅ 11 pruebas de productos (CRUD + validaciones)
- ✅ 10 pruebas de movimientos (CRUD + stock)
- ✅ 11 pruebas de usuarios (CRUD + validaciones)

**Total: 37 requests de prueba**

### Documentación Detallada

Ver: `/postman/GUIA_POSTMAN.md`

## 🔧 Troubleshooting

### Error: "API Key inválida"
```bash
# Verificar que el header esté presente
curl -H "X-API-Key: TU_KEY_AQUI" http://localhost/.../productos
```

### Error: "SQLSTATE[HY000] [1045] Access denied"
- Verificar credenciales en `.env`
- Verificar que MySQL esté corriendo

### Error: "Class 'Monolog\Logger' not found"
```bash
composer install
```

### Error 404 en todas las rutas
- Verificar que `.htaccess` esté presente
- Verificar que `mod_rewrite` esté habilitado en Apache

### Los logs no se generan
```bash
# Dar permisos de escritura
chmod -R 775 api/rest/inventario/logs/
```

## 📚 Documentación Adicional

- [`/postman/GUIA_POSTMAN.md`](postman/GUIA_POSTMAN.md) - Guía completa de uso de Postman
- [`/api/rest/inventario/ESTRUCTURA_RESPUESTAS.md`](api/rest/inventario/ESTRUCTURA_RESPUESTAS.md) - Especificación del formato de respuestas
- [`/api/rest/inventario/MIDDLEWARE_APIKEY.md`](api/rest/inventario/MIDDLEWARE_APIKEY.md) - Documentación del sistema de autenticación

## 🚀 Despliegue en Producción

### Checklist de Seguridad

- [ ] Cambiar todas las API Keys
- [ ] Configurar `DISPLAY_ERROR_DETAILS=false`
- [ ] Revisar permisos de archivos (644 para PHP, 755 para directorios)
- [ ] Configurar nivel de logs a WARNING o ERROR
- [ ] Habilitar HTTPS
- [ ] Configurar CORS según dominios permitidos
- [ ] Realizar backup de base de datos
- [ ] Configurar rotación de logs

## 👨‍💻 Desarrollo

### Agregar un Nuevo Módulo

1. Crear Modelo en `/src/Models/`
2. Crear Repository en `/src/Repositories/`
3. Crear Service en `/src/Services/`
4. Crear Smart (validador) en `/src/Smart/`
5. Crear Controller en `/src/Controllers/`
6. Crear archivo de rutas en `/routes/`
7. Registrar rutas en `index.php`

### Estándares de Código

- PSR-4 para autoloading
- Nombres de clases en PascalCase
- Métodos y variables en camelCase
- Comentarios PHPDoc obligatorios
- Transacciones para operaciones críticas
- Logging de todas las operaciones importantes

## 📞 Soporte

Para dudas o problemas:
- Revisar logs en `/api/rest/inventario/logs/app.log`
- Consultar documentación en `/postman/GUIA_POSTMAN.md`
- Verificar variables de entorno en `.env`

---

## 📄 Licencia

Proyecto Final - Curso de Desarrollo Backend con Slim Framework

**Desarrollado por:** [Elder Cardoza]  
**Fecha:** 24 Noviembre 2025  
**Versión:** 1.0.0
