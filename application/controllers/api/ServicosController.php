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
            $search   = $this->input->get('search');
            $where    = $search ? "nome LIKE '%{$search}%' OR descricao LIKE '%{$search}%'" : '';

            $perPage  = 20;
            $page     = $this->input->get('page') ?: 0;
            $start    = $page ? (($perPage * $page) + 1) : 0;

            $servicos = $this->servicos_model->get('servicos', '*', $where, $perPage, $start);

            $this->response([
                'status' => true,
                'message' => 'Listando Serviços',
                'result' => $servicos,
            ], RestController::HTTP_OK);
        }

        if($id && is_numeric($id)) {
            $servico = $this->servicos_model->getById($id);
            
            $this->response([
                'status' => true,
                'message' => 'Detalhes do Serviço',
                'result' => $servico,
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Nenhum Produto localizado.',
            'result' => null,
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

        $inputData = json_decode(trim(file_get_contents('php://input')));

        if(!$inputData->nome || !$inputData->preco) {
            $this->response([
                'status' => false,
                'message' => 'Preencha todos os campos obrigatórios!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $preco = $inputData->preco;
        $preco = str_replace(",", "", $preco);

        $data = [
            'nome' => $inputData->nome,
            'descricao' => $inputData->descricao,
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

        $inputData = json_decode(trim(file_get_contents('php://input')));

        if(!$inputData->nome || !$inputData->preco) {
            $this->response([
                'status' => false,
                'message' => 'Preencha todos os campos obrigatórios!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $preco = $inputData->preco;
        $preco = str_replace(",", "", $preco);

        $data = [
            'nome' => $inputData->nome,
            'descricao' => $inputData->descricao,
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
