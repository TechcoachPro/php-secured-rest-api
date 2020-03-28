<?php

class Trades Extends Controller{
    protected $db;
    protected $method;
    protected $response;
    protected $models;
    protected $file_content;

    protected $process;
    protected $userLogin;

    public function __construct(){
        $this->models = $this->model('Trading');
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->file_content = file_get_contents('php://input', true);
    }

    public function processApi($param){
        switch($this->method){
            case 'GET':
                if ($param){
                    $isParamValid = $this->models->validateParam($param);
                    if($isParamValid < 1){
                        $this->throwError(INVALID_PARAMETER, 'Trade parameter is invalid');
                        return;
                    }
                    $this->response = $this->models->getTrade($param);
                    $this->displayRes('trade');
                } else {
                    $this->response = $this->models->getAllTrades();
                    $this->displayRes('trades');
                };
                break;
            case 'POST':
                $data = $this->validateRequest();
                $this->response = $this->models->createTrade($data);
                $this->displayRes('created');
                break;
            case 'PUT':
                if($param){
                    $isParamValid = $this->models->validateParam($param);
                    if($isParamValid < 1){
                        $this->throwError(INVALID_PARAMETER, 'Trade parameter is invalid');
                        return;
                    }
                    $data = $this->validateRequest();
                    $this->response = $this->models->updateTrade($data, $param);
                    $this->displayRes('Updated');
                }else{
                    $this->throwError(VALIDATE_PARAMETER_REQUIRED, 'Trade parameter is required');
                }
                break;
            case 'DELETE':
                if($param){
                    $isParamValid = $this->models->validateParam($param);
                    if($isParamValid < 1){
                        $this->throwError(INVALID_PARAMETER, 'Trade parameter is invalid');
                        return;
                    }
                    $data = $this->validateRequest();
                    $this->models->deleteTrade($param);
                    $this->response = ["trade" => $param];
                    $this->displayRes('Deleted');
                }else{
                    $this->throwError(VALIDATE_PARAMETER_REQUIRED, 'Trade parameter is required');
                }
                break;
            default:
                $this->throwError(REQUEST_METHOD_NOT_VALID, 'Your requested method is not valid');
                break;
        }
    }

    private function displayRes($name){
        echo json_encode([$name => $this->response]);
    }

    public function validateRequest(){
        if($_SERVER['CONTENT_TYPE'] !== "application/json"){
            $this->throwError(REQUEST_CONTENTTYPE_NOT_VALID, "Requested content type not valid");
            return;
        }

        $data = json_decode($this->file_content, true);
        if(!isset($data['price'])){
            $this->throwError(API_PARAM_REQUIRED, "Api json format not supported");
            return;
        }

        return $data;
    }

    public function throwError($errCode, $errMsg){
        echo json_encode(['error' => ['status'=>$errCode, 'message'=>$errMsg]]);
        exit();
    }

}