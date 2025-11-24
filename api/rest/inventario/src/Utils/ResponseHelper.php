<?php

namespace Inventario\Utils;

/**
 * Helper para estandarizar todas las respuestas de la API
 * Estructura: { tipo, mensajes[], data }
 */
class ResponseHelper
{
    // Constantes para tipos de respuesta
    const TIPO_SUCCESS = 1;
    const TIPO_WARNING = 2;
    const TIPO_ERROR = 3;

    /**
     * Genera una respuesta exitosa
     * @param mixed $data Los datos a retornar
     * @param array $mensajes Mensajes informativos (opcional)
     * @return array
     */
    public static function success($data = null, $mensajes = [])
    {
        return [
            'tipo' => self::TIPO_SUCCESS,
            'mensajes' => empty($mensajes) ? ['Operación exitosa'] : $mensajes,
            'data' => $data
        ];
    }

    /**
     * Genera una respuesta de advertencia
     * @param array $mensajes Mensajes de advertencia
     * @param mixed $data Datos opcionales
     * @return array
     */
    public static function warning($mensajes = [], $data = null)
    {
        return [
            'tipo' => self::TIPO_WARNING,
            'mensajes' => empty($mensajes) ? ['Advertencia en la operación'] : $mensajes,
            'data' => $data
        ];
    }

    /**
     * Genera una respuesta de error
     * @param array $mensajes Mensajes de error
     * @param mixed $data Datos opcionales (para debugging)
     * @return array
     */
    public static function error($mensajes = [], $data = null)
    {
        return [
            'tipo' => self::TIPO_ERROR,
            'mensajes' => empty($mensajes) ? ['Error en la operación'] : $mensajes,
            'data' => $data
        ];
    }

    /**
     * Envía la respuesta JSON al cliente
     * @param \Slim\Slim $app Instancia de Slim
     * @param array $response Array de respuesta
     * @param int $httpCode Código HTTP (200, 400, 500, etc)
     */
    public static function send($app, $response, $httpCode = 200)
    {
        $app->response->setStatus($httpCode);
        $app->response->headers->set('Content-Type', 'application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
