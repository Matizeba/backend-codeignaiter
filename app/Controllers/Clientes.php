<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\ClienteModel;

class Clientes extends Controller
{
    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }

    public function index()
    {
        try {
            $model = new ClienteModel();
            return $this->response->setJSON($model->findAll());
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Error al listar clientes']);
        }
    }

    public function buscar($ci = null)
    {
        try {
            $model = new ClienteModel();
            $cliente = $model->where('ci_nit', trim($ci))->first();
            if ($cliente) {
                return $this->response->setJSON(['status' => 200, 'data' => $cliente]);
            }
            return $this->response->setStatusCode(404)->setJSON(['status' => 404, 'message' => 'Cliente no encontrado']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Error al buscar cliente']);
        }
    }

    public function create()
    {
        try {
            $model = new ClienteModel();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];

            $ci_nit = trim($raw['ci_nit'] ?? '');
            $nombre = trim($raw['nombre_completo'] ?? '');
            $celular = trim($raw['celular'] ?? '');

            if (empty($ci_nit)) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'message' => 'El CI/NIT es obligatorio']);
            }

            $existente = $model->where('ci_nit', $ci_nit)->first();
            if ($existente) {
                // Actualizar datos si cambiaron
                $model->update($existente['id'], [
                    'nombre_completo' => !empty($nombre) ? $nombre : $existente['nombre_completo'],
                    'celular' => !empty($celular) ? $celular : $existente['celular']
                ]);
                // CORREGIDO: Volver a consultar para devolver el registro actualizado real
                $actualizado = $model->find($existente['id']);
                return $this->response->setJSON(['status' => 200, 'data' => $actualizado]);
            }

            $data = [
                'ci_nit'          => $ci_nit,
                'nombre_completo' => $nombre,
                'celular'         => $celular
            ];

            $newId = $model->insert($data, true);
            if ($newId) {
                $data['id'] = $newId;
                return $this->response->setStatusCode(201)->setJSON(['status' => 201, 'data' => $data]);
            }

            return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'errors' => $model->errors()]);

        } catch (\Throwable $e) {
            $model = new ClienteModel();
            $recuperado = $model->where('ci_nit', trim($raw['ci_nit'] ?? ''))->first();
            if ($recuperado) {
                return $this->response->setJSON(['status' => 200, 'data' => $recuperado]);
            }

            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Excepción en cliente']);
        }
    }
}