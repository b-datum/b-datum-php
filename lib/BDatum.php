<?php


if (!extension_loaded('curl')) {
    throw new Exception("extension CURL eh necessario para usar este script");
}

class BDatumNodeAuth
{
    private $authorization;


    public function __construct($nodeid, $partnerid)
    {
        $this->authorization = base64_encode("$nodeid:$partnerid");
    }

    public function get_token(){
        return $this->authorization;
    }
}

class BDatumNode
{

    private $base_dir;

    private $auth;

    public function __construct(BDatumNodeAuth $auth)
    {
        $this->auth = $auth;
        $this->base_dir = NULL;


    }

    public function set_base_path($dir, $existir=NULL){

        if (is_null($existir) && is_dir($base_dir)){
            throw new Exception("diretorio [$dir] nao existe. Caso realmente queria fazer isso, use set_base_path('$dir', FALSE)");
        }
        if ($existir == true && is_dir($base_dir) == false){
            throw new Exception("diretorio [$dir] nao existe.");
        }

        $this->base_dir = realpath($dir);

    }

    public function send($filename, $key = NULL){

        if (!file_exists($filename)){
            throw new Exception("$filename não existe.");
        }

        if (is_null($key)){
            if (is_null($this->base_dir)){
                throw new Exception("Você esta tentando enviar o arquivo sem definir a chave.");
            }else{
                $key = str_replace(str_replace(DIRECTORY_SEPARATOR, '/', $this->base_dir . DIRECTORY_SEPARATOR), '', $filename);
            }
        }else{
            $key = str_replace('\\','/', $key). '/' . basename($filename);
        }
        $key = preg_replace('/\/+/', '/', $key); # tira / duplicados


        $ch = curl_init();

        $url = 'https://api.b-datum.com/storage/' . $key;

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "B-Datum partner");

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT , 443);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);



        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'Authorization: Basic ' . $this->auth->get_token() . '=='
            )
        );

        $post = array(
            "value" => "@$filename",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        curl_setopt($ch, CURLOPT_VERBOSE, true);


        if(($response = curl_exec($ch)) === false)
        {
            echo 'Curl error: ' . curl_error($ch) . ' '. curl_errno($ch) ;
        }
        else
        {
            $info = curl_getinfo($ch);

            $header = substr($response, 0, $info['header_size']);
            $body = substr($response, -$info['download_content_length']);

            print $body . "<pre>".$header;
        }

        curl_close ($ch);

    }


    public function download($filename, $version=-1){
        return '';
    }

    public function get_list($basedir='/'){

        return array();

    }

}


class BDatumNodeActivation {
    private $partner_key;
    private $activation_key;


    public function __construct($PARTNER_KEY, $ACTIVATION_KEY)
    {
        $this->partner_key = $PARTNER_KEY;
        $this->activation_key = $ACTIVATION_KEY;
    }

    public function activate()
    {
        $ch = curl_init();

        $url = 'https://api.b-datum.com/node/activate';

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "B-Datum partner");

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT , 443);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($ch, CURLOPT_POST, true);

        $post = array(
            "partner_key" => $this->partner_key,
            "activation_key" => $this->activation_key
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
        return $obj;

    }

}