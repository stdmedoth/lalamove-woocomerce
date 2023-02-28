<?php

class LalamoveAPI
{
    protected $api_key;
    protected $api_secret;
    protected $market;

    protected $signature;

    const API_PROD_URL = "https://rest.sandbox.lalamove.com";
    const API_SANDBOX_URL = "https://rest.sandbox.lalamove.com";

    protected $api_url;

    public function __construct($api_key, $api_secret, $market, $environment)
    {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->market = $market;
        $this->api_url = ($environment == 'product') ? self::API_PROD_URL : self::API_SANDBOX_URL;
    }

    function generate_hmac($method, $path, $time, $data = NULL)
    {
        $payload = '';

        if ($method == "POST") {
            $body = json_encode($data);
            $payload = "$time\r\n$method\r\n$path\r\n\r\n$body";
        } else {
            $payload = "$time\r\n$method\r\n$path\r\n\r\n";
        }


        // Generate hash using HMAC-SHA256 algorithm
        $hash = hash_hmac('sha256', $payload, $this->api_secret);

        return $hash;
    }

    public function get_cities()
    {
        $path = "/v3/cities";
        $query = [];

        $response = $this->callGet($path, $query);
        return $response;
    }

    public function quotations($serviceType, $stops, $language, $item = NULL)
    {

        $path = "/v3/quotations";
        $data = (object)[
            'serviceType' => $serviceType,
            'stops' => $stops,
            'language' => $language
        ];
        if ($item) $data->item = $item;

        $response = $this->callPost($path, $data);
        return $response;
    }

    public function callPost($path, $data)
    {
        $time = time() * 1000;
        $method = "POST";
        $postfields = (object)["data" => $data];
        $signature = $this->generate_hmac($method, $path, $time, $postfields);
        $token = "$this->api_key:$time:$signature";

        $url = $this->api_url . $path;
        $postfields = json_encode($postfields);
        $json = NULL;
        try {
            $curl = curl_init();

            $opts = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $postfields,
                CURLOPT_HTTPHEADER => [
                    "Authorization: hmac $this->api_key:$time:$signature",
                    "Market: $this->market",
                    "Request-ID: " . wp_generate_uuid4()
                ],
            ];
            //var_dump($opts);
            //die();
            curl_setopt_array($curl, $opts);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($response) $json = json_decode($response);
            if ($err) throw new Exception($err);
            if (!in_array($httpcode, [200, 201])) {
                if (isset($json->message)) {
                    throw new Exception($json->message);
                }
                if (isset($json->errors)) {
                    $message = implode("<br>", array_map((fn ($e) => $e->id), $json->errors));
                    throw new Exception($message);
                }
            }
        } catch (Exception $e) {
            throw new Exception("Ocorreu um erro enviar dados para a API - " . $e->getMessage());
        }

        return $json;
    }

    public function callGet($path, $params)
    {
        $time = time() * 1000;
        $method = "GET";
        $signature = $this->generate_hmac($method, $path, $time);

        $url = $this->api_url . $path . "?" . http_build_query($params);
        $json = NULL;
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => [
                    "Authorization: hmac $this->api_key:$time:$signature",
                    "Market: $this->market",
                    "Request-ID: " . wp_generate_uuid4()
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($response) $json = json_decode($response);
            if ($err) throw new Exception($err);
            if (!in_array($httpcode, [200, 201])) {
                if ($json && isset($json->message)) throw new Exception($json->message);

                throw new Exception($response);
            }
        } catch (Exception $e) {
            throw new Exception("Lalamove - " . $e->getMessage());
        }

        return $json;
    }
}
