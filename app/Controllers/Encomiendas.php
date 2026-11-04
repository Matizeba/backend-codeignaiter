<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\EncomiendaModel;

class Encomiendas extends Controller
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
            $model = new EncomiendaModel();
            
            // Leemos todo directo de la tabla encomiendas sin hacer JOIN a clientes
            $encomiendas = $model->orderBy('id', 'DESC')->findAll();

            foreach ($encomiendas as &$enc) {
                if (!isset($enc['tipo_pago']) || empty($enc['tipo_pago'])) {
                    $enc['tipo_pago'] = 'PAGADO';
                }
                if (empty($enc['remitente_nombre'])) $enc['remitente_nombre'] = 'Remitente General';
                if (empty($enc['destinatario_nombre'])) $enc['destinatario_nombre'] = 'Destinatario General';
            }

            return $this->response->setJSON($encomiendas);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 500,
                'error' => 'ERROR REAL: ' . $e->getMessage()
            ]);
        }
    }

    public function create()
    {
        try {
            $raw = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];
            $encModel = new EncomiendaModel();

            // Guardar directo en la tabla encomiendas (sin buscar clientes)
            $payload = [
                'codigo_rastreo'      => $raw['codigo_rastreo'] ?? 'TM-0000',
                'remitente_id'        => 0, 
                'destinatario_id'     => 0,
                'origen_id'           => intval($raw['origen_id'] ?? 1),
                'destino_id'          => intval($raw['destino_id'] ?? 2),
                'origen'              => $raw['origen'] ?? 'Cochabamba',
                'destino'             => $raw['destino'] ?? 'Santa Cruz',
                'remitente_nombre'    => $raw['remitente_nombre'] ?? 'Remitente General',
                'destinatario_nombre' => $raw['destinatario_nombre'] ?? 'Destinatario General',
                'descripcion'         => trim($raw['descripcion'] ?? 'SIN DESCRIPCIÓN'),
                'cantidad'            => intval($raw['cantidad'] ?? 1),
                'precio'              => floatval($raw['precio'] ?? 0),
                'tipo_pago'           => $raw['tipo_pago'] ?? 'PAGADO',
                'estado'              => 'RECIBIDO',
                'fecha_registro'      => date('Y-m-d H:i:s')
            ];

            $newId = $encModel->insert($payload, true);

            if ($newId) {
                $payload['id'] = $newId; // Devolvemos el ID generado al frontend
                return $this->response->setStatusCode(201)->setJSON(['status' => 201, 'data' => $payload]);
            }

            return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'errors' => $encModel->errors()]);

        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 500,
                'error' => 'Error al guardar en BD: ' . $e->getMessage()
            ]);
        }
    }

    public function actualizarEstado($id = null)
    {
        try {
            $model = new EncomiendaModel();
            $encomienda = $model->find($id);

            if (!$encomienda) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 404, 'message' => 'Encomienda no encontrada']);
            }

            $raw = $this->request->getJSON(true) ?? [];
            $nuevoEstado = $raw['estado'] ?? 'RECIBIDO';

            $model->update($id, ['estado' => $nuevoEstado]);
            return $this->response->setJSON(['status' => 200, 'message' => 'Estado actualizado exitosamente']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Error al actualizar estado: ' . $e->getMessage()]);
        }
    }

    public function actualizarPago($id = null)
    {
        try {
            $model = new EncomiendaModel();
            $encomienda = $model->find($id);

            if (!$encomienda) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 404, 'message' => 'Encomienda no encontrada']);
            }

            $raw = $this->request->getJSON(true) ?? [];
            $nuevoPago = $raw['tipo_pago'] ?? 'PAGADO';

            $model->update($id, ['tipo_pago' => $nuevoPago]);
            return $this->response->setJSON(['status' => 200, 'message' => 'Pago actualizado exitosamente']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Error al actualizar pago: ' . $e->getMessage()]);
        }
    }

    public function rastrear($guia = null)
    {
        try {
            $model = new EncomiendaModel();
            $guiaLimpia = strtoupper(trim($guia ?? ''));

            if (empty($guiaLimpia)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 400,
                    'message' => 'Número de guía vacío'
                ]);
            }

            $encomienda = $model->where('codigo_rastreo', $guiaLimpia)->first();

            if ($encomienda) {
                return $this->response->setJSON([
                    'status' => 200,
                    'data' => $encomienda
                ]);
            }

            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'message' => 'No se encontró ninguna encomienda registrada con esa guía.'
            ]);

        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 500,
                'error' => 'Error interno al rastrear'
            ]);
        }
    }
}