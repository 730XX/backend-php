#!/bin/bash

# Script de Pruebas para Módulo de Ventas
# Ejecutar: chmod +x test_ventas.sh && ./test_ventas.sh

API_URL="http://localhost/Proyecto-final/backend-inventario/api/rest/inventario"
API_KEY="sk_live_master_2024_XyZ123AbC456"
USER_ID="1"

echo "=========================================="
echo "🧪 PRUEBAS DEL MÓDULO DE VENTAS"
echo "=========================================="
echo ""

# Test 1: Health Check
echo "📡 Test 1: Health Check"
curl -s -X GET "$API_URL/" \
  -H "X-API-Key: $API_KEY" | jq '.'
echo ""
echo "---"
echo ""

# Test 2: Venta Exitosa
echo "✅ Test 2: Venta Exitosa (2 productos)"
curl -s -X POST "$API_URL/ventas" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -H "X-User-Id: $USER_ID" \
  -d '{
    "items": [
      {
        "productos_id": 1,
        "cantidad": 2,
        "precio": 15.50
      },
      {
        "productos_id": 2,
        "cantidad": 1.5,
        "precio": 8.00
      }
    ],
    "cliente_nombre": "Juan Pérez Test",
    "observaciones": "Venta de prueba automatizada"
  }' | jq '.'
echo ""
echo "---"
echo ""

# Test 3: Stock Insuficiente
echo "❌ Test 3: Stock Insuficiente"
curl -s -X POST "$API_URL/ventas" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -H "X-User-Id: $USER_ID" \
  -d '{
    "items": [
      {
        "productos_id": 1,
        "cantidad": 999999,
        "precio": 15.50
      }
    ]
  }' | jq '.'
echo ""
echo "---"
echo ""

# Test 4: Producto Inexistente
echo "❌ Test 4: Producto Inexistente"
curl -s -X POST "$API_URL/ventas" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -H "X-User-Id: $USER_ID" \
  -d '{
    "items": [
      {
        "productos_id": 99999,
        "cantidad": 1,
        "precio": 10.00
      }
    ]
  }' | jq '.'
echo ""
echo "---"
echo ""

# Test 5: Sin Header X-User-Id
echo "❌ Test 5: Sin Header X-User-Id (401)"
curl -s -X POST "$API_URL/ventas" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -d '{
    "items": [
      {
        "productos_id": 1,
        "cantidad": 1,
        "precio": 15.50
      }
    ]
  }' | jq '.'
echo ""
echo "---"
echo ""

# Test 6: Items vacío
echo "❌ Test 6: Items vacío"
curl -s -X POST "$API_URL/ventas" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -H "X-User-Id: $USER_ID" \
  -d '{
    "items": []
  }' | jq '.'
echo ""
echo "---"
echo ""

# Test 7: Cantidad negativa
echo "❌ Test 7: Cantidad negativa"
curl -s -X POST "$API_URL/ventas" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -H "X-User-Id: $USER_ID" \
  -d '{
    "items": [
      {
        "productos_id": 1,
        "cantidad": -5,
        "precio": 15.50
      }
    ]
  }' | jq '.'
echo ""
echo "---"
echo ""

# Test 8: Producto duplicado
echo "❌ Test 8: Producto Duplicado"
curl -s -X POST "$API_URL/ventas" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -H "X-User-Id: $USER_ID" \
  -d '{
    "items": [
      {
        "productos_id": 1,
        "cantidad": 2,
        "precio": 15.50
      },
      {
        "productos_id": 1,
        "cantidad": 1,
        "precio": 15.50
      }
    ]
  }' | jq '.'
echo ""
echo "---"
echo ""

echo "=========================================="
echo "✅ PRUEBAS COMPLETADAS"
echo "=========================================="
echo ""
echo "📊 Verifica los logs en:"
echo "   backend-inventario/logs/app.log"
echo ""
echo "🔍 Verifica la BD:"
echo "   SELECT * FROM ventas ORDER BY ventas_id DESC LIMIT 5;"
echo "   SELECT * FROM kardex WHERE movimientos_motivo LIKE 'VENTA%' ORDER BY movimientos_id DESC;"
echo ""
