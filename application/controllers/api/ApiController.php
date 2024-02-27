<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestController.php';

// use chriskacerguis\RestServer\RestController;

class ApiController extends RestController
{
    public function index_get()
    {
        echo 'Olá mundo!';
    }

    public function login_post()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'E-mail', 'valid_email|required|trim');
        $this->form_validation->set_rules('senha', 'Senha', 'required|trim');
        if ($this->form_validation->run() == false) {
            $json = ['result' => false, 'message' => validation_errors()];
            echo json_encode($json);
        } else {
            $email = $this->input->post('email');
            $password = $this->input->post('senha');
            $this->load->model('Mapos_model');
            $user = $this->Mapos_model->check_credentials($email);

            if ($user) {
                // Verificar se acesso está expirado
                if ($this->chk_date($user->dataExpiracao)) {
                    $json = ['result' => false, 'message' => 'A conta do usuário está expirada, por favor entre em contato com o administrador do sistema.'];
                    echo json_encode($json);
                    die();
                }

                // Verificar credenciais do usuário
                if (password_verify($password, $user->senha)) {
                    $session_admin_data = ['nome_admin' => $user->nome, 'email_admin' => $user->email, 'url_image_user_admin' => $user->url_image_user, 'id_admin' => $user->idUsuarios, 'permissao' => $user->permissoes_id, 'logado' => true];
                    $this->session->set_userdata($session_admin_data);
                    log_info('Efetuou login no app');
                    
                    $this->load->model('Apikeys_model');
                    $this->load->model('Permissoes_model');
                    $data = [
                        'user_id'      => $user->idUsuarios,
                        'ci_key'       => md5(time()),
                        'level'        => $user->permissoes_id,
                        'ip_addresses' => $this->input->ip_address(),
                        'date_created' => date('Y-m-d H:i:s')
                    ];
                    if($this->Apikeys_model->add($data)) {
                        $this->CI = &get_instance();
                        $this->CI->load->database();
                        $this->CI->db->select('*');
                        $this->CI->db->where('idPermissao', $user->permissoes_id);
                        $this->CI->db->limit(1);
                        $array = $this->CI->db->get('permissoes')->row_array();
                        $permissoes = unserialize($array['permissoes']);

                        $json = [
                            'result'      => true, 
                            'ci_key'      => $data['ci_key'],
                            'permissions' => [$permissoes]
                        ];
                        echo json_encode($json);
                    }
                } else {
                    $json = ['result' => false, 'message' => 'Os dados de acesso estão incorretos.'];
                    echo json_encode($json);
                }
            } else {
                $json = ['result' => false, 'message' => 'Usuário não encontrado, verifique se suas credenciais estão corretass.'];
                echo json_encode($json);
            }
        }
        die();
    }

    private function chk_date($data_banco)
    {
        $data_banco = new DateTime($data_banco);
        $data_hoje = new DateTime("now");

        return $data_banco < $data_hoje;
    }
}
