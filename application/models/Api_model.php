<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User_model class.
 * 
 * @extends CI_Model
 */
class Api_model extends CI_Model {
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

    public function getUserByEmail($email)
    {
        $this->db->where('email', $email);
        $this->db->limit(1);
        return $this->db->get('usuarios')->row();
    }
}