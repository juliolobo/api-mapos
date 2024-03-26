<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Classe ApiController.
 * 
 * @extends REST_Controller
 */
require(APPPATH.'/libraries/REST_Controller.php');

// class UsuarioController extends REST_Controller
class ApiController extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('Authorization_Token');
        $this->load->model('mapos_model');
    }

    public function index_get()
    {
        $user = $this->logged_user();

        $result = new stdClass;
        $result->countOs   = $this->mapos_model->count('os');
        $result->clientes  = $this->mapos_model->count('clientes');
        $result->produtos  = $this->mapos_model->count('produtos');
        $result->servicos  = $this->mapos_model->count('servicos');
        $result->garantias = $this->mapos_model->count('garantias');
        $result->vendas    = $this->mapos_model->count('vendas');
        
        $result->osAbertas    = $this->mapos_model->getOsAbertas();
        $result->osAndamento  = $this->mapos_model->getOsAndamento();
        $result->estoqueBaixo = $this->mapos_model->getProdutosMinimo();

        $this->response([
            'status'  => true,
            'message' => 'Dashboard',
            'result'  => $result
        ], REST_Controller::HTTP_OK);
    }

    public function status_get()
    {
        $user = $this->logged_user();

        $this->response([
            'status'  => $user->status ? true : false,
            'message' => $user->status ? 'API UP e Usuário logado!' : 'API UP e Usuário deslogado!'
        ], REST_Controller::HTTP_OK);
    }

    public function emitente_get()
    {
        $this->logged_user();

        $result = new stdClass;
        $result->appName  = $this->getConfig('app_name');
        $result->emitente = $this->mapos_model->getEmitente() ?: false;

        $this->response([
            'status'  => true,
            'message' => 'Dados do Map-OS',
            'result'  => $result
        ], REST_Controller::HTTP_OK);
    }
}
