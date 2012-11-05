<?php

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

        if (!extension_loaded('curl')) {
            die("extension CURL eh necessario para usar este script");
        }
    }

    public function set_base_path($dir, $existir=NULL){

        if (is_null($existir) && is_dir($base_dir)){
            die("diretorio [$dir] nao existe. Caso realmente queria fazer isso, use set_base_path('$dir', FALSE)");
        }
        if ($existir == true && is_dir($base_dir) == false){
            die("diretorio [$dir] nao existe.");
        }

        $this->base_dir = realpath($dir);

    }

    public function send($filename, $key = NULL){

        if (!file_exists($filename)){
            die("$filename não existe.");
        }

        if (is_null($key)){
            if (is_null($this->base_dir)){
                die("Você esta tentando enviar o arquivo sem definir a chave.");
            }else{
                $key = str_replace(str_replace(DIRECTORY_SEPARATOR, '/', $this->base_dir . DIRECTORY_SEPARATOR), '', $filename);
            }
        }else{
            $key = str_replace('\\','/', $key);
        }

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
                'Authorization: Basic ' . $this->auth->get_token()
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