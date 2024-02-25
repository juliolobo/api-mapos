<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestController.php';

// use chriskacerguis\RestServer\RestController;

class ClientesController extends RestController
{
    public function index_get()
    {        
        if (!$this->permission->checkPermission($this->verify_permissons()->level, 'vCliente')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Visualizar Clientes'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        $perPage = 20;
        $page    = $this->input->get('page') ?: 1;
        $start   = ($perPage * ($page - 1)) + 1;

        $this->load->model('clientes_model');
        $clientes = $this->clientes_model->get('clientes', '*', '', $perPage, $start);

        $this->response([
            'status' => true,
            'message' => 'Listando Clientes',
            'result' => $clientes,
        ], RestController::HTTP_OK); 
    }
}