<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestController.php';

class ClientesController extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('clientes_model');
        $this->load->helper('validation_helper');
    }

    public function index_get($id = '')
    {        
        if (!$this->permission->checkPermission($this->logged_user()->level, 'vCliente')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Visualizar Clientes'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        if(!$id){
            $perPage = 20;
            $page    = $this->input->get('page') ?: 1;
            $start   = ($perPage * ($page - 1)) + 1;

            $clientes = $this->clientes_model->get('clientes', '*', '', $perPage, $start);

            $this->response([
                'status' => true,
                'message' => 'Listando Clientes',
                'result' => $clientes,
            ], RestController::HTTP_OK);
        }

        $cliente = $this->clientes_model->getById($id);
        
        $this->response([
            'status' => true,
            'message' => 'Detalhes do Cliente',
            'result' => $cliente,
        ], RestController::HTTP_OK);
    }
    
    public function index_post()
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'aCliente')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Adicionar Clientes!'
            ], RestController::HTTP_UNAUTHORIZED);
        }
        
        if(!verific_cpf_cnpj($this->input->post('documento'))) {
            $this->response([
                'status' => false,
                'message' => 'CPF/CNPJ inválido. Verifique o número do documento e tente novamente.'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $userExist = $this->clientes_model->get('clientes', '*', "documento = '{$this->input->post('documento')}'", 1, 0, true);

        if($userExist) {
            $this->response([
                'status' => false,
                'message' => 'Já existe um usuário com esse documento!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $senhaCliente = $this->input->post('senha') ? $this->input->post('senha') : preg_replace('/[^\p{L}\p{N}\s]/', '', $this->input->post('documento'));
        $cpf_cnpj     = preg_replace('/[^\p{L}\p{N}\s]/', '', $this->input->post('documento'));
        $pessoaFisica = strlen($cpf_cnpj) == 11 ? true : false;

        $data = [
            'nomeCliente' => $this->input->post('nomeCliente'),
            'contato' => $this->input->post('contato'),
            'pessoa_fisica' => $pessoaFisica,
            'documento' => $this->input->post('documento'),
            'telefone' => $this->input->post('telefone'),
            'celular' => $this->input->post('celular'),
            'email' => $this->input->post('email'),
            'senha' => password_hash($senhaCliente, PASSWORD_DEFAULT),
            'rua' => $this->input->post('rua'),
            'numero' => $this->input->post('numero'),
            'complemento' => $this->input->post('complemento'),
            'bairro' => $this->input->post('bairro'),
            'cidade' => $this->input->post('cidade'),
            'estado' => $this->input->post('estado'),
            'cep' => $this->input->post('cep'),
            'dataCadastro' => date('Y-m-d'),
            'fornecedor' => ($this->input->post('fornecedor') == true ? 1 : 0),
        ];

        if ($this->clientes_model->add('clientes', $data) == true) {
            $this->response([
                'status' => true,
                'message' => 'Cliente adicionado com sucesso!',
                'result' => $this->clientes_model->get('clientes', '*', "documento = '{$data['documento']}'", 1, 0, true)
            ], RestController::HTTP_CREATED);
        }
        
        $this->response([
            'status' => false,
            'message' => 'Não foi possível adicionar o Cliente. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }

    public function index_put($id)
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'eCliente')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Editar Clientes!'
            ], RestController::HTTP_UNAUTHORIZED);
        }
        
        if(!verific_cpf_cnpj($this->input->post('documento'))) {
            $this->response([
                'status' => false,
                'message' => 'CPF/CNPJ inválido. Verifique o número do documento e tente novamente.'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $data = [
            'nomeCliente' => $this->put('nomeCliente'),
            'contato' => $this->put('contato'),
            'documento' => $this->put('documento'),
            'telefone' => $this->put('telefone'),
            'celular' => $this->put('celular'),
            'email' => $this->put('email'),
            'rua' => $this->put('rua'),
            'numero' => $this->put('numero'),
            'complemento' => $this->put('complemento'),
            'bairro' => $this->put('bairro'),
            'cidade' => $this->put('cidade'),
            'estado' => $this->put('estado'),
            'cep' => $this->put('cep'),
            'fornecedor' => ($this->put('fornecedor') == true ? 1 : 0),
        ];

        if($this->put('senha')) {
            $data['senha'] = password_hash($this->put('senha'), PASSWORD_DEFAULT);
        }

        if ($this->clientes_model->edit('clientes', $data, 'idClientes', $id) == true) {
            $this->response([
                'status' => true,
                'message' => 'Cliente editado com sucesso!',
                'result' => $this->clientes_model->getById($id)
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível editar o Cliente. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }

    public function index_delete($id)
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'dCliente')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Apagar Clientes!'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        if(!$id) {
            $this->response([
                'status' => false,
                'message' => 'Informe o ID do cliente!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $os = $this->clientes_model->getAllOsByClient($id);
        if ($os != null) {
            $this->clientes_model->removeClientOs($os);
        }

        $vendas = $this->clientes_model->getAllVendasByClient($id);
        if ($vendas != null) {
            $this->clientes_model->removeClientVendas($vendas);
        }

        if ($this->clientes_model->delete('clientes', 'idClientes', $id) == true) {
            log_info('Removeu um cliente. ID' . $id);
            $this->response([
                'status' => true,
                'message' => 'Cliente excluído com sucesso!'
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível excluir o Cliente. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}