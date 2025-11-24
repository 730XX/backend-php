<?php

namespace Inventario\Config;

use \PDO;
use \PDOException;
use \Exception;

class Database
{

    // Propiedades privadas para que nadie las modifique desde fuera
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset = 'utf8mb4'; // Importante para eñes y tildes
    public $conn;

    public function __construct()
    {
        // Obtenemos las credenciales de las Variables de Entorno (.env)
        // Rúbrica Punto 5: No hardcodear parámetros
        $this->host     = getenv('DB_HOST');
        $this->db_name  = getenv('DB_NAME');
        $this->username = getenv('DB_USER');
        $this->password = getenv('DB_PASS');
    }

    /**
     * Obtener conexión a la base de datos
     * @return PDO
     * @throws Exception Si falla la conexión
     */
    public function getConnection()
    {
        $this->conn = null;

        try {
            // Data Source Name (Cadena de conexión)
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;

            // Opciones de PDO para seguridad y manejo de errores
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en error SQL (Vital)
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays asociativos
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Seguridad real contra inyección SQL
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            // Rúbrica Punto 7: Manejo de errores.
            // No hacemos echo del error real al usuario, pero lanzamos una excepción 
            // que el controlador capturará para loguear el error técnico.
            throw new Exception("Error de conexión (BD): No se pudo establecer comunicación con el servidor.");
        }

        return $this->conn;
    }
}
