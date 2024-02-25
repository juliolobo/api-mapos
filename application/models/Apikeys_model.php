<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Apikeys_model extends CI_Model
{
    public function getById($id)
    {
        $this->db->where('id', $id);
        $this->db->limit(1);
        return $this->db->get('apikeys')->row();
    }
    
    public function add($data)
    {
        $this->db->insert('apikeys', $data);
        if ($this->db->affected_rows() == '1') {
            return true;
        }
        
        return false;
    }
}