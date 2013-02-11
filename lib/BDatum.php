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


/***
  * BDatumNode usado para enviar, receber, apagar e listar os arquivos armazenados
  * todos os metodos podem jogar exceção, exceto o constructor
*/

class BDatumNode
{

    private $base_dir;

    private $auth;

    public function __construct(BDatumNodeAuth $auth)
    {
        $this->auth = $auth;
        $this->base_dir = NULL;
        set_time_limit(36000); // 10 hours
    }

    public function set_base_path($dir, $existir=NULL){

        if (is_null($existir) && is_dir($base_dir)){
            throw new Exception("diretorio [$dir] nao existe. Caso realmente queria fazer isso, use set_base_path('$dir', FALSE)");
        }
        if ($existir == true && is_dir($base_dir) == false){
            throw new Exception("diretorio [$dir] nao existe.");
        }

        if ($existir == true){
            $this->base_dir = realpath($dir);
        }else{
            $this->base_dir = $dir;
        }

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

        $md5 = md5_file($filename);

        if (filesize($filename) > 200){
            $info = $this->get_info($key);
            if ($info && $info['etag'] == $md5 ){
                return $info;
            }
        }

        $url = 'https://api.b-datum.com/storage?path=' . $key;

        $ch = $this->get_curl_obj($url, 'GET');

        $post = array(
            "value" => "@$filename",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);


        $return = array();
        if(($response = curl_exec($ch)) === false)
        {
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }
        else
        {
            $info = curl_getinfo($ch);

            $header = substr($response, 0, $info['header_size']);
            $body = substr($response, -$info['download_content_length']);

            $headers = $this->get_headers($header);

            if ($info['http_code'] == 200 || $info['http_code'] == 204){
                $return = array(
                    'url' => $info['url'],
                    'version' => $headers['X-Meta-B-Datum-Version'],
                    'content_type' => $headers['Content-Type'],
                    'etag' => $headers['ETag'],
                    'headers' => $headers
                );
            }else{
                throw new Exception("http_status " . $info['http_code'] . " nao reconhecido: " . print_r($headers, true) );
            }
        }
        curl_close ($ch);

        return $return;
    }

    function get_headers($header)
    {
        $headers = array();

        foreach (explode("\r\n", $header) as $i => $line)
            if (substr($line, 0,4) == 'HTTP')
                $headers['http_code'] = $line;
            else
            {
                if ($line == '') continue;
                list($key, $value) = explode(': ', $line);

                $headers["$key"] = $value;
            }

        return $headers;
    }

    public function get_info($key){
        $key = preg_replace('/\/+/', '/', $key); # tira / duplicados

        $url = 'https://api.b-datum.com/storage/' . $key;

        $ch = $this->get_curl_obj($url, 'HEAD');


        $return = array();
        if(($response = curl_exec($ch)) === false)
        {
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }
        else
        {
            $info = curl_getinfo($ch);

            $header = substr($response, 0, $info['header_size']);
            $body = substr($response, -$info['download_content_length']);

            $headers = $this->get_headers($header);

            if ($info['http_code'] == 404){
                return false;
            }elseif ($info['http_code'] == 200){
                $return = array(
                    'name' => $headers['Content-Disposition'],
                    'content_type' => $headers['Content-Type'],
                    'size' => $headers['Content-Length'],
                    'etag' => $headers['ETag'],

                    'headers' => $headers
                );
            }else{
                throw new Exception("http_status " . $info['http_code'] . " nao reconhecido: " . print_r($headers, true) );
            }
        }
        curl_close ($ch);
        return $return;
    }


    public function download($key, $filename = NULL, $version = -1){
        $key = preg_replace('/\/+/', '/', $key); # tira / duplicados


        $url = 'https://api.b-datum.com/storage?path=/' . $key;
        if ($version != -1 && is_numeric($version)){
            $url .= '&version='.$version;
        }

        $ch = $this->get_curl_obj($url, 'GET');

        $return = array();
        if(($response = curl_exec($ch)) === false)
        {
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }
        else
        {
            $info = curl_getinfo($ch);

            $header = substr($response, 0, $info['header_size']);

            $headers = $this->get_headers($header);

            if ($info['http_code'] == 404){
                return false;
            }elseif ($info['http_code'] == 200){
                $return = array(
                    'name' => $headers['Content-Disposition'],
                    'content_type' => $headers['Content-Type'],
                    'size' => $headers['Content-Length'],
                    'etag' => $headers['ETag'],
                    'version' => $headers['X-Meta-B-Datum-Version'],
                    'headers' => $headers
                );

                if (is_null($filename)){
                    $return['content'] = substr($response, -$info['download_content_length']);
                }else{
                    file_put_contents($filename, substr($response, -$info['download_content_length']));
                }
            }else{
                throw new Exception("http_status " . $info['http_code'] . " nao reconhecido: " . print_r($headers, true) );
            }
        }
        curl_close ($ch);
        return $return;
    }


    public function get_curl_obj($url, $method){
        $ch = curl_init();
        $method = strtoupper($method);

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "B-Datum partner");

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT , 443);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if ($method == 'POST'){
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if ($method != 'GET'){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'Authorization: Basic ' . $this->auth->get_token() . '=='
            )
        );
        return $ch;
    }

    public function delete($key){
        $key = preg_replace('/\/+/', '/', $key); # tira / duplicados


        $url = 'https://api.b-datum.com/storage?path=/' . $key;

        $ch = $this->get_curl_obj($url, 'HEAD');

        $return = array();
        if(($response = curl_exec($ch)) === false)
        {
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }
        else
        {
            $info = curl_getinfo($ch);

            $header = substr($response, 0, $info['header_size']);

            $headers = $this->get_headers($header);

            if ($info['http_code'] == 404){
                return false;
            }elseif ($info['http_code'] == 200){ # teoricamente é 410..
                $return = array(
                    'name' => $headers['Content-Disposition'],
                    'content_type' => $headers['Content-Type'],
                    'size' => $headers['Content-Length'],
                    'etag' => $headers['ETag'],

                    'headers' => $headers
                );

                if (is_null($filename)){
                    $return['content'] = substr($response, -$info['download_content_length']);
                }else{
                    file_put_contents($filename, substr($response, -$info['download_content_length']));
                }
            }else{
                throw new Exception("http_status " . $info['http_code'] . " nao reconhecido: " . print_r($headers, true) );
            }
        }
        curl_close ($ch);
        return $return;
    }

    public function get_list($root='/'){

        $ch = curl_init();

        $url = 'https://api.b-datum.com/storage';

        $root = preg_replace('/\/+/', '/', $root); # tira / duplicados
        if ($root !== '/'){
            $root = preg_replace('/^\/+/', '', $root); # tira do comeco
            $root = preg_replace('/\/+$/', '', $root); # tira do final
            $url .= '?path=/' . $root . '/'; # mas poe de novo
        }

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "B-Datum partner");

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT , 443);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'Authorization: Basic ' . $this->auth->get_token() . '=='
            )
        );
        $return = array();
        if(($response = curl_exec($ch)) === false)
        {
            echo 'Curl error: ' . curl_error($ch) . ' '. curl_errno($ch) ;
        }
        else
        {
            $info = curl_getinfo($ch);

            $header = substr($response, 0, $info['header_size']);
            $body   = substr($response, -$info['download_content_length']);

            $headers = $this->get_headers($header);

            var_dump($headers);
            var_dump($body);
            if ($info['http_code'] == 404){
                return false;
            }elseif ($info['http_code'] == 200){
                $return = json_decode($body);

            }else{
                throw new Exception("http_status " . $info['http_code'] . " nao reconhecido: " . print_r($headers, true) );
            }
        }
        curl_close ($ch);
        return $return;
    }

}

/***
  * BDatumNodeActivation usado para ativar uma ponto
  * retorna StdClass( node_key => 'node_keynode_key' )
  * ou throw error
*/

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
        curl_setopt($ch, CURLOPT_VERBOSE, true);
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


        if(($response = curl_exec($ch)) === false)
        {
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }
        $info = curl_getinfo($ch);
        curl_close ($ch);

        $header = substr($response, 0, $info['header_size']);
        $body = substr($response, -$info['download_content_length']);

        $obj = @json_decode($body);
        if (!empty($obj->error)){
            throw new Exception( $obj->error );
        }
        return $obj;

    }

}