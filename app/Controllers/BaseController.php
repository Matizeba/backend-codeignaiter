<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // 🚀 Encabezados CORS globales y automáticos para todo CodeIgniter 4
        $origin = $request->getServer('HTTP_ORIGIN') ?? '*';
        
        header("Access-Control-Allow-Origin: " . $origin);
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH");
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method, Authorization");

        // Si la petición es OPTIONS (preflight de JavaScript), detenemos la ejecución con éxito 200
        if ($request->getMethod() === 'options') {
            header("HTTP/1.1 200 OK");
            exit(0);
        }
    }
}