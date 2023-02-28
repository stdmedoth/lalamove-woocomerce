<?php


function lalamove_shipping_method()
{
    class Lalamove_Shipping_Method extends WC_Shipping_Method
    {

        protected $api_key;
        protected $api_secret;

        // Initialize the shipping method
        public function __construct($instance_id = 0)
        {
            $this->id = 'lalamove_shipping_method';
            $this->instance_id        = absint($instance_id);
            $this->method_title = __('Lalamove', 'lalamove_shipping_method');
            $this->method_description = __('Use Lalamove to deliver your products.', 'lalamove_shipping_method');
            $this->title = __('Lalamove', 'lalamove_shipping_method');
            $this->countries = array(
                'BR',
            );

            $this->availability = 'including';
            $this->supports = array(
                'shipping-zones',
                'instance-settings',
                'instance-settings-modal',
            );
            $this->init();
        }

        // Define the settings for the shipping method
        public function init()
        {
            $this->init_form_fields();
            $this->init_settings();

            $this->api_key = $this->get_option('api_key');
            $this->api_secret = $this->get_option('api_secret');

            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }
        public function init_form_fields()
        {
            // Define the settings
            $this->instance_form_fields = array(
                'api_key' => array(
                    'title' => __('API Key', 'lalamove_shipping_method'),
                    'type' => 'text',
                    'description' => __('Enter your Lalamove API key.', 'lalamove_shipping_method'),
                    'default' => '',
                    'desc_tip'    => true,
                ),
                'api_secret' => array(
                    'title' => __('API Secret', 'lalamove_shipping_method'),
                    'type' => 'text',
                    'description' => __('Enter your Lalamove API secret.', 'lalamove_shipping_method'),
                    'default' => '',
                    'desc_tip'    => true,
                ),
                'google_maps_api_key' => array(
                    'title' => __('Google Maps API Key', 'lalamove_shipping_method'),
                    'type' => 'text',
                    'description' => __('Enter your Google Maps API Key.', 'lalamove_shipping_method'),
                    'default' => '',
                    'desc_tip'    => true,
                ),
                'market' => array(
                    'title' => __('Available Markets', 'lalamove_shipping_method'),
                    'type'          => 'select',
                    'options'           => array(
                        'BR' => __('Brasil', 'lalamove_shipping_method'),
                        'HK' => __('Hong Kong	', 'lalamove_shipping_method'),
                        'ID' => __('Indonesia', 'lalamove_shipping_method'),
                        'MY' => __('Malaysia', 'lalamove_shipping_method'),
                        'MX' => __('Mexico', 'lalamove_shipping_method'),
                        'PH' => __('Philippines', 'lalamove_shipping_method'),
                        'SG' => __('Singapore', 'lalamove_shipping_method'),
                        'TW' => __('Taiwan', 'lalamove_shipping_method'),
                        'TH' => __('Thailand', 'lalamove_shipping_method'),
                        'VN' => __('Vietnam', 'lalamove_shipping_method'),
                    ),
                    'description' => __('Enter lalamove market.', 'lalamove_shipping_method'),
                    'default' => '',
                    'desc_tip'    => true,
                ),
                'language' => array(
                    'title' => __('Language', 'lalamove_shipping_method'),
                    'type'          => 'select',
                    'options'           => array(
                        'pt_BR' => __('Português (Brasil)', 'lalamove_shipping_method'),
                        'zh_HK' => __('Hong Kong	', 'lalamove_shipping_method'),
                        'id_ID' => __('Indonesia', 'lalamove_shipping_method'),
                        'ms_MY' => __('Malaysia', 'lalamove_shipping_method'),
                        'es_MX' => __('Mexico', 'lalamove_shipping_method'),
                        'en_PH' => __('Philippines', 'lalamove_shipping_method'),
                        'en_SG' => __('Singapore', 'lalamove_shipping_method'),
                        'zh_TW' => __('Taiwan', 'lalamove_shipping_method'),
                        'en_TH' => __('Thailand', 'lalamove_shipping_method'),
                        'vi_VN' => __('Vietnam', 'lalamove_shipping_method'),
                    ),
                    'description' => __('Enter lalamove language market.', 'lalamove_shipping_method'),
                    'default' => '',
                    'desc_tip'    => true,
                ),
                'environment' => array(
                    'title' => __('Environment', 'lalamove_shipping_method'),
                    'type'          => 'select',
                    'options'           => array(
                        'sandbox' => __('SandBox', 'lalamove_shipping_method'),
                        'production' => __('Production', 'lalamove_shipping_method'),
                    ),
                    'description' => __('Enter lalamove API environment.', 'lalamove_shipping_method'),
                    'default' => '',
                    'desc_tip'    => true,
                ),
            );
            if (!function_exists('dokan_get_option')) {
                $this->instance_form_fields['pickup_address'] = [
                    'title' => __('Pickup Address', 'lalamove_shipping_method'),
                    'type' => 'text',
                    'description' => __('Enter pickup address.', 'lalamove_shipping_method'),
                    'default' => '',
                    'desc_tip'    => true,
                ];

                $this->instance_form_fields['pickup_city'] = [
                    'title' => __('Pickup City', 'lalamove_shipping_method'),
                    'type' => 'text',
                    'description' => __('Enter pickup city.', 'lalamove_shipping_method'),
                    'default' => '',
                    'desc_tip'    => true,
                ];

                $this->instance_form_fields['pickup_state'] = [
                    'title' => __('Pickup State', 'lalamove_shipping_method'),
                    'type' => 'text',
                    'description' => __('Enter pickup state.', 'lalamove_shipping_method'),
                    'default' => '',
                    'desc_tip'    => true,
                ];
            }
        }

        /**
         * Calculate the shipping rate for the Lalamove shipping method
         */
        public function calculate_shipping($package = array())
        {
            if (!strlen($package['destination']['address'])) return;

            // shipping address
            $destination_address = $package['destination']['address'];
            $destination_city = $package['destination']['city'];
            $destination_state = $package['destination']['state'];
            $destination_postcode = $package['destination']['postcode'];
            $destination_country = $package['destination']['country'];

            // pickup address
            $pickup_address = NULL;
            $pickup_city = NULL;
            $pickup_state = NULL;
            $pickup_postcode = NULL;
            $pickup_country = NULL;

            // Get the API key and secret
            $api_key = $this->get_option('api_key');
            $api_secret = $this->get_option('api_secret');
            $market = $this->get_option('market');
            $language = $this->get_option('language');
            $google_maps_api_key = $this->get_option('google_maps_api_key');
            $pickup_address = $this->get_option('pickup_address');
            $pickup_city = $this->get_option('pickup_city');
            $pickup_state = $this->get_option('pickup_state');
            $environment = $this->get_option('environment');


            // Get the total weight of the package
            foreach ($package['contents'] as $item) {

                $weight = floatval($item['data']->get_weight());
                $width = floatval($item['data']->get_width());
                $height = floatval($item['data']->get_height());
                $depth = floatval($item['data']->get_length());

                $seller = $item['data']->post->post_author;
                $author = get_user_by('id', $seller);
                // If dokan is installed
                if (function_exists('dokan_get_option')) {
                    $store_info = dokan_get_store_info($author->ID);

                    $pickup_address = $store_info['address']['street_1'];
                    $pickup_city = $store_info['address']['city'];
                    $pickup_state = $store_info['address']['state'];
                    $pickup_postcode = $store_info['address']['zip'];
                    $pickup_country = $store_info['address']['country'];
                    $full_pickup_address = "{$pickup_address}, {$pickup_city}, {$pickup_state}";
                } else {
                    if (!strlen($pickup_address) || !strlen($pickup_city) || strlen($pickup_state)) return;

                    $full_pickup_address = "{$pickup_address}, {$pickup_city}, {$pickup_state}";
                }

                $volume = $width * $height * $depth;
                $quantity = floatval($item['quantity']);
                $costs = [];
                $quotations_id = [];
                try {
                    require_once dirname(__FILE__) . '/LalamoveAPI.php';
                    require_once dirname(__FILE__) . '/GoogleMapsAPI.php';

                    $gmapi = new GoogleMapsAPI($google_maps_api_key);

                    $full_pickup_address = "{$pickup_address}, {$pickup_city}, {$pickup_state}";
                    $localization = $gmapi->get_geolocalization($full_pickup_address);
                    $localization_result = $localization->results[0];
                    $geometry = $localization_result->geometry;
                    $pickup_location = $geometry->location;


                    $full_destination_address = "{$destination_address}, {$destination_city}, {$destination_state}";
                    $localization = $gmapi->get_geolocalization($full_destination_address);
                    $localization_result = $localization->results[0];
                    $geometry = $localization_result->geometry;
                    $destination_location = $geometry->location;

                    // Create a new Lalamove API instance
                    $lalamove_api = new LalamoveAPI($api_key, $api_secret, $market, $environment);

                    $selected_city = NULL;
                    $response = $lalamove_api->get_cities();
                    $cities = $response->data;
                    foreach ($cities as $city) {
                        if (strtolower($city->name) == strtolower($destination_city)) $selected_city = $city;
                    }

                    if (!$selected_city) return NULL;
                    $services = $selected_city->services;
                    $possible_services = [];
                    foreach ($services as $service) {
                        $service_dimensions = $service->dimensions;
                        //var_dump($service_dimensions);
                        //die();
                        $service_width = $service_dimensions->width->value;
                        $service_height = $service_dimensions->height->value;
                        $service_length = $service_dimensions->length->value;

                        $service_volume = $service_width * $service_height * $service_length;

                        $service_weight = $service->load->value;

                        $valid_volume = ($service_volume >= $volume);
                        $valid_weight = ($service_weight >= $weight);

                        $service->volume = $service_volume;
                        if ($valid_volume && $valid_weight) $possible_services[] = $service;
                    }
                    usort($possible_services, fn ($a, $b) => floatval($a->volume) > floatval($b->volume));
                    usort($possible_services, fn ($a, $b) => floatval($a->load->value) > floatval($b->load->value));
                    $possible_service = $possible_services[0];


                    $serviceType = $possible_service->key;
                    $stops = [(object)[
                        'coordinates' => (object)[
                            'lat' => "$pickup_location->lat",
                            'lng' => "$pickup_location->lng",
                        ],
                        'address' => $pickup_address
                    ], (object)[
                        'coordinates' => (object)[
                            'lat' => "$destination_location->lat",
                            'lng' => "$destination_location->lng",
                        ],
                        'address' => $destination_address
                    ]];
                    $item = (object)[
                        "quantity" => "$quantity",
                        "weight" => "LESS_THAN_3KG",
                        "categories" => ["FOOD_DELIVERY", "OFFICE_ITEM"],
                        "handlingInstructions" => ["KEEP_UPRIGHT"]
                    ];
                    $response = $lalamove_api->quotations($serviceType, $stops, $language, $item);
                    $quotation = $response->data;
                    $priceBreakdown = $quotation->priceBreakdown->total;

                    $quotations_id[] = $quotation->quotationId;
                    $costs[] = $priceBreakdown;
                } catch (Exception $e) {
                    var_dump($e);
                    die();
                    return $e->getMessage();
                }
            }
            $rate = array(
                'quotations' => $quotations_id,
                'label' => $this->title,
                'cost' => $costs,
                'calc_tax' => 'per_item'
            );
            $this->add_rate($rate);
            //var_dump($destination);
            //die();

        }
    }
}
