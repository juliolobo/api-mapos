<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestController.php';

class ProdutosController extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('produtos_model');
        $this->load->helper('validation_helper');
    }

    public function index_get($id = '')
    {        
        if (!$this->permission->checkPermission($this->logged_user()->level, 'vProduto')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Visualizar Produtos'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        if(!$id){
            $perPage = 20;
            $page    = $this->input->get('page') ?: 1;
            $start   = $page != 1 ? (($perPage * ($page - 1)) + 1) : 0;

            $produtos = $this->produtos_model->get('produtos', '*', '', $perPage, $start);

            $this->response([
                'status' => true,
                'message' => 'Listando Produtos',
                'result' => $produtos,
            ], RestController::HTTP_OK);
        }

        $produto = $this->produtos_model->getById($id);
        
        $this->response([
            'status' => true,
            'message' => 'Detalhes do Produto',
            'result' => $produto,
        ], RestController::HTTP_OK);
    }
    
    public function index_post()
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'aProduto')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Adicionar Produtos!'
            ], RestController::HTTP_UNAUTHORIZED);
        }
        
        $inputData = json_decode(trim(file_get_contents('php://input')));

        if(!$inputData->descricao || 
        !$inputData->unidade || 
        !$inputData->precoCompra || 
        !$inputData->precoVenda || 
        !$inputData->estoque) {
            $this->response([
                'status' => false,
                'message' => 'Preencha todos os campos obrigatórios!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $precoCompra = $inputData->precoCompra;
        $precoCompra = str_replace(",", "", $precoCompra);
        $precoVenda  = $inputData->precoVenda;
        $precoVenda  = str_replace(",", "", $precoVenda);
        $data = [
            'codDeBarra' => $inputData->codDeBarra,
            'descricao' => $inputData->descricao,
            'unidade' => $inputData->unidade,
            'precoCompra' => $precoCompra,
            'precoVenda' => $precoVenda,
            'estoque' => $inputData->estoque,
            'estoqueMinimo' => $inputData->estoqueMinimo,
            'saida' => $inputData->saida,
            'entrada' => $inputData->entrada,
        ];

        if ($this->produtos_model->add('produtos', $data) == true) {
            $this->response([
                'status' => true,
                'message' => 'Produto adicionado com sucesso!',
                'result' => $this->produtos_model->get('produtos', '*', "descricao = '{$data['descricao']}'", 1, 0, true)
            ], RestController::HTTP_CREATED);
        }
        
        $this->response([
            'status' => false,
            'message' => 'Não foi possível adicionar o Produto. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }

    public function index_put($id)
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'eProduto')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Editar Produtos!'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        $inputData = json_decode(trim(file_get_contents('php://input')));
        
        if(!$inputData->descricao || 
        !$inputData->unidade || 
        !$inputData->precoCompra || 
        !$inputData->precoVenda || 
        !$inputData->estoque) {
            $this->response([
                'status' => false,
                'message' => 'Preencha todos os campos obrigatórios!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $precoCompra = $inputData->precoCompra;
        $precoCompra = str_replace(",", "", $precoCompra);
        $precoVenda  = $inputData->precoVenda;
        $precoVenda  = str_replace(",", "", $precoVenda);
        $data = [
            'codDeBarra' => $inputData->codDeBarra,
            'descricao' => $inputData->descricao,
            'unidade' => $inputData->unidade,
            'precoCompra' => $precoCompra,
            'precoVenda' => $precoVenda,
            'estoque' => $inputData->estoque,
            'estoqueMinimo' => $inputData->estoqueMinimo,
            'saida' => $inputData->saida,
            'entrada' => $inputData->entrada,
        ];

        if ($this->produtos_model->edit('produtos', $data, 'idProdutos', $id) == true) {
            $this->response([
                'status' => true,
                'message' => 'Produto editado com sucesso!',
                'result' => $this->produtos_model->getById($id)
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível editar o Produto. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }

    public function index_delete($id)
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'dProduto')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Apagar Produtos!'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        if(!$id) {
            $this->response([
                'status' => false,
                'message' => 'Informe o ID do Produto!'
            ], RestController::HTTP_BAD_REQUEST);
        }
        
        $this->produtos_model->delete('produtos_os', 'produtos_id', $id);
        $this->produtos_model->delete('itens_de_vendas', 'produtos_id', $id);

        if ($this->produtos_model->delete('produtos', 'idProdutos', $id) == true) {
            log_info('Removeu um Produto. ID' . $id);
            $this->response([
                'status' => true,
                'message' => 'Produto excluído com sucesso!'
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível excluir o Produto. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}