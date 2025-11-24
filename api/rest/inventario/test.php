<?php
// Archivo de prueba simple
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'PHP funcionando correctamente',
    'php_version' => phpversion(),
    'ruta' => __DIR__
]);
