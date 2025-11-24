# Middleware de API Key - Guía de Uso

## 📋 Descripción

El Middleware de API Key protege todos los endpoints de la API requiriendo una clave de autenticación válida en cada petición.

## 🔐 Configuración

### 1. Configurar API Keys en .env

Edita el archivo `.env` y define tus API Keys:

```env
API_KEY_MASTER=sk_live_master_2024_XyZ123AbC456
API_KEY_ADMIN=sk_live_admin_2024_DeF789GhI012
API_KEY_CLIENT=sk_live_client_2024_JkL345MnO678
```

**⚠️ IMPORTANTE**: 
- Cambia estas keys en producción
- Nunca compartas tus API Keys en repositorios públicos
- Usa keys de al menos 32 caracteres

### 2. Rutas Protegidas

En `index.php`, todas las rutas con el middleware están protegidas:

```php
// Ruta protegida
$app->get('/kardex', $protegerRutas, function() use ($controller, $app) {
    $controller->getAll();
});
```

### 3. Rutas Públicas

Las rutas sin el middleware son públicas (no requieren API Key):

```php
// Ruta pública
$app->get('/', function() use ($app) {
    // No requiere API Key
});
```

## 🚀 Cómo Usar la API Key

### Opción 1: Header HTTP (RECOMENDADO)

Envía la API Key en el header `X-API-Key`:

```bash
curl -X GET "http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/kardex" \
  -H "X-API-Key: sk_live_master_2024_XyZ123AbC456"
```

### Opción 2: Postman

1. Abre tu colección en Postman
2. Ve a la pestaña **Headers**
3. Agrega:
   - **Key**: `X-API-Key`
   - **Value**: `sk_live_master_2024_XyZ123AbC456`

### Opción 3: JavaScript/Fetch

```javascript
fetch('http://localhost/api/kardex', {
  method: 'GET',
  headers: {
    'X-API-Key': 'sk_live_master_2024_XyZ123AbC456',
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

### Opción 4: Angular HttpClient

```typescript
import { HttpClient, HttpHeaders } from '@angular/common/http';

const headers = new HttpHeaders({
  'X-API-Key': 'sk_live_master_2024_XyZ123AbC456'
});

this.http.get('/api/kardex', { headers })
  .subscribe(data => console.log(data));
```

## ❌ Respuestas de Error

### Sin API Key

**Request:**
```bash
curl -X GET "http://localhost/api/kardex"
```

**Response (401):**
```json
{
  "tipo": 3,
  "mensajes": [
    "API Key requerida. Envíe el header X-API-Key",
    "Acceso no autorizado"
  ],
  "data": null
}
```

### API Key Inválida

**Request:**
```bash
curl -X GET "http://localhost/api/kardex" \
  -H "X-API-Key: clave_incorrecta"
```

**Response (401):**
```json
{
  "tipo": 3,
  "mensajes": [
    "API Key inválida. Acceso denegado",
    "Acceso no autorizado"
  ],
  "data": null
}
```

## ✅ Respuesta Exitosa

**Request:**
```bash
curl -X GET "http://localhost/api/kardex" \
  -H "X-API-Key: sk_live_master_2024_XyZ123AbC456"
```

**Response (200):**
```json
{
  "tipo": 1,
  "mensajes": ["Historial de movimientos obtenido correctamente"],
  "data": [...]
}
```

## 🔧 Personalización

### Agregar Más API Keys

Edita `.env`:
```env
API_KEY_MASTER=tu_key_master
API_KEY_ADMIN=tu_key_admin
API_KEY_CLIENT=tu_key_client
API_KEY_CUSTOM=tu_key_personalizada
```

Actualiza `ApiKeyMiddleware.php`:
```php
$this->validApiKeys = [
    getenv('API_KEY_MASTER'),
    getenv('API_KEY_ADMIN'),
    getenv('API_KEY_CLIENT'),
    getenv('API_KEY_CUSTOM')  // Nueva key
];
```

### Validar contra Base de Datos

Para un sistema más avanzado, puedes validar las API Keys contra la base de datos:

```php
public function verificar()
{
    $apiKeyRecibida = $this->app->request->headers->get('X-API-Key');
    
    // Consultar en base de datos
    $stmt = $this->db->prepare("SELECT * FROM api_keys WHERE key_value = ? AND activa = 1");
    $stmt->execute([$apiKeyRecibida]);
    
    if ($stmt->rowCount() === 0) {
        $this->rechazar('API Key inválida');
        return false;
    }
    
    return true;
}
```

## 📊 Testing

### Probar Ruta Pública
```bash
curl http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/
```
✅ Debe funcionar sin API Key

### Probar Ruta Protegida sin API Key
```bash
curl http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/kardex
```
❌ Debe retornar error 401

### Probar Ruta Protegida con API Key
```bash
curl -H "X-API-Key: sk_live_master_2024_XyZ123AbC456" \
  http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/kardex
```
✅ Debe retornar los datos

## 🛡️ Mejores Prácticas de Seguridad

1. **Rotación de Keys**: Cambia las API Keys periódicamente
2. **Keys por Cliente**: Asigna una key única por cliente
3. **Logs de Acceso**: Registra todos los intentos de acceso
4. **Rate Limiting**: Limita las peticiones por key
5. **HTTPS**: Usa siempre HTTPS en producción
6. **Expiración**: Implementa fechas de expiración para las keys
7. **Revocación**: Permite revocar keys comprometidas

## 📝 Notas

- El middleware usa `$app->stop()` para detener la ejecución si la validación falla
- Todas las respuestas de error usan el formato estandarizado `ResponseHelper`
- Las API Keys se cargan desde variables de entorno para mayor seguridad
- El código HTTP 401 indica "Unauthorized" (no autenticado)
