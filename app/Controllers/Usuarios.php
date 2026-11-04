<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Usuarios extends ResourceController
{
    protected $format = 'json';

    // Ejemplo de validación rápida dentro de Usuarios.php
    private function verificarAdmin()
    {
        // Puedes verificar si el request manda un indicador de que es admin
        // O dejarlo listo para cuando implementes tokens JWT.
        // Por ahora, aseguramos que el sistema controle los roles desde el frontend y los endpoints principales.
    }
    public function index()
    {
        try {
            $db = \Config\Database::connect();
            $builder = $db->table('usuarios');
            $builder->select('usuarios.id, usuarios.usuario, usuarios.nombre_completo, usuarios.rol, sucursales.nombre as sucursal, sucursales.id as sucursal_id, usuarios.guia_inicial');
            $builder->join('sucursales', 'sucursales.id = usuarios.sucursal_id', 'left');
            $usuarios = $builder->get()->getResultArray();

            return $this->respond($usuarios);
        } catch (\Exception $e) {
            // 🟢 MODIFICADO: Muestra el error real de la Base de Datos
            return $this->failServerError($e->getMessage());
        }
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        if (empty($data['usuario']) || empty($data['password']) || empty($data['nombre_completo'])) {
            return $this->fail('Faltan campos obligatorios', 400);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('usuarios');

        $insertData = [
            'usuario'         => trim($data['usuario']),
            'password'        => password_hash($data['password'], PASSWORD_DEFAULT), // Blindado con hash
            'nombre_completo' => trim($data['nombre_completo']),
            'rol'             => $data['rol'] ?? 'operador',
            'sucursal_id'     => $data['sucursal_id'] ?? null,
            'guia_inicial'    => $data['guia_inicial'] ?? null
        ];

        try {
            $builder->insert($insertData);
            return $this->respondCreated(['status' => 'success', 'message' => '¡Usuario registrado exitosamente!']);
        } catch (\Exception $e) {
            // 🟢 MODIFICADO: Muestra el error exacto de SQL al insertar
            return $this->fail('Error en la BD: ' . $e->getMessage(), 400);
        }
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        if (!$id) {
            return $this->fail('ID no proporcionado', 400);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('usuarios');

        $updateData = [];
        if (isset($data['usuario'])) $updateData['usuario'] = trim($data['usuario']);
        if (isset($data['nombre_completo'])) $updateData['nombre_completo'] = trim($data['nombre_completo']);
        if (isset($data['rol'])) $updateData['rol'] = $data['rol'];
        if (isset($data['sucursal_id'])) $updateData['sucursal_id'] = $data['sucursal_id'];
        if (isset($data['guia_inicial'])) $updateData['guia_inicial'] = $data['guia_inicial'];

        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($updateData)) {
            return $this->fail('No hay datos para actualizar', 400);
        }

        try {
            $builder->where('id', $id)->update($updateData);
            return $this->respond(['status' => 'success', 'message' => '¡Usuario actualizado correctamente!']);
        } catch (\Exception $e) {
            return $this->fail('Error al actualizar: ' . $e->getMessage(), 400);
        }
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->fail('ID no proporcionado', 400);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('usuarios');

        try {
            $builder->where('id', $id)->delete();
            return $this->respondDeleted(['status' => 'success', 'message' => 'Usuario eliminado correctamente']);
        } catch (\Exception $e) {
            return $this->fail('Error al eliminar: ' . $e->getMessage(), 400);
        }
    }

    public function login()
    {
        try {
            $raw = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];
            $usuario = $raw['usuario'] ?? '';
            $password = $raw['password'] ?? '';

            if (empty($usuario) || empty($password)) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'message' => 'Usuario y contraseña son requeridos']);
            }

            $db = \Config\Database::connect();
            // Buscamos al usuario en la tabla
            $user = $db->table('usuarios')->where('usuario', $usuario)->get()->getRowArray();

            if (!$user) {
                return $this->response->setStatusCode(401)->setJSON(['status' => 401, 'message' => 'Credenciales incorrectas']);
            }

            // Verificamos la contraseña (soporta hash seguro o texto plano temporal)
            if (!password_verify($password, $user['password']) && $password !== $user['password']) {
                return $this->response->setStatusCode(401)->setJSON(['status' => 401, 'message' => 'Credenciales incorrectas']);
            }

            // Por seguridad, eliminamos la contraseña antes de mandar los datos a React
            unset($user['password']);

            return $this->response->setJSON(['status' => 200, 'data' => $user]);

        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
}