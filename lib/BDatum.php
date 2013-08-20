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

        if (file_exists(CA_FILE) == FALSE){
            die("Arquivo de certificado nao foi encontrado.".
                "Você precisa definir o valor correto usando a ".
                "define('CA_FILE', 'caminho/para/arquivo.crt');\nvalor de CA_FILE atual = " . CA_FILE );
        }
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




    public function allowed_metadata(){
        $url = 'https://api.b-datum.com/storage/allowed_metadata';

        $ch = $this->get_curl_obj($url, 'GET');

        $return = array();
        if(($response = curl_exec($ch)) === false)
        {
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }
        else
        {
            $info = curl_getinfo($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header     = substr($response, 0, $headerSize);
            $body       = substr($response, $headerSize);

            $headers = $this->get_headers($header);

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


    public function search_by_metadata($meta=array()){
        $url = 'https://api.b-datum.com/storage/search?';

        $metad = array();
        foreach ($meta as $k => $v ){
            $metad["meta-$k"] = $v;
        }

        $url .= http_build_query($metad);

        $ch = $this->get_curl_obj($url, 'GET');

        $return = array();
        if(($response = curl_exec($ch)) === false)
        {
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }
        else
        {
            $info = curl_getinfo($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header     = substr($response, 0, $headerSize);
            $body       = substr($response, $headerSize);

            $headers = $this->get_headers($header);
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

    public function send($filename, $key = NULL, $meta=array()){

        if (!file_exists($filename)){
            throw new Exception("$filename não existe.");
        }
        if (filesize($filename) > 104857600){
            throw new Exception("Upload maximo de 100mb");
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

        $metad = array();
        foreach ($meta as $k => $v ){
            $metad["meta-$k"] = $v;
        }

        $url = 'https://api.b-datum.com/storage/?path=/' . $key . '&' . http_build_query($metad);
//die(print_r($url, true));
        $ch = $this->get_curl_obj($url, 'POST', $md5);

        $post = array(
            "value" => "@$filename",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        $response = null;
        $try      = 3;

        while ($try > 0){
            $response = curl_exec($ch);
            if ($response)
                break;

            sleep(1);
            $try--;
        }
        if ($response == false && $try == 0){
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }

        $return = array();
        if ($response){
            $info = curl_getinfo($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header     = substr($response, 0, $headerSize);
            $body       = substr($response, $headerSize);

            $headers = $this->get_headers($header);

            if ($info['http_code'] == 201 || $info['http_code'] == 202 || $info['http_code'] == 204){
                $return = array(
                    'url' => $info['url'],
                    'version' => @$headers['X-Meta-B-Datum-Version'],
                    'etag' => @$headers['ETag'],
                );
            }else{
                throw new Exception("http_status " . $info['http_code'] . " nao reconhecido: " . print_r($headers, true) );
            }
        }
        curl_close ($ch);

        return $return;
    }

    public function set_meta($key = NULL, $meta=array() ){


        $key = preg_replace('/\/+/', '/', $key); # tira / duplicados

        $metad = array();
        foreach ($meta as $k => $v ){
            $metad["meta-$k"] = $v;
        }

        $url = 'https://api.b-datum.com/storage/?path=/' . $key . '&' . http_build_query($metad);

        $ch = $this->get_curl_obj($url, 'PATCH');

        $response = null;
        $try      = 3;

        while ($try > 0){
            $response = curl_exec($ch);
            if ($response)
                break;

            sleep(1);
            $try--;
        }
        if ($response == false && $try == 0){
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }

        $return = array();
        if ($response){
            $info = curl_getinfo($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header     = substr($response, 0, $headerSize);
            $body       = substr($response, $headerSize);

            $headers = $this->get_headers($header);

            if ($info['http_code'] == 201 || $info['http_code'] == 204){
                $return = array(
                    'version' => @$headers['X-Meta-B-Datum-Version']
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

    public function get_info($key, $version = -1){
        $key = preg_replace('/\/+/', '/', $key); # tira / duplicados

        $key = preg_replace('/^\/+/', '', $key); # tira do comeco
        $key = preg_replace('/\/+$/', '', $key); # tira do final

        $url = 'https://api.b-datum.com/storage?path=/' . $key;
        if ($version != -1 && is_numeric($version)){
            $url .= '&version='.$version;
        }
        $ch = $this->get_curl_obj($url, 'HEAD');

        $return = array();
        if(($response = curl_exec($ch)) === false)
        {
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }
        else
        {
            $info = curl_getinfo($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header     = substr($response, 0, $headerSize);
            $body       = substr($response, $headerSize);

            $headers = $this->get_headers($header);

            if ($info['http_code'] == 404){
                return false;
            }elseif ($info['http_code'] == 200){
                $metas = array();
                foreach ($headers as $k => $v){
                    if (substr($k, 0, 20 ) == "X-Meta-B-Datum-Field"){
                        $metas[strtolower(substr($k, 21 ))] = $v;
                    }
                }

                $return = array(
                    'name'         => @$headers['Content-Disposition'],
                    'content_type' => @$headers['Content-Type'],
                    'size'         => @$headers['X-Meta-B-Datum-Size'],
                    'etag'         => @$headers['ETag'],
                    'version'      => @$headers['X-Meta-B-Datum-Version'],
                    'deleted'      => @$headers['X-Meta-B-Datum-Delete'],
                    'meta'=> $metas
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

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header     = substr($response, 0, $headerSize);
            $body       = substr($response, $headerSize);

            $headers = $this->get_headers($header);

            if ($info['http_code'] == 404){
                return false;
            }elseif ($info['http_code'] == 200){
            #die(print_r($headers, true));
                $return = array(
                    'name' => $headers['Content-Disposition'],
                    'content_type' => $headers['Content-Type'],
                    'size' => $headers['Content-Length'],
            //        'etag' => $headers['ETag'],
              //      'version' => $headers['X-Meta-B-Datum-Version'],
                );

                if (is_null($filename)){
                    $return['content'] = $body;
                }else{
                    file_put_contents($filename, $body);
                }
            }else{
                throw new Exception("http_status " . $info['http_code'] . " nao reconhecido: " . print_r($headers, true) );
            }
        }
        curl_close ($ch);
        return $return;
    }


    public function get_curl_obj($url, $method, $etag=''){
        $ch = curl_init();
        $method = strtoupper($method);

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "B-Datum partner");

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT , 443);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CAINFO, CA_FILE);

        if ($method == 'POST'){
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if ($method == 'HEAD'){
        curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        if ($method != 'GET'){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $arr = array(
            'Authorization: Basic ' . $this->auth->get_token() . '==',
        );
        if (is_null($etag) == false){
            $arr[] = "ETag: $etag";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $arr);
        return $ch;
    }

    public function delete($key){
        $key = preg_replace('/\/+/', '/', $key); # tira / duplicados


        $url = 'https://api.b-datum.com/storage?path=/' . $key;

        $ch = $this->get_curl_obj($url, 'DELETE');

        $return = array();
        if(($response = curl_exec($ch)) === false)
        {
            throw new Exception('Curl error: ' . curl_error($ch) . ' '. curl_errno($ch));
        }
        else
        {
            $info = curl_getinfo($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header     = substr($response, 0, $headerSize);
            $body       = substr($response, $headerSize);

            $headers = $this->get_headers($header);

            if ($info['http_code'] == 404){
                return false;
            }elseif ($info['http_code'] == 410 || $info['http_code'] == 204 ){
                $return = array(
                    'deleted' => '1'
                );
            }else{
                throw new Exception("http_status " . $info['http_code'] . " nao reconhecido: " . print_r($headers, true) );
            }
        }
        curl_close ($ch);
        return $return;
    }

    public function get_list($root='/'){


        $url = 'https://api.b-datum.com/storage';

        $root = preg_replace('/\/+/', '/', $root); # tira / duplicados
        if ($root !== '/'){
            $root = preg_replace('/^\/+/', '', $root); # tira do comeco
            $root = preg_replace('/\/+$/', '', $root); # tira do final
            $url .= '?path=/' . $root . '/'; # mas poe de novo
        }


        $ch = $this->get_curl_obj($url, 'GET');
        $return = array();
        if(($response = curl_exec($ch)) === false)
        {
            echo 'Curl error: ' . curl_error($ch) . ' '. curl_errno($ch) ;
        }
        else
        {
            $info = curl_getinfo($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header     = substr($response, 0, $headerSize);
            $body       = substr($response, $headerSize);

            $headers = $this->get_headers($header);

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CAINFO, CA_FILE);

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

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header     = substr($response, 0, $headerSize);
        $body       = substr($response, $headerSize);

        $obj = @json_decode($body);
        if (!empty($obj->error)){
            throw new Exception( $obj->error );
        }
        return $obj;

    }

}