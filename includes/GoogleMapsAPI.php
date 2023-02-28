<?php

class GoogleMapsAPI
{
    protected $api_key;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function get_geolocalization($address)
    {
        $json = NULL;
        try {
            $curl = curl_init();
            $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . rawurlencode($address) . "&key=" . rawurlencode($this->api_key);
            //echo $url;
            //die();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "",
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($response) $json = json_decode($response);
            if ($err) throw new Exception($err);

            if (!in_array($httpcode, [200, 201])) throw new Exception("Intern error");
        } catch (Exception $e) {
            throw new Exception("GoogleMaps - " . $e->getMessage());
        }
        return $json;
    }
}
