# Script de Prueba - Middleware API Key

## Test 1: Ruta Pública (Sin API Key) ✅
curl -X GET "http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/"

## Test 2: Ruta Protegida SIN API Key (Debe Fallar) ❌
curl -X GET "http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/kardex"

## Test 3: Ruta Protegida CON API Key MASTER (Debe Funcionar) ✅
curl -X GET "http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/kardex" \
  -H "X-API-Key: sk_live_master_2024_XyZ123AbC456"

## Test 4: Ruta Protegida CON API Key ADMIN (Debe Funcionar) ✅
curl -X GET "http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/kardex" \
  -H "X-API-Key: sk_live_admin_2024_DeF789GhI012"

## Test 5: Ruta Protegida CON API Key CLIENT (Debe Funcionar) ✅
curl -X GET "http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/kardex" \
  -H "X-API-Key: sk_live_client_2024_JkL345MnO678"

## Test 6: Ruta Protegida CON API Key INCORRECTA (Debe Fallar) ❌
curl -X GET "http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/kardex" \
  -H "X-API-Key: clave_incorrecta_123"

## Test 7: POST con API Key (Debe Funcionar) ✅
curl -X POST "http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/kardex" \
  -H "X-API-Key: sk_live_master_2024_XyZ123AbC456" \
  -H "Content-Type: application/json" \
  -d '{
    "productos_id": 1,
    "movimientos_tipo": "ENTRADA",
    "movimientos_cantidad": 10,
    "movimientos_motivo": "Compra de prueba"
  }'

## Test 8: POST sin API Key (Debe Fallar) ❌
curl -X POST "http://localhost/Proyecto-final/backend-inventario/api/rest/inventario/kardex" \
  -H "Content-Type: application/json" \
  -d '{
    "productos_id": 1,
    "movimientos_tipo": "ENTRADA",
    "movimientos_cantidad": 10,
    "movimientos_motivo": "Compra de prueba"
  }'
