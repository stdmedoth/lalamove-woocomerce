<?php

function lalamove_shipping_method()
{
    class Lalamove_Shipping_Method extends WC_Shipping_Method
    {

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
                'language' => array(
                    'title' => __('Language', 'lalamove_shipping_method'),
                    'type'          => 'select',
                    'options'           => array(
                        'pt_BR' => __('Português', 'lalamove_shipping_method'),
                    ),
                    'description' => __('Enter lalamove language market.', 'lalamove_shipping_method'),
                    'default' => '',
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Calculate the shipping rate for the Lalamove shipping method
         */
        public function calculate_shipping($package = array())
        {
            require_once dirname(__FILE__) . '/LalamoveAPI.php';
            // Get the shipping address
            $destination = array(
                'address_1' => $package['destination']['address'],
                'city' => $package['destination']['city'],
                'state' => $package['destination']['state'],
                'postcode' => $package['destination']['postcode'],
                'country' => $package['destination']['country']
            );

            // Get the total weight of the package
            $weight = 0;
            foreach ($package['contents'] as $item) {
                $weight += floatval($item['data']->get_weight()) * floatval($item['quantity']);
            }

            // Get the API key and secret
            $api_key = $this->get_option('api_key');
            $api_secret = $this->get_option('api_secret');

            // Create a new Lalamove API instance
            $lalamove_api = new LalamoveAPI($api_key, $api_secret);
            $serviceType = "MOTORCYCLE";
            $stops = [(object)[
                'coordinates' => (object)[
                    'lat' => '22.3353139',
                    'lng' => '114.1758402',
                ],
                'address' => 'Jl. Perum Dasana'
            ]];
            $quotation = $lalamove_api->quotations($serviceType, $stops);

            $priceBreakdown = $quotation->priceBreakdown->total;
            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => $priceBreakdown
            );
            $this->add_rate($rate);
        }
    }
}
