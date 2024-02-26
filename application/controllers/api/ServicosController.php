<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestController.php';

class ServicosController extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('servicos_model');
    }

    public function index_get($id = '')
    {        
        if (!$this->permission->checkPermission($this->logged_user()->level, 'vServico')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Visualizar Serviços'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        if(!$id){
            $perPage = 20;
            $page    = $this->input->get('page') ?: 1;
            $start   = $page != 1 ? (($perPage * ($page - 1)) + 1) : 0;

            $servicos = $this->servicos_model->get('servicos', '*', '', $perPage, $start);

            $this->response([
                'status' => true,
                'message' => 'Listando Serviços',
                'result' => $servicos,
            ], RestController::HTTP_OK);
        }

        $servico = $this->servicos_model->getById($id);
        
        $this->response([
            'status' => true,
            'message' => 'Detalhes do Serviço',
            'result' => $servico,
        ], RestController::HTTP_OK);
    }
    
    public function index_post()
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'aServico')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Adicionar Serviços!'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        if(!$this->input->post('nome') || !$this->input->post('preco')) {
            $this->response([
                'status' => false,
                'message' => 'Preencha todos os campos obrigatórios!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $preco = $this->input->post('preco');
        $preco = str_replace(",", "", $preco);

        $data = [
            'nome' => $this->input->post('nome'),
            'descricao' => $this->input->post('descricao'),
            'preco' => $preco,
        ];

        if ($this->servicos_model->add('servicos', $data) == true) {
            $this->response([
                'status' => true,
                'message' => 'Serviço adicionado com sucesso!',
                'result' => $this->servicos_model->get('servicos', '*', "descricao = '{$data['descricao']}'", 1, 0, true)
            ], RestController::HTTP_CREATED);
        }
        
        $this->response([
            'status' => false,
            'message' => 'Não foi possível adicionar o Serviço. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }

    public function index_put($id)
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'eServico')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Editar Serviços!'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        if(!$this->put('nome') || !$this->put('preco')) {
            $this->response([
                'status' => false,
                'message' => 'Preencha todos os campos obrigatórios!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $preco = $this->put('preco');
        $preco = str_replace(",", "", $preco);

        $data = [
            'nome' => $this->put('nome'),
            'descricao' => $this->put('descricao'),
            'preco' => $preco,
        ];

        if ($this->servicos_model->edit('servicos', $data, 'idServicos', $id) == true) {
            $this->response([
                'status' => true,
                'message' => 'Serviço editado com sucesso!',
                'result' => $this->servicos_model->getById($id)
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível editar o Serviço. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }

    public function index_delete($id)
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'dServico')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Apagar Serviços!'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        if(!$id) {
            $this->response([
                'status' => false,
                'message' => 'Informe o ID do Serviço!'
            ], RestController::HTTP_BAD_REQUEST);
        }
        
        $this->servicos_model->delete('servicos_os', 'servicos_id', $id);

        if ($this->servicos_model->delete('servicos', 'idServicos', $id) == true) {
            log_info('Removeu um Serviço. ID' . $id);
            $this->response([
                'status' => true,
                'message' => 'Serviço excluído com sucesso!'
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível excluir o Serviço. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}