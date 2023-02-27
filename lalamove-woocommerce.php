<?php
/*
Plugin Name: Lalamove Shipping Method
Plugin URI: https://example.com/
Description: A plugin that integrates Lalamove as a shipping method for WooCommerce.
Version: 1.0
Author: Your Name
Author URI: https://example.com/
*/

// Add the Lalamove shipping method to WooCommerce
add_filter('woocommerce_shipping_methods', 'add_lalamove_shipping_method');
function add_lalamove_shipping_method($methods) {
    $methods['lalamove_shipping_method'] = 'Lalamove_Shipping_Method';
    return $methods;
}

// Define the Lalamove shipping method class
class Lalamove_Shipping_Method extends WC_Shipping_Method {
    
    // Initialize the shipping method
    public function __construct() {
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
    public function init() {
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
                'description' => __('Enter your Lalamove API
