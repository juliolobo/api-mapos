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
            $start   = ($perPage * ($page - 1)) + 1;

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

        if(!$this->input->post('descricao') || 
        !$this->input->post('unidade') || 
        !$this->input->post('precoCompra') || 
        !$this->input->post('precoVenda') || 
        !$this->input->post('estoque')) {
            $this->response([
                'status' => false,
                'message' => 'Preencha todos os campos obrigatórios!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $precoCompra = $this->input->post('precoCompra');
        $precoCompra = str_replace(",", "", $precoCompra);
        $precoVenda  = $this->input->post('precoVenda');
        $precoVenda  = str_replace(",", "", $precoVenda);
        $data = [
            'codDeBarra' => $this->input->post('codDeBarra'),
            'descricao' => $this->input->post('descricao'),
            'unidade' => $this->input->post('unidade'),
            'precoCompra' => $precoCompra,
            'precoVenda' => $precoVenda,
            'estoque' => $this->input->post('estoque'),
            'estoqueMinimo' => $this->input->post('estoqueMinimo'),
            'saida' => $this->input->post('saida'),
            'entrada' => $this->input->post('entrada'),
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

        if(!$this->put('descricao') || 
        !$this->put('unidade') || 
        !$this->put('precoCompra') || 
        !$this->put('precoVenda') || 
        !$this->put('estoque')) {
            $this->response([
                'status' => false,
                'message' => 'Preencha todos os campos obrigatórios!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $precoCompra = $this->put('precoCompra');
        $precoCompra = str_replace(",", "", $precoCompra);
        $precoVenda  = $this->put('precoVenda');
        $precoVenda  = str_replace(",", "", $precoVenda);
        $data = [
            'codDeBarra' => $this->put('codDeBarra'),
            'descricao' => $this->put('descricao'),
            'unidade' => $this->put('unidade'),
            'precoCompra' => $precoCompra,
            'precoVenda' => $precoVenda,
            'estoque' => $this->put('estoque'),
            'estoqueMinimo' => $this->put('estoqueMinimo'),
            'saida' => $this->put('saida'),
            'entrada' => $this->put('entrada'),
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