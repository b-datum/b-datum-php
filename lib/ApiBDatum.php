<?php

if (!extension_loaded('curl')) {
    throw new Exception("extension CURL eh necessario para usar este script");
}

/***
  * Classes para conversar diretamente com a API da b-datum
  * usando email+senha que viram token valido por 24h para o mesmo IP
*/

class ApiBDatumAuth
{
    private $email;
    private $senha;
    private $token;

    public function __construct($email, $senha)
    {
        $this->email = strtolower($email);
        $this->senha = trim($senha);
    }

    public function authorize(){
        $ch = curl_init();

        $url = 'https://api.b-datum.com/login';

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "B-Datum partner");

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT , 443);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $post = array(
            "email" => $this->email,
            "password" => $this->senha,
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        if(($response = curl_exec($ch)) === false)
        {
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }

        $info = curl_getinfo($ch);
        curl_close ($ch);

        $header = substr($response, 0, $info['header_size']);
        $body = substr($response, -$info['download_content_length']);

        $obj = json_decode($body);
        if (!empty($obj->error)){
            throw new Exception( $obj->error );
        }

        $token =

        return true;
    }

    public function get_token(){
        return $this->token;
    }
}



class BDatumNode {

    private $nome;
    private $id;
    private $status;


}


class BDatumOrganization {

    private $nome;
    private $id;
    private $status;
    private $b_datum_api;

    public function add_node(BDatumNode $node){

    }

}


class ApiBDatum
{
    private $auth;

    public function __construct(ApiBDatumAuth $auth)
    {
        $this->auth = $auth;
        $this->base_dir = NULL;


    }

    public function organizations(){


    }

}
