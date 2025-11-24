<?php

namespace Inventario\Services;

use Inventario\Repositories\UsuariosRepository;
use Inventario\Utils\Logger;
use Exception;

class UsuariosService
{
    private $repository;
    private $logger;

    public function __construct(UsuariosRepository $repository, Logger $logger)
    {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function obtenerTodos()
    {
        $this->logger->info("Listando usuarios activos");
        return $this->repository->obtenerTodos();
    }

    public function obtenerPorId($id)
    {
        if (!is_numeric($id) || $id <= 0) throw new Exception("ID de usuario inválido.");

        $usuario = $this->repository->obtenerPorId($id);
        if (!$usuario) throw new Exception("Usuario con ID {$id} no encontrado.");

        // Removemos password del array antes de retornarlo al controller (por si acaso)
        unset($usuario['usuarios_password']);
        return $usuario;
    }

    public function crear($datos)
    {
        // 1. Validar correo único
        if ($this->repository->existePorCorreo($datos['usuarios_correo'])) {
            throw new Exception("El correo '{$datos['usuarios_correo']}' ya está registrado.");
        }

        try {
            $this->repository->beginTransaction();

            // 2. HASHEAR PASSWORD (Seguridad CRÍTICA)
            $datos['usuarios_password'] = password_hash($datos['usuarios_password'], PASSWORD_DEFAULT);

            // 3. Generar API Key básica (si no viene)
            if (!isset($datos['usuarios_apikey'])) {
                $datos['usuarios_apikey'] = bin2hex(random_bytes(32));
            }

            $id = $this->repository->crear($datos);

            $this->repository->commit();
            $this->logger->info("Usuario creado", ['id' => $id, 'correo' => $datos['usuarios_correo']]);

            return $id;
        } catch (Exception $e) {
            if ($this->repository->inTransaction()) $this->repository->rollBack();
            $this->logger->error("Error creando usuario", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function actualizar($id, $datos)
    {
        if (!is_numeric($id) || $id <= 0) throw new Exception("ID inválido.");
        if (!$this->repository->obtenerPorId($id)) throw new Exception("Usuario no encontrado.");

        // Validar correo duplicado si se cambia
        if (isset($datos['usuarios_correo'])) {
            if ($this->repository->existePorCorreo($datos['usuarios_correo'], $id)) {
                throw new Exception("El correo ya pertenece a otro usuario.");
            }
        }

        try {
            $this->repository->beginTransaction();

            // Hashear password solo si viene en los datos
            if (isset($datos['usuarios_password']) && !empty($datos['usuarios_password'])) {
                $datos['usuarios_password'] = password_hash($datos['usuarios_password'], PASSWORD_DEFAULT);
            }

            $this->repository->actualizar($id, $datos);
            $this->repository->commit();

            $this->logger->info("Usuario actualizado", ['id' => $id]);
            return true;
        } catch (Exception $e) {
            if ($this->repository->inTransaction()) $this->repository->rollBack();
            $this->logger->error("Error actualizando usuario", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function cambiarEstado($id, $nuevoEstado)
    {
        if (!is_numeric($id) || $id <= 0) throw new Exception("ID inválido.");

        // Evitar que un Admin se borre a sí mismo (Validación básica)
        // Esto requeriría saber quién es el usuario logueado, lo dejamos para fase Auth.

        try {
            $this->repository->cambiarEstado($id, $nuevoEstado);
            $this->logger->info("Estado de usuario cambiado", ['id' => $id, 'estado' => $nuevoEstado]);
            return true;
        } catch (Exception $e) {
            $this->logger->error("Error cambiando estado usuario", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Autenticar usuario por correo y contraseña
     * @return array|null Usuario si las credenciales son correctas, null si no
     */
    public function autenticar($correo, $password)
    {
        try {
            // Buscar usuario por correo
            $usuario = $this->repository->obtenerPorCorreo($correo);

            // Si no existe el usuario
            if (!$usuario) {
                $this->logger->warning("Intento de login con correo inexistente", ['correo' => $correo]);
                return null;
            }

            // Verificar que el usuario esté activo
            if ($usuario['usuarios_estado'] == 0) {
                $this->logger->warning("Intento de login con usuario inactivo", ['correo' => $correo]);
                return null;
            }

            // Verificar contraseña
            if (!password_verify($password, $usuario['usuarios_password'])) {
                $this->logger->warning("Intento de login con contraseña incorrecta", ['correo' => $correo]);
                return null;
            }

            // Login exitoso - Remover datos sensibles
            unset($usuario['usuarios_password']);
            unset($usuario['usuarios_apikey']);

            $this->logger->info("Login exitoso", ['correo' => $correo, 'id' => $usuario['usuarios_id']]);
            return $usuario;

        } catch (Exception $e) {
            $this->logger->error("Error en autenticación", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
