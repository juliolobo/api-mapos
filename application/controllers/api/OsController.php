<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestController.php';

class OsController extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('os_model');
    }

    public function index_get($id = '')
    {        
        if (!$this->permission->checkPermission($this->logged_user()->level, 'vOs')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Visualizar Ordens de Serviços'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        $where_array = [];

        $pesquisa = trim($this->input->get('search'));
        $status   = $this->input->get('status');
        $de       = $this->input->get('from');
        $ate      = $this->input->get('to');

        if ($pesquisa) {
            $where_array['pesquisa'] = $pesquisa;
        }
        if ($status) {
            $where_array['status'] = $status;
        }
        if ($de) {
            $de = explode('/', $de);
            $de = $de[2] . '-' . $de[1] . '-' . $de[0];

            $where_array['de'] = $de;
        }
        if ($ate) {
            $ate = explode('/', $ate);
            $ate = $ate[2] . '-' . $ate[1] . '-' . $ate[0];

            $where_array['ate'] = $ate;
        }

        if(!$id){
            $perPage = 20;
            $page    = $this->input->get('page') ?: 0;
            $start   = $page ? (($perPage * $page) + 1) : 0;

            $oss = $this->os_model->getOs(
                'os',
                'os.*,
                COALESCE((SELECT SUM(produtos_os.preco * produtos_os.quantidade ) FROM produtos_os WHERE produtos_os.os_id = os.idOs), 0) totalProdutos,
                COALESCE((SELECT SUM(servicos_os.preco * servicos_os.quantidade ) FROM servicos_os WHERE servicos_os.os_id = os.idOs), 0) totalServicos',
                $where_array,
                $perPage,
                $page
            );

            $this->response([
                'status' => true,
                'message' => 'Listando O.S.s',
                'result' => $oss,
            ], RestController::HTTP_OK);
        }

        $oss            = $this->os_model->getById($id);
        $oss->produtos  = $this->os_model->getProdutos($id);
        $oss->servicos  = $this->os_model->getServicos($id);
        $oss->anexos    = $this->os_model->getAnexos($id);
        $oss->anotacoes = $this->os_model->getAnotacoes($id);
        
        $this->response([
            'status' => true,
            'message' => 'Detalhes da O.S.',
            'result' => $oss,
        ], RestController::HTTP_OK);
    }

    public function index_delete($id)
    {
        if (!$this->permission->checkPermission($this->logged_user()->level, 'dServico')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Apagar O.S.!'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        if(!$id) {
            $this->response([
                'status' => false,
                'message' => 'Informe o ID da O.S.!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $os = $this->os_model->getByIdCobrancas($id);
        if ($os == null) {
            $os = $this->os_model->getById($id);
            if ($os == null) {
                $this->response([
                    'status' => false,
                    'message' => 'Erro ao tentar excluir O.S.!'
                ], RestController::HTTP_BAD_REQUEST);
            }
        }

        if (isset($os->idCobranca) != null) {
            if ($os->status == "canceled") {
                $this->os_model->delete('cobrancas', 'os_id', $id);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Existe uma cobrança associada a esta OS, deve cancelar e/ou excluir a cobrança primeiro!'
                ], RestController::HTTP_BAD_REQUEST);
            }
        }

        $osStockRefund = $this->os_model->getById($id);
        //Verifica para poder fazer a devolução do produto para o estoque caso OS seja excluida.
        if (strtolower($osStockRefund->status) != "cancelado") {
            $this->devolucaoEstoque($id);
        }

        $this->os_model->delete('servicos_os', 'os_id', $id);
        $this->os_model->delete('produtos_os', 'os_id', $id);
        $this->os_model->delete('anexos', 'os_id', $id);

        if ((int)$os->faturado === 1) {
            $this->os_model->delete('lancamentos', 'descricao', "Fatura de OS - #${id}");
        }

        if ($this->os_model->delete('os', 'idOs', $id) == true) {
            log_info('Removeu uma O.S. ID' . $id);
            $this->response([
                'status' => true,
                'message' => 'O.S. excluída com sucesso!'
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível excluir a O.S. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}
