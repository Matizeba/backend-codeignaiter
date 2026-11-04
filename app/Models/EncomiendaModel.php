<?php

namespace App\Models;

use CodeIgniter\Model;

class EncomiendaModel extends Model
{
    protected $table              = 'encomiendas';
    protected $primaryKey         = 'id';
    protected $useAutoIncrement   = true;
    protected $returnType         = 'array';
    
    // AQUÍ ESTÁ LA MAGIA: Agregamos los campos para que CodeIgniter deje guardarlos
    protected $allowedFields      = [
        'codigo_rastreo',
        'remitente_id',
        'destinatario_id',
        'origen_id',
        'destino_id',
        'origen',              // <-- AGREGADO
        'destino',             // <-- AGREGADO
        'remitente_nombre',    // <-- AGREGADO
        'destinatario_nombre', // <-- AGREGADO
        'descripcion',
        'cantidad',     
        'precio',
        'tipo_pago',
        'estado',
        'fecha_registro'
    ];
}