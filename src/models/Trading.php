<?php
class Trading Extends Database{
    protected $db;
    public function __construct(){
        $this->db = new Database();
    }

    public function getAllTrades(){
        $this->db->query("SELECT * FROM trades");
        $res = $this->db->resultSet();
        return $res;
    }

    public function getTrade($id){
        $this->db->query("SELECT * FROM trades WHERE id = ?");
        $this->db->bind(1, $id);
        $res = $this->db->single();
        return $res;
    }

    public function createTrade($data){
        $this->db->query("INSERT INTO trades (price, created_at) VALUES (:price, now())");
        $this->db->bind(":price", $data['price']);
        $this->db->execute();
        return $data;
    }

    public function updateTrade($data, $param){
        $this->db->query("UPDATE trades SET price = ? WHERE id = ?");
        $this->db->bind(1, $data['price']);
        $this->db->bind(2, $param);
        $this->db->execute();
        return $data;
    }

    public function validateParam($param){
        $this->db->query("SELECT * FROM trades WHERE id = ?");
        $this->db->bind(1, $param);
        return $this->db->count();
    }

    public function deleteTrade($param){
        $this->db->query("DELETE FROM trades WHERE id = ?");
        $this->db->bind(1, $param);
        $this->db->execute();
        return true;
    }
}