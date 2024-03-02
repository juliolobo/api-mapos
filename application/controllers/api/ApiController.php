<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestController.php';

// use chriskacerguis\RestServer\RestController;

class ApiController extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('mapos_model');
        $this->load->model('Apikeys_model');
    }

    public function index_get()
    {
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
            'result'  => $result,
            'refresh_token' => $this->refreshToken()
        ], RestController::HTTP_OK);
    }

    public function login_post()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'E-mail', 'valid_email|required|trim');
        $this->form_validation->set_rules('senha', 'Senha', 'required|trim');
        if ($this->form_validation->run() == false) {
            $this->response([
                'status'  => false,
                'message' => validation_errors()
            ], RestController::HTTP_UNAUTHORIZED);
        }

        $this->load->model('Mapos_model');
        $email    = $this->input->post('email');
        $password = $this->input->post('senha');
        $user     = $this->Mapos_model->check_credentials($email);

        if ($user) {
            // Verificar se acesso está expirado
            if ($this->chk_date($user->dataExpiracao)) {
                $this->response([
                    'status'  => false,
                    'message' => 'A conta do usuário está expirada, por favor entre em contato com o administrador do sistema.'
                ], RestController::HTTP_UNAUTHORIZED);
            }

            // Verificar credenciais do usuário
            if (password_verify($password, $user->senha)) {
                $this->log_app('Efetuou login no app', $user->nome);
                
                $data = [
                    'user_id'      => $user->idUsuarios,
                    'ci_key'       => hash('sha256', time()),
                    'level'        => $user->permissoes_id,
                    'ip_addresses' => $this->input->ip_address(),
                    'date_created' => date('Y-m-d H:i:s')
                ];

                $existLoginUser = $this->Apikeys_model->getByUserId($user->idUsuarios);
                if($existLoginUser) {
                    $this->mapos_model->delete('apikeys', 'id', $existLoginUser->id);
                }

                if($this->Apikeys_model->add($data)) {
                    $this->CI = &get_instance();
                    $this->CI->load->database();
                    $this->CI->db->select('*');
                    $this->CI->db->where('idPermissao', $user->permissoes_id);
                    $this->CI->db->limit(1);
                    $array = $this->CI->db->get('permissoes')->row_array();
                    $permissoes = unserialize($array['permissoes']);

                    $result = [
                        'ci_key'      => $data['ci_key'],
                        'permissions' => [$permissoes]
                    ];

                    $this->response([
                        'status'  => true,
                        'message' => 'Login realizado com sucesso!',
                        'result'  => $result,
                    ], RestController::HTTP_OK);
                }
            }

            $this->response([
                'status'  => false,
                'message' => 'Os dados de acesso estão incorretos!'
            ], RestController::HTTP_UNAUTHORIZED);
        }

        $this->response([
            'status'  => false,
            'message' => 'Usuário não encontrado, verifique se suas credenciais estão corretas!'
        ], RestController::HTTP_UNAUTHORIZED);
    }

    public function login_delete()
    {
        $inputData = json_decode(trim(file_get_contents('php://input')));

        if(!isset($inputData->api_key)) {
            $this->response([
                'status' => false,
                'message' => 'API Key Não informada!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        if($inputData->api_key == '569e087716a8e427c8defefacb2011c1') {
            $this->response([
                'status' => false,
                'message' => 'A API Key padrão não pode ser excluída!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $keyId = $this->Apikeys_model->getByKey($inputData->api_key)->id;

        if(!$keyId) {
            $this->response([
                'status' => false,
                'message' => 'API Key não encontrada!'
            ], RestController::HTTP_BAD_REQUEST);
        }

        if($this->mapos_model->delete('apikeys', 'id', $keyId)) {
            $this->response([
                'status'  => true,
                'message' => 'Logout efetuado com sucesso!',
                'refresh_token' => $this->refreshToken()
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Não foi possível adicionar o Cliente. Avise ao Administrador.'
        ], RestController::HTTP_INTERNAL_ERROR);
    }

    public function status_post()
    {
        $inputData = json_decode(trim(file_get_contents('php://input')));

        $key = $this->Apikeys_model->getByKey($inputData->api_key);

        $this->response([
            'status'  => $key ? true : false,
            'message' => $key ? 'API UP e Usuário logado!' : 'API UP e Usuário deslogado!',
            'refresh_token' => $this->refreshToken()
        ], RestController::HTTP_OK);
    }

    public function conta_get()
    {
        $usuarioLogado = $this->logged_user();
        $usuarioLogado->usuario->url_image_user = base_url().'assets/userImage/'.$usuarioLogado->usuario->url_image_user;
        unset($usuarioLogado->usuario->senha);
        unset($usuarioLogado->ci_key);

        $this->response([
            'status'  => true,
            'message' => 'Dados do Usuário!',
            'result'  => $usuarioLogado,
            'refresh_token' => $this->refreshToken()
        ], RestController::HTTP_OK);
    }

    public function emitente_get()
    {
        $result = new stdClass;
        $result->appName  = $this->getConfig('app_name');
        $result->emitente = $this->mapos_model->getEmitente() ?: false;

        $this->response([
            'status'  => true,
            'message' => 'Dados do Map-OS',
            'result'  => $result,
            'refresh_token' => $this->refreshToken()
        ], RestController::HTTP_OK);
    }

    private function chk_date($data_banco)
    {
        $data_banco = new DateTime($data_banco);
        $data_hoje  = new DateTime("now");

        return $data_banco < $data_hoje;
    }
}
