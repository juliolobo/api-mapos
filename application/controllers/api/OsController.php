<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/REST_Controller.php');

class OsController extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('os_model');
        $this->load->model('Api_model');
    }

    public function index_get($id = '')
    {
        $this->logged_user();    
        if (!$this->permission->checkPermission($this->logged_user()->level, 'vOs')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Visualizar Ordens de Serviços'
            ], REST_Controller::HTTP_UNAUTHORIZED);
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
            $perPage = $this->input->get('perPage') ?: 20;
            $page    = $this->input->get('page') ?: 0;
            $start   = $page ? ($perPage * $page) : 0;

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
                'message' => 'Listando OSs',
                'result' => $oss
            ], REST_Controller::HTTP_OK);
        }

        $oss            = $this->os_model->getById($id);
        $oss->produtos  = $this->os_model->getProdutos($id);
        $oss->servicos  = $this->os_model->getServicos($id);
        $oss->anexos    = $this->os_model->getAnexos($id);
        $oss->anotacoes = $this->os_model->getAnotacoes($id);
        $oss->calcTotal = $this->calcTotal($id);
        unset($oss->senha);
        
        $this->response([
            'status' => true,
            'message' => 'Detalhes da OS',
            'result' => $oss
        ], REST_Controller::HTTP_OK);
    }

    public function index_post()
    {
        $this->logged_user();
        if (!$this->permission->checkPermission($this->logged_user()->level, 'aOs')) {
            $this->response([
                'status'  => false,
                'message' => 'Você não está autorizado a Adicionar OS!'
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $_POST = json_decode(file_get_contents("php://input"), true);

        $this->load->library('form_validation');
        
        if($this->form_validation->run('os') == false) {
            $this->response([
                'status' => false,
                'message' => validation_errors()
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $dataInicial     = $this->input->post('dataInicial');
        $dataFinal       = $this->input->post('dataFinal');
        $termoGarantiaId = $this->input->post('termoGarantia');

        try {
            $dataInicial = explode('/', $dataInicial);
            $dataInicial = $dataInicial[2].'-'.$dataInicial[1].'-'.$dataInicial[0];

            if ($dataFinal) {
                $dataFinal = explode('/', $dataFinal);
                $dataFinal = $dataFinal[2].'-'.$dataFinal[1].'-'.$dataFinal[0];
            } else {
                $dataFinal = date('Y/m/d');
            }

            $termoGarantiaId = (!$termoGarantiaId == null || !$termoGarantiaId == '') ? $this->input->post('garantias_id') : null;
        } catch (Exception $e) {
            $dataInicial = date('Y/m/d');
            $dataFinal   = date('Y/m/d');
        }

        $data = [
            'dataInicial'      => $dataInicial,
            'clientes_id'      => $this->input->post('clientes_id'),
            'usuarios_id'      => $this->input->post('usuarios_id'),
            'dataFinal'        => $dataFinal,
            'garantia'         => $this->input->post('garantia'),
            'garantias_id'     => $termoGarantiaId,
            'descricaoProduto' => $this->input->post('descricaoProduto'),
            'defeito'          => $this->input->post('defeito'),
            'status'           => $this->input->post('status'),
            'observacoes'      => $this->input->post('observacoes'),
            'laudoTecnico'     => $this->input->post('laudoTecnico'),
            'faturado'         => 0,
        ];

        if (is_numeric($id = $this->os_model->add('os', $data, true))) {
            $this->load->model('mapos_model');
            $this->load->model('usuarios_model');

            $idOs     = $id;
            $os       = $this->os_model->getById($idOs);
            $emitente = $this->mapos_model->getEmitente();
            $tecnico  = $this->usuarios_model->getById($os->usuarios_id);
            
            // Verificar configuração de notificação
            if ($this->getConfig('os_notification') != 'nenhum' && $this->getConfig('email_automatico') == 1) {
                $remetentes = [];
                switch ($this->getConfig('os_notification')) {
                    case 'todos':
                        array_push($remetentes, $os->email);
                        array_push($remetentes, $tecnico->email);
                        array_push($remetentes, $emitente->email);
                        break;
                    case 'cliente':
                        array_push($remetentes, $os->email);
                        break;
                    case 'tecnico':
                        array_push($remetentes, $tecnico->email);
                        break;
                    case 'emitente':
                        array_push($remetentes, $emitente->email);
                        break;
                    default:
                        array_push($remetentes, $os->email);
                        break;
                }
                $this->enviarOsPorEmail($idOs, $remetentes, 'Ordem de Serviço - Criada');
            }

            log_app('Adicionou uma OS. ID: ' . $id);
            
            $this->response([
                'status' => true,
                'message' => 'OS adicionada com sucesso!',
                'result' => $os
            ], REST_Controller::HTTP_CREATED);
        }
        
        $this->response([
            'status' => false,
            'message' => 'Não foi possível adicionar a OS. Avise ao Administrador.'
        ], REST_Controller::HTTP_INTERNAL_ERROR);
    }

    public function index_delete($id)
    {
        $this->logged_user();
        if (!$this->permission->checkPermission($this->logged_user()->level, 'dServico')) {
            $this->response([
                'status' => false,
                'message' => 'Você não está autorizado a Apagar OS!'
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        if(!$id) {
            $this->response([
                'status' => false,
                'message' => 'Informe o ID da OS!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $os = $this->os_model->getByIdCobrancas($id);
        if ($os == null) {
            $os = $this->os_model->getById($id);
            if ($os == null) {
                $this->response([
                    'status' => false,
                    'message' => 'Erro ao tentar excluir OS!'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        if (isset($os->idCobranca) != null) {
            if ($os->status == "canceled") {
                $this->os_model->delete('cobrancas', 'os_id', $id);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Existe uma cobrança associada a esta OS, deve cancelar e/ou excluir a cobrança primeiro!'
                ], REST_Controller::HTTP_BAD_REQUEST);
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
            $this->log_app('Removeu uma OS ID' . $id);
            $this->response([
                'status' => true,
                'message' => 'OS excluída com sucesso!'
            ], REST_Controller::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível excluir a OS Avise ao Administrador.'
        ], REST_Controller::HTTP_INTERNAL_ERROR);
    }
    
    public function produtos_post($id)
    {
        $this->logged_user();

        $_POST = json_decode(trim(file_get_contents('php://input')));
        
        $this->load->library('form_validation');

        if ($this->form_validation->run('adicionar_produto_os') === false) {
            $this->response([
                'status' => false,
                'message' => validation_errors()
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $data = [
            'produtos_id' => $this->input->post('idProduto'),
            'preco'       => $this->input->post('preco'),
            'quantidade'  => $this->input->post('quantidade'),
            'subTotal'    => $this->input->post('preco') * $this->input->post('quantidade'),
            'os_id'       => $id,
        ];

        $os = $this->os_model->getById($id);
        if ($os == null) {
            $this->response([
                'status'  => false,
                'message' => 'Erro ao tentar inserir produto na OS. OS Não encontrada!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        if ($this->os_model->add('produtos_os', $data) == true) {
            $lastProdutoOs = $this->Api_model->lastRow('produtos_os', 'idProdutos_os');

            $this->load->model('produtos_model');

            $this->produtoEstoque($this->input->post('idProduto'), $this->input->post('quantidade'), '-');

            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $id);
            $this->db->update('os');

            $this->log_app('Adicionou produto a uma OS. ID (OS): '.$id);

            $result = $lastProdutoOs;
            unset($result->descricao);
            $result->produto = $this->produtos_model->getById($this->input->post('idProduto'));

            $this->response([
                'status'  => true,
                'message' => 'Produto adicinado com sucesso!',
                'result'  => $result
            ], REST_Controller::HTTP_OK);
        }
        
        $this->response([
            'status'  => false,
            'message' => 'Não foi possível adicionar o Produto. Avise ao Administrador.'
        ], REST_Controller::HTTP_INTERNAL_ERROR);
    }
    
    public function produtos_put($id, $idProdutos_os)
    {
        $this->logged_user();
        $inputData = json_decode(trim(file_get_contents('php://input')));
        
        $ddAntigo = $this->Api_model->getRowById('produtos_os', 'idProdutos_os', $idProdutos_os);

        $subTotal = $inputData->preco * $inputData->quantidade;

        $data = [
            'quantidade' => $inputData->quantidade,
            'preco'      => $inputData->preco,
            'subTotal'   => $subTotal
        ];

        if ($this->os_model->edit('produtos_os', $data, 'idProdutos_os', $idProdutos_os) == true) {
            $operacao = $ddAntigo->quantidade > $inputData->quantidade ? '+' : '-';
            $diferenca = $operacao == '+' ? $ddAntigo->quantidade - $inputData->quantidade : $inputData->quantidade - $ddAntigo->quantidade;
            
            if($diferenca) {
                $this->produtoEstoque($ddAntigo->produtos_id, $diferenca, $operacao);
            }

            $this->log_app("Atualizou a quantidade do produto id <b>{$ddAntigo->produtos_id}</b> na OS id <b>{$id}</b> para <b>{$inputData->quantidade}</b>");

            $data['idProdutos_os'] = $idProdutos_os;

            $this->response([
                'status'  => true,
                'message' => 'Produto da OS editado com sucesso!',
                'result'  => $data
            ], REST_Controller::HTTP_OK);
        }
        
        $this->response([
            'status'  => false,
            'message' => 'Não foi possível editar o Produto da OS. Avise ao Administrador.'
        ], REST_Controller::HTTP_INTERNAL_ERROR);
    }

    public function produtos_delete($id, $idProdutos_os)
    {
        $this->logged_user();
        $os = $this->os_model->getById($id);
        if ($os == null) {
            $this->response([
                'status'  => false,
                'message' => 'Não foi possível excluir o Produto da OS.'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        
        $ddAntigo = $this->Api_model->getRowById('produtos_os', 'idProdutos_os', $idProdutos_os);

        if(!$ddAntigo) {
            $this->response([
                'status'  => false,
                'message' => 'Não foi encontrado esse Produto na OS.'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        if ($this->os_model->delete('produtos_os', 'idProdutos_os', $idProdutos_os) == true) {
            $this->produtoEstoque($ddAntigo->produtos_id, $ddAntigo->quantidade, '+');

            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $id);
            $this->db->update('os');

            $this->log_app('Removeu produto de uma OS. ID (OS): ' . $id);

            $this->response([
                'status'  => true,
                'message' => 'Produto da OS excluído com sucesso!'
            ], REST_Controller::HTTP_OK);
        }
        
        $this->response([
            'status'  => false,
            'message' => 'Não foi possível excluir o Produto da OS. Avise ao Administrador.'
        ], REST_Controller::HTTP_INTERNAL_ERROR);
    }

    public function servicos_post($id)
    {
        $this->logged_user();
        $_POST = json_decode(trim(file_get_contents('php://input')));

        $this->load->library('form_validation');

        if ($this->form_validation->run('adicionar_servico_os') === false) {
            $this->response([
                'status' => false,
                'message' => validation_errors()
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $data = [
            'servicos_id' => $this->input->post('idServico'),
            'quantidade'  => $this->input->post('quantidade'),
            'preco'       => $this->input->post('preco'),
            'subTotal'    => $this->input->post('preco') * $this->input->post('quantidade'),
            'os_id'       => $id,
        ];

        if ($this->os_model->add('servicos_os', $data) == true) {
            $lastServicoOs = $this->Api_model->lastRow('servicos_os', 'idServicos_os');

            $this->load->model('servicos_model');

            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $id);
            $this->db->update('os');

            $this->log_app('Adicionou serviço a uma OS. ID (OS): '.$id);

            $result = $lastServicoOs;
            unset($result->servico);
            $result->servico = $this->servicos_model->getById($this->input->post('idServico'));

            $this->response([
                'status'  => true,
                'message' => 'Serviço adicinado com sucesso!',
                'result'  => $result
            ], REST_Controller::HTTP_OK);
        }
        
        $this->response([
            'status'  => false,
            'message' => 'Não foi possível adicionar o Serviço. Avise ao Administrador.'
        ], REST_Controller::HTTP_INTERNAL_ERROR);
    }
    
    public function servicos_put($id, $idServicos_os)
    {
        $this->logged_user();
        $inputData = json_decode(trim(file_get_contents('php://input')));

        $ddAntigo = $this->Api_model->getRowById('servicos_os', 'idServicos_os', $idServicos_os);

        $subTotal = $inputData->preco * $inputData->quantidade;

        $data = [
            'quantidade' => $inputData->quantidade,
            'preco'      => $inputData->preco,
            'subTotal'   => $subTotal
        ];

        if ($this->os_model->edit('servicos_os', $data, 'idServicos_os', $idServicos_os) == true) {
            $this->log_app("Atualizou a quantidade do Serviço id <b>{$ddAntigo->servicos_id}</b> na OS id <b>{$id}</b> para <b>{$inputData->quantidade}</b>");

            $data['idServicos_os'] = $idServicos_os;

            $this->response([
                'status'  => true,
                'message' => 'Serviço da OS editado com sucesso!',
                'result'  => $data
            ], REST_Controller::HTTP_OK);
        }
        
        $this->response([
            'status'  => false,
            'message' => 'Não foi possível editar o Serviço da OS. Avise ao Administrador.'
        ], REST_Controller::HTTP_INTERNAL_ERROR);
    }

    public function servicos_delete($id, $idServicos_os)
    {
        $this->logged_user();
        if ($this->os_model->delete('servicos_os', 'idServicos_os', $idServicos_os) == true) {
            $this->log_app('Removeu Serviço de uma OS. ID (OS): ' . $id);
            $this->CI = &get_instance();
            $this->CI->load->database();
            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $id);
            $this->db->update('os');
            
            $this->response([
                'status'  => true,
                'message' => 'Serviço da OS excluído com sucesso!'
            ], REST_Controller::HTTP_OK);
        }
        
        $this->response([
            'status'  => false,
            'message' => 'Não foi possível excluir o Serviço da OS. Avise ao Administrador.'
        ], REST_Controller::HTTP_INTERNAL_ERROR);
    }

    public function anotacoes_post($id)
    {
        $this->logged_user();

        $_POST = json_decode(trim(file_get_contents('php://input')));

        $this->load->library('form_validation');
        
        if($this->form_validation->run('anotacoes_os') == false) {
            $this->response([
                'status' => false,
                'message' => validation_errors()
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        
        $data = [
            'anotacao' => "[{$this->logged_user()->usuario->nome}] ".$this->input->post('anotacao'),
            'data_hora' => date('Y-m-d H:i:s'),
            'os_id' => $id,
        ];

        if ($this->os_model->add('anotacoes_os', $data) == true) {
            $lastAnotacao = $this->Api_model->lastRow('anotacoes_os', 'idAnotacoes');
            $this->log_app('Adicionou anotação a uma OS. ID (OS): ' . $id);
            
            $result = [
                'idAnotacoes' => $lastAnotacao->idAnotacoes,
                'anotacao'   => $this->input->post('anotacao')
            ];

            $this->response([
                'status'  => true,
                'message' => 'Serviço adicinado com sucesso!',
                'result'  => $result
            ], REST_Controller::HTTP_OK);
        }
        
        $this->response([
            'status'  => false,
            'message' => 'Não foi possível adicionar Anotação. Avise ao Administrador.'
        ], REST_Controller::HTTP_INTERNAL_ERROR);
    }

    public function anotacoes_delete($id, $idAnotacao)
    {
        $this->logged_user();
        if ($this->os_model->delete('anotacoes_os', 'idAnotacoes', $idAnotacao) == true) {
            $this->log_app('Removeu anotação de uma OS. ID (OS): ' . $id);
            
            $this->response([
                'status'  => true,
                'message' => 'Anotação excluída com sucesso!'
            ], REST_Controller::HTTP_OK);
        }
        
        $this->response([
            'status'  => false,
            'message' => 'Não foi possível excluir a Anotação. Avise ao Administrador.'
        ], REST_Controller::HTTP_INTERNAL_ERROR);
    }

    private function calcTotal($id)
    {  
        $ordem    = $this->os_model->getById($id);
        $produtos = $this->os_model->getProdutos($ordem->idOs);
        $servicos = $this->os_model->getServicos($ordem->idOs);

        $totalProdutos = 0;
        $totalServicos = 0;
        
        foreach ($produtos as $p) {
            $totalProdutos = $totalProdutos + $p->subTotal;
        }
            
        foreach ($servicos as $s) {
            $preco = $s->preco ?: $s->precoVenda;
            $subtotal = $preco * ($s->quantidade ?: 1);
            $totalServicos = $totalServicos + $subtotal;       
        }

        if($totalProdutos != 0 || $totalServicos != 0 ){
            return $ordem->valor_desconto != 0 ? $ordem->valor_desconto : ($totalProdutos + $totalServicos);
        }
        
        return 0;
    }

    private function produtoEstoque($produtosId, $quantidade, $operacao)
    {
        $this->load->model('produtos_model');

        if($this->getConfig('control_estoque')) {
            $this->produtos_model->updateEstoque($produtosId, $quantidade, $operacao);
        }
    }
}
