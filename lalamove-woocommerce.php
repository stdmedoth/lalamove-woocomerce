<?php
/*
Plugin Name: Lalamove Shipping Method
Description: A plugin that integrates Lalamove as a shipping method for WooCommerce.
Version: 1.0
Author: stdmedoth (João Calisto)
*/

require_once dirname(__FILE__) . '/includes/functions.php';

class LalamovePlugin
{

    public function __construct()
    {
        if (lalamove_check_is_woocommerce_active()) {
            require_once dirname(__FILE__) . '/includes/lalamove-woocommerce-method.php';
            add_action('woocommerce_shipping_init', 'lalamove_shipping_method');
            add_filter('woocommerce_shipping_methods', [$this, 'add_shipping_method']);
        } else {
            add_action('admin_notices', array($this, 'notice_activate_wc'));
        }
    }

    public function add_shipping_method($methods)
    {
        $methods['lalamove_shipping_method'] = 'Lalamove_Shipping_Method';
        return $methods;
    }

    public function notice_activate_wc()
    { ?>
        <div class="error">
            <p>
                <?php
                printf(esc_html__('Please install and activate %1$sWooCommerce%2$s to use Lalamove!'), '<a href="' . esc_url(admin_url('plugin-install.php?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins')) . '">', '</a>');
                ?>
            </p>
        </div>
<?php
    }
}

$lalamove = new LalamovePlugin();
