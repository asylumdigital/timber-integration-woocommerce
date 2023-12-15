<?php

namespace Timber\Integration\Woocommerce\Woocommerce;

class Media
{
    public function __construct()
    {
        $this->disableMedia();
    }
    
    /**
     * Disable image functions added in WooCommerce.
     *
     * This is useful if you want to use Timber’s image functionalities to display images.
     *
     * @api
     */
    public function disableMedia() {
        if (apply_filters('asylum/woocommerce/disable_image', false)) {
            remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
            remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
            remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
            remove_action( 'woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10 );
        }
    }
}
