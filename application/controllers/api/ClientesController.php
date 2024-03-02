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
            $search   = trim($this->input->get('search'));
            $where    = $search ? "nomeCliente LIKE '%{$search}%' OR documento LIKE '%{$search}%' OR telefone LIKE '%{$search}%' OR celular LIKE '%{$search}%' OR email LIKE '%{$search}%' OR contato LIKE '%{$search}%'" : '';

            $perPage  = $this->input->get('perPage') ?: 20;
            $page     = $this->input->get('page') ?: 0;
            $start    = $page ? ($perPage * $page) : 0;

            $clientes = $this->clientes_model->get('clientes', '*', $where, $perPage, $start);

            if($clientes) {
                $this->response([
                    'status' => true,
                    'message' => 'Lista de Clientes',
                    'result' => $clientes,
                    'refresh_token' => $this->refreshToken()
                ], RestController::HTTP_OK);
            }

            $this->response([
                'status' => false,
                'message' => 'Nenhum cliente localizado',
                'result' => null
            ], RestController::HTTP_OK);
        }

        if($id && is_numeric($id)) {
            $cliente = $this->clientes_model->getById($id);
            $cliente->ordensServicos = $this->clientes_model->getOsByCliente($id);
            
            if($cliente) {
                $this->response([
                    'status' => true,
                    'message' => 'Detalhes do Cliente',
                    'result' => $cliente,
                    'refresh_token' => $this->refreshToken()
                ], RestController::HTTP_OK);
            }
            
            $this->response([
                'status' => false,
                'message' => 'Nenhum cliente localizado com esse ID.',
                'result' => null,
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Nenhum cliente localizado.',
            'result' => null,
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

        $inputData = json_decode(trim(file_get_contents('php://input')));

        if(!isset($inputData->nomeCliente)){
            $this->response([
                'status' => false,
                'message' => 'Preencha todos os campos obrigatórios!'
            ], RestController::HTTP_BAD_REQUEST);
        }
        
        if(isset($inputData->documento) && !verific_cpf_cnpj($inputData->documento)) {
            $this->response([
                'status' => false,
                'message' => 'CPF/CNPJ inválido. Verifique o número do documento e tente novamente.'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $userExist = isset($inputData->documento) ? $this->clientes_model->get('clientes', '*', "documento = '{$inputData->documento}'", 1, 0, true) : false;

        if($userExist) {
            $this->response([
                'status' => false,
                'message' => 'Já existe um usuário com esse documento!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $senhaCliente = $inputData->senha ? $inputData->senha : preg_replace('/[^\p{L}\p{N}\s]/', '', $inputData->documento);
        $cpf_cnpj     = preg_replace('/[^\p{L}\p{N}\s]/', '', $inputData->documento);
        $pessoaFisica = strlen($cpf_cnpj) == 11 ? true : false;

        $data = [
            'nomeCliente' => $inputData->nomeCliente,
            'contato' => $inputData->contato,
            'pessoa_fisica' => $pessoaFisica,
            'documento' => $inputData->documento,
            'telefone' => $inputData->telefone,
            'celular' => $inputData->celular,
            'email' => $inputData->email,
            'senha' => password_hash($senhaCliente, PASSWORD_DEFAULT),
            'rua' => $inputData->rua,
            'numero' => $inputData->numero,
            'complemento' => $inputData->complemento,
            'bairro' => $inputData->bairro,
            'cidade' => $inputData->cidade,
            'estado' => $inputData->estado,
            'cep' => $inputData->cep,
            'dataCadastro' => date('Y-m-d'),
            'fornecedor' => $inputData->fornecedor == true ? 1 : 0,
        ];

        if ($this->clientes_model->add('clientes', $data) == true) {
            $this->response([
                'status' => true,
                'message' => 'Cliente adicionado com sucesso!',
                'result' => $this->clientes_model->get('clientes', '*', "telefone = '{$data['telefone']}'", 1, 0, true),
                'refresh_token' => $this->refreshToken()
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

        $inputData = json_decode(trim(file_get_contents('php://input')));
        
        if(isset($inputData->documento) && !verific_cpf_cnpj($inputData->documento)) {
            $this->response([
                'status' => false,
                'message' => 'CPF/CNPJ inválido. Verifique o número do documento e tente novamente.'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $data = [
            'nomeCliente' => $inputData->nomeCliente,
            'contato' => $inputData->contato,
            'documento' => $inputData->documento,
            'telefone' => $inputData->telefone,
            'celular' => $inputData->celular,
            'email' => $inputData->email,
            'rua' => $inputData->rua,
            'numero' => $inputData->numero,
            'complemento' => $inputData->complemento,
            'bairro' => $inputData->bairro,
            'cidade' => $inputData->cidade,
            'estado' => $inputData->estado,
            'cep' => $inputData->cep,
            'fornecedor' => $inputData->fornecedor == true ? 1 : 0
        ];

        if($this->put('senha')) {
            $data['senha'] = password_hash($this->put('senha'), PASSWORD_DEFAULT);
        }

        if ($this->clientes_model->edit('clientes', $data, 'idClientes', $id) == true) {
            $this->response([
                'status' => true,
                'message' => 'Cliente editado com sucesso!',
                'result' => $this->clientes_model->getById($id),
                'refresh_token' => $this->refreshToken()
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
            $this->log_app('Removeu um cliente. ID' . $id);
            $this->response([
                'status' => true,
                'message' => 'Cliente excluído com sucesso!',
                'refresh_token' => $this->refreshToken()
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível excluir o Cliente. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}
