<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestController.php';

class ClientesController extends RestController
{
    public function index_get($id = '')
    {        
        if (!$this->permission->checkPermission($this->logged_user()->level, 'vCliente')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Visualizar Clientes'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        $this->load->model('clientes_model');

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
                'message' => 'Você não está autorizado a Visualizar Clientes'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        $this->load->library('form_validation');
        if ($this->form_validation->run('clientes') == false) {
            $this->response([
                'status' => false,
                'message' => 'Os dados fornecidos estão incorretos, corrija e tente novamente!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $senhaCliente = $this->input->post('senha') ?  $this->input->post('senha') : preg_replace('/[^\p{L}\p{N}\s]/', '', set_value('documento'));
        $cpf_cnpj     = preg_replace('/[^\p{L}\p{N}\s]/', '', set_value('documento'));
        $pessoaFisica = strlen($cpf_cnpj) == 11 ? true : false;

        $data = [
            'nomeCliente' => set_value('nomeCliente'),
            'contato' => set_value('contato'),
            'pessoa_fisica' => $pessoaFisica,
            'documento' => set_value('documento'),
            'telefone' => set_value('telefone'),
            'celular' => set_value('celular'),
            'email' => set_value('email'),
            'senha' => password_hash($senhaCliente, PASSWORD_DEFAULT),
            'rua' => set_value('rua'),
            'numero' => set_value('numero'),
            'complemento' => set_value('complemento'),
            'bairro' => set_value('bairro'),
            'cidade' => set_value('cidade'),
            'estado' => set_value('estado'),
            'cep' => set_value('cep'),
            'dataCadastro' => date('Y-m-d'),
            'fornecedor' => (set_value('fornecedor') == true ? 1 : 0),
        ];

        if ($this->clientes_model->add('clientes', $data) == true) {
            $this->response([
                'status' => true,
                'message' => 'Cliente adicionado com sucesso'
            ], RestController::HTTP_OK);
        }
        
        $this->response([
            'status' => false,
            'message' => 'Não foi possível adicionar o Cliente. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function index_put($id)
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'eCliente')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Visualizar Clientes'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        $this->load->library('form_validation');
        if ($this->form_validation->run('clientes') == false) {
            $this->response([
                'status' => false,
                'message' => 'Os dados fornecidos estão incorretos, corrija e tente novamente!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $data = [
            'nomeCliente' => $this->input->post('nomeCliente'),
            'contato' => $this->input->post('contato'),
            'documento' => $this->input->post('documento'),
            'telefone' => $this->input->post('telefone'),
            'celular' => $this->input->post('celular'),
            'email' => $this->input->post('email'),
            'rua' => $this->input->post('rua'),
            'numero' => $this->input->post('numero'),
            'complemento' => $this->input->post('complemento'),
            'bairro' => $this->input->post('bairro'),
            'cidade' => $this->input->post('cidade'),
            'estado' => $this->input->post('estado'),
            'cep' => $this->input->post('cep'),
            'fornecedor' => (set_value('fornecedor') == true ? 1 : 0),
        ];

        if($this->input->post('senha')) {
            $data['senha'] = password_hash($this->input->post('senha'), PASSWORD_DEFAULT);
        }

        if ($this->clientes_model->edit('clientes', $data, 'idClientes', $id) == true) {
            $this->response([
                'status' => true,
                'message' => 'Cliente editado com sucesso',
                'result' => $this->clientes_model->getById($id)
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível editar o Cliente. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_SERVER_ERROR);
    }
}