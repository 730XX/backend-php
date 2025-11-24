# Estructura Estándar de Respuestas API - Inventario

## Formato de Respuesta

Todas las respuestas de la API siguen esta estructura:

```json
{
  "tipo": 1,
  "mensajes": ["Lista de mensajes informativos"],
  "data": {}
}
```

## Constantes de Tipo de Respuesta

### TIPO_SUCCESS = 1
**Uso**: Operación completada exitosamente
**HTTP Status**: 200, 201
**Ejemplo**:
```json
{
  "tipo": 1,
  "mensajes": ["Movimiento registrado correctamente", "Stock actualizado"],
  "data": {
    "movimiento_id": 123
  }
}
```

### TIPO_WARNING = 2
**Uso**: Operación completada con advertencias
**HTTP Status**: 200
**Ejemplo**:
```json
{
  "tipo": 2,
  "mensajes": ["Stock bajo: solo quedan 5 unidades"],
  "data": {
    "stock_actual": 5
  }
}
```

### TIPO_ERROR = 3
**Uso**: Error en la operación
**HTTP Status**: 400 (cliente), 500 (servidor)
**Ejemplo**:
```json
{
  "tipo": 3,
  "mensajes": ["El campo 'productos_id' es obligatorio y no puede estar vacío"],
  "data": null
}
```

## Ejemplos de Respuestas por Endpoint

### GET /kardex
```json
{
  "tipo": 1,
  "mensajes": ["Historial de movimientos obtenido correctamente"],
  "data": [
    {
      "movimientos_id": 1,
      "movimientos_fecha": "2025-11-24 11:52:36",
      "productos_nombre": "Arroz Costeño",
      "usuarios_nombre": "Admin Sistema",
      "movimientos_tipo": "ENTRADA",
      "movimientos_cantidad": "5.000",
      "movimientos_stock_historico": "15.000",
      "movimientos_motivo": "Compra inicial"
    }
  ]
}
```

### POST /kardex (Exitoso)
```json
{
  "tipo": 1,
  "mensajes": ["Movimiento registrado correctamente", "Stock actualizado"],
  "data": {
    "movimiento_id": 45
  }
}
```

### POST /kardex (Error de validación)
```json
{
  "tipo": 3,
  "mensajes": ["El campo 'movimientos_cantidad' es obligatorio y no puede estar vacío"],
  "data": null
}
```

### POST /kardex (Error de negocio)
```json
{
  "tipo": 3,
  "mensajes": ["Stock insuficiente para realizar la salida. Stock actual: 5, solicitado: 10"],
  "data": null
}
```

## Uso en Frontend (Angular/TypeScript)

```typescript
// Constantes
export enum TipoRespuesta {
  SUCCESS = 1,
  WARNING = 2,
  ERROR = 3
}

// Interface
export interface ApiResponse<T = any> {
  tipo: TipoRespuesta;
  mensajes: string[];
  data: T;
}

// Ejemplo de uso
this.http.get<ApiResponse>('/kardex').subscribe(response => {
  if (response.tipo === TipoRespuesta.SUCCESS) {
    console.log('Datos:', response.data);
    console.log('Mensajes:', response.mensajes);
  } else if (response.tipo === TipoRespuesta.ERROR) {
    console.error('Errores:', response.mensajes);
  }
});
```

## Códigos HTTP Asociados

- **200**: GET exitoso, operación completada
- **201**: POST exitoso, recurso creado
- **400**: Error de validación o datos incorrectos (cliente)
- **500**: Error interno del servidor
