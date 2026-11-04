<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Sucursales extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('sucursales');
        $sucursales = $builder->get()->getResultArray();

        return $this->respond($sucursales);
    }
}