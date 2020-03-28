<?php
use \Firebase\JWT\JWT;

class Api Extends Database{
    protected $gateway = "trades";
    protected $method = "processApi";
    protected $params = [];

    protected $file_content;
    protected $process;
    protected $userLogin;

    protected $db;

    public function __construct(){
        $url = $this->parseUrl();
        $this->db = new Database;

        $this->file_content = file_get_contents('php://input', true);
        $this->validateRequest();

        if(strtolower($this->process) != "generatetoken"){
            $this->validateToken();
        }else{
            $this->generateToken();
            return;
        }

        if(isset($url[0])){
            if(file_exists('src/gateway/'.ucfirst($url[0]).'.php')){
                $this->gateway = $url[0];
                unset($url[0]);
            }else{
                $this->throwError(API_DOST_NOT_EXIST, "Api not found!");
            }
        }

        require_once 'src/gateway/'.$this->gateway.'.php';
        $this->gateway = new $this->gateway;

        $this->params = $url ? array_values($url) : array('');
        call_user_func_array([$this->gateway, $this->method], $this->params);
    }

    public function throwError($errCode, $errMsg){
        echo json_encode(['error' => ['status'=>$errCode, 'message'=>$errMsg]]);
        exit();
    }

    public function parseUrl(){
        if(isset($_GET['url'])){
            return $url = explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
    }

    public function validateRequest(){
        if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
            $this->throwError(REQUEST_CONTENTTYPE_NOT_VALID, "Requested content type not valid");
            return;
        }

        $data = json_decode($this->file_content, true);
        if(isset($data['name']) && !empty($data['name']) && isset($data['login'])){
            $this->process = $data['name'];
            $this->userLogin = $data['login'];
            return;
        }
    }

    public function generateToken(){
        try{
            $isVerified = $this->verifyUser();
            if(empty($isVerified)){
                $this->throwError(INVALID_USER, 'User is not valid');
            }

            $payload = [
                'iat' => time(),
                'iss' => 'localhost',
                'exp' => time() + (15*60),
                'userId' => $isVerified->email
            ];

            $token = JWT::encode($payload, SECRETE_KEY);
            echo json_encode(['token' => $token]);
        }catch(Exception $e){
            throwError(JWT_PROCESSING_ERROR, 'We couldn\'t generate a token');
        }
    }

    public function validateToken(){
        try {
            $token = $this->getBearerToken();
            $payload = JWT::decode($token, SECRETE_KEY, ['HS256']);
        
            $this->db->query("SELECT * FROM users WHERE email = ?");
            $this->db->bind(1, $payload->userId);
            $this->userLogin = $this->db->single()->email;
            if(empty($this->userLogin)) {
                $this->throwError(INVALID_USER, "This user is not found in our database.");
            }
            $this->userLogin = $payload->userId;
        } catch (Exception $e) {
            $this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
        }
    }

    private function verifyUser(){
        if(isset($this->userLogin['email']) && isset($this->userLogin['password'])){
            if(empty($this->userLogin['email']) || empty($this->userLogin['password'])){
                $this->throwError(VALIDATE_PARAMETER_REQUIRED, 'Insert a valid parameter');
            }
            $this->db->query("SELECT * FROM users WHERE email = ? AND password = ?");
            $this->db->bind(1, $this->userLogin['email']);
            $this->db->bind(2, $this->userLogin['password']);
            return $this->db->single();
        }
    }

    public function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    public function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        $this->throwError( ATHORIZATION_HEADER_NOT_FOUND, 'Access Token Not found');
    }
}