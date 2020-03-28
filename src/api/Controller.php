<?php
class Controller Extends Database{
    protected $db;

    public function __construct(){
        $this->db = new Database;
    }

    public function model($model){
        require_once 'src/models/'.ucfirst($model).'.php';
        return new $model();
    }

}