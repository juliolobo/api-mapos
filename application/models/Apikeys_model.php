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

    public function getByKey($ci_key)
    {
        $this->db->where('ci_key', $ci_key);
        $this->db->limit(1);
        return $this->db->get('apikeys')->row();
    }

    public function lastRow($table, $idColumn)
    {
        return $this->db->select("*")->limit(1)->order_by($idColumn,"DESC")->get($table)->row();
    }

    public function getRowById($table, $idColumn, $id)
    {
        $this->db->where($idColumn, $id);
        $this->db->limit(1);
        return $this->db->get($table)->row();
    }

    public function getByUserId($userId)
    {
        $this->db->where('user_id', $userId);
        $this->db->limit(1);
        return $this->db->get('apikeys')->row();
    }

    public function updateKeyByUserId($userId)
    {
        $ci_key = hash('sha256', time());

        $this->db->set('ci_key', $ci_key);
        $this->db->where('user_id', $userId);
        $this->db->update('apikeys');

        if ($this->db->affected_rows() >= 0) {
            return $ci_key;
        }

        return false;
    }
}