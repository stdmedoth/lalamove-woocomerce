<?php
/*
Plugin Name: Lalamove Shipping Method
Description: A plugin that integrates Lalamove as a shipping method for WooCommerce.
Version: 1.0
Author: stdmedoth (João Calisto)
*/

// Add the Lalamove shipping method to WooCommerce
add_filter('woocommerce_shipping_methods', 'add_lalamove_shipping_method');
function add_lalamove_shipping_method($methods)
{
    $methods['lalamove_shipping_method'] = 'Lalamove_Shipping_Method';
    return $methods;
}

// Define the Lalamove shipping method class
class Lalamove_Shipping_Method extends WC_Shipping_Method
{

    // Initialize the shipping method
    public function __construct()
    {
        $this->id = 'lalamove_shipping_method';
        $this->title = __('Lalamove Shipping Method', 'lalamove_shipping_method');
        $this->method_description = __('Use Lalamove for fast and secure delivery', 'lalamove_shipping_method');
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
        $this->instance_form_fields = array(
            'enabled' => array(
                'title' => __('Enable Lalamove Shipping', 'lalamove_shipping_method'),
                'type' => 'checkbox',
                'default' => 'yes',
                'description' => __('Enable this shipping method to use Lalamove for delivery', 'lalamove_shipping_method'),
            ),
            'api_key' => array(
                'title' => __('Lalamove API Key', 'lalamove_shipping_method'),
                'type' => 'text',
                'description' => __('Enter your Lalamove API key', 'lalamove_shipping_method'),
                'default' => '',
            ),
            'api_secret' => array(
                'title' => __('Lalamove API Secret', 'lalamove_shipping_method'),
                'type' => 'password',
                'description' => __('Enter your Lalamove API secret', 'lalamove_shipping_method'),
                'default' => '',
            ),
            'delivery_time' => array(
                'title' => __('Delivery Time', 'lalamove_shipping_method'),
                'type' => 'text',
                'description' => __('Enter the estimated delivery time for Lalamove', 'lalamove_shipping_method'),
                'default' => '2-4 hours',
            ),
            'delivery_price' => array(
                'title' => __('Delivery Price', 'lalamove_shipping_method'),
                'type' => 'text',
                'description' => __('Enter the delivery price for Lalamove', 'lalamove_shipping_method'),
                'default' => '10',
            ),
        );
    }

    // Calculate the shipping cost for an order
    public function calculate_shipping($package = array())
    {
        $this->add_rate(array(
            'id' => $this->id,
            'label' => $this->title,
            'cost' => $this->instance_settings['delivery_price'],
            'package' => $package,
        ));
    }

    // Validate the settings for the shipping method
    public function validate_instance_settings_form()
    {
        $api_key = isset($_POST['instance_settings']['api_key']) ? $_POST['instance_settings']['api_key'] : '';
        $api_secret = isset($_POST['instance_settings']['api_secret']) ? $_POST['instance_settings']['api_secret'] : '';

        if (empty($api_key)) {
            $this->errors[] = __('Please enter your Lalamove API key', 'lalamove_shipping_method');
        }

        if (empty($api_secret)) {
            $this->errors[] = __('Please enter your Lalamove API secret', 'lalamove_shipping_method');
        }

        return count($this->errors) == 0;
    }
}
