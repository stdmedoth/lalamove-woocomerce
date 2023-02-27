<?php

class LalamoveAPI
{
    protected $api_key;
    protected $api_secret;

    protected $signature;

    const API_PROD_URL = "https://rest.sandbox.lalamove.com";
    const API_SANDBOX_URL = "https://rest.sandbox.lalamove.com";

    const API_URL = self::API_SANDBOX_URL;

    public function __construct($api_key, $api_secret)
    {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    function generate_hmac($method, $path, $data)
    {
        $time = time();
        $body = json_encode($data);
        $payload = "$time\r\n$method\r\n$path\r\n\r\n$body";
        // Generate hash using HMAC-SHA256 algorithm
        $hash = hash_hmac('sha256', $payload, $this->api_secret);

        return $hash;
    }

    public function quotations($serviceType, $stops, $language = 'pt_BR')
    {
        $method = "POST";
        $path = "/v3/quotations";
        $data = [
            'serviceType' => $serviceType,
            'stops' => $stops,
            'language' => $language
        ];

        $response = $this->callPost($path, $method, $data);
        return json_decode($response);
    }

    public function callPost($path, $method, $data)
    {
        $signature = $this->generate_hmac($method, $path, $data);

        $url = self::API_URL . $path;

        $response = NULL;
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    "Authorization: hmac $signature",
                ],
            ]);

            $response = curl_exec($curl);
        } catch (Exception $e) {
            throw new Exception("Ocorreu um erro consultar API - " . $e->getMessage());
        }

        return $response;
    }
}
