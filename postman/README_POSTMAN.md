# 📦 Importar Colección en Postman - Guía Rápida

## 📥 Paso 1: Importar la Colección

1. **Abre Postman**
2. Click en **Import** (arriba a la izquierda)
3. Selecciona **Upload Files**
4. Navega a: `/backend-inventario/postman/Inventario_API.postman_collection.json`
5. Click en **Import**

✅ Verás la colección "Inventario API - Backend" con 10 peticiones

---

## 🌍 Paso 2: Importar el Environment (Opcional pero Recomendado)

1. Click en **Import** nuevamente
2. Selecciona: `/backend-inventario/postman/Inventario_API_Dev.postman_environment.json`
3. Click en **Import**
4. En el dropdown superior derecho, selecciona: **"Inventario API - Development"**

✅ Ahora tienes las variables de entorno configuradas

---

## 🚀 Paso 3: Probar los Endpoints

### ✅ Test Rápido - Ruta Pública
1. Abre: **"01 - Health Check (Público)"**
2. Click en **Send**
3. ✅ Debe retornar código 200 con info de la API

### ❌ Test de Seguridad - Sin API Key
1. Abre: **"02 - GET Kardex - Sin API Key (ERROR)"**
2. Click en **Send**
3. ❌ Debe retornar código 401 con mensaje "API Key requerida"

### ✅ Test con API Key
1. Abre: **"03 - GET Kardex - Con API Key MASTER"**
2. Verifica que en **Headers** está: `X-API-Key: {{api_key_master}}`
3. Click en **Send**
4. ✅ Debe retornar código 200 con el listado de movimientos

---

## 🎯 Estructura de la Colección

```
Inventario API - Backend
├── 01 - Health Check (Público)              ✅ Sin API Key
├── 02 - GET Kardex - Sin API Key            ❌ Error 401
├── 03 - GET Kardex - API Key MASTER         ✅ Con autenticación
├── 04 - GET Kardex - API Key ADMIN          ✅ Con autenticación
├── 05 - GET Kardex - API Key Inválida       ❌ Error 401
├── 06 - POST Kardex - Crear ENTRADA         ✅ Con autenticación
├── 07 - POST Kardex - Crear SALIDA          ✅ Con autenticación
├── 08 - POST Kardex - Sin API Key           ❌ Error 401
├── 09 - POST Kardex - Campo Faltante        ❌ Error 400 (Validación)
└── 10 - POST Kardex - Cantidad Negativa     ❌ Error 400 (Validación)
```

---

## 🔑 Cómo Usar las API Keys

### Opción 1: Con Variables (Recomendado)
```
Header: X-API-Key
Value: {{api_key_master}}
```

Las peticiones ya incluyen esto por defecto.

### Opción 2: Manualmente
Si no usas el environment, reemplaza `{{api_key_master}}` con:
```
sk_live_master_2024_XyZ123AbC456
```

---

## 📋 API Keys Disponibles

| Nombre | Valor | Uso |
|--------|-------|-----|
| MASTER | `sk_live_master_2024_XyZ123AbC456` | Acceso completo |
| ADMIN  | `sk_live_admin_2024_DeF789GhI012` | Administrador |
| CLIENT | `sk_live_client_2024_JkL345MnO678` | Cliente |

---

## ⚙️ Configuración Manual del Header (Si no importaste el Environment)

Para cada petición protegida:

1. Ve a la pestaña **Headers**
2. Agrega:
   - **Key**: `X-API-Key`
   - **Value**: `sk_live_master_2024_XyZ123AbC456`
3. Marca el checkbox para activarlo
4. Click en **Send**

---

## 🎨 Personalizar Base URL

Si tu proyecto está en otra ruta, edita el environment:

1. Click en el ícono de ⚙️ (arriba derecha)
2. Selecciona **Inventario API - Development**
3. Edita `base_url`:
   ```
   ACTUAL:  http://localhost/Proyecto-final/backend-inventario/api/rest/inventario
   NUEVA:   tu_ruta_aqui
   ```
4. Click en **Save**

---

## ✅ Validar que Todo Funciona

Ejecuta en este orden:

1. ✅ **01 - Health Check** → Debe retornar 200
2. ❌ **02 - Sin API Key** → Debe retornar 401
3. ✅ **03 - Con API Key MASTER** → Debe retornar 200 + datos
4. ❌ **05 - API Key Inválida** → Debe retornar 401

Si todas pasan, ¡tu API está funcionando correctamente! 🎉

---

## 🐛 Troubleshooting

### Error: "Could not get any response"
- Verifica que XAMPP esté corriendo
- Verifica la URL: `http://localhost/Proyecto-final/backend-inventario/api/rest/inventario`

### Error 401 aunque tengas API Key
- Verifica que el header sea: `X-API-Key` (case-sensitive)
- Verifica que la API Key no tenga espacios
- Revisa que el environment esté seleccionado

### Error 404
- Verifica que el archivo `.htaccess` existe en `api/rest/inventario/`
- Verifica que `mod_rewrite` esté habilitado en Apache

---

## 📞 Soporte

Si tienes problemas:
1. Revisa el archivo `logs/app.log`
2. Verifica que el `.env` tenga las API Keys correctas
3. Prueba primero la ruta pública: `GET /`
