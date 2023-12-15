<?php

namespace Timber\Integration\Woocommerce\Setup;

class Core
{
    const SUPPORT = [
        'woocommerce',
        'wc-product-gallery-zoom',
        'wc-product-gallery-lightbox',
        'wc-product-gallery-slider',
    ];

    public function __construct()
    {
        add_action('after_setup_theme', [$this, 'themeSupport']);
    }

    /**
     * Add WooCommerce theme support
     *
     * @return void
     */
    public function themeSupport()
    {
        foreach (apply_filters('asylum/timber_woocommerce/theme_support', self::SUPPORT) as $feature) {
            add_theme_support($feature);
        }
    }
}
