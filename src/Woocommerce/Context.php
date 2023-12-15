<?php

namespace Timber\Integration\Woocommerce\Woocommerce;

use Timber\Term;
use Timber\Timber;

class Context
{
    public static $contextCache = [];

    public function __construct()
    {
        // Fixes product global for singular product pages.
		// add_action( 'woocommerce_before_main_content', [$this, 'setupProductData'], 1 );

        // Add WooCommerce context data to normal context.
		add_filter( 'timber/context', [$this, 'getWoocommerceContext']);
    }

    /**
	 * Fixes product global for singular product pages.
	 *
	 * On singular product pages, the product global is set up when the template loads. But it is
	 * reset when Timber calls the 'the_post' action repeatedly on objects that are not
	 * WooCommerce posts.
	 *
	 * By hooking into the 'woocommerce_before_main_content' action, we can set up the product
	 * global again.
	 *
	 * @since 0.6.2
	 * @link https://github.com/timber/timber/issues/1639
	 * @see Remove this for Timber 2.0?
	 */
	public function setupProductData() {
		// Setup missing product global.
		global $product, $post;

		if ( ! $product ) {
			$product = wc_setup_product_data( $post );
		}
	}

    public function getWoocommerceContext( $context = [] ) {
		if ( empty( self::$contextCache ) ) {
			$woocommerce_context = [];

			if ( is_singular( 'product' ) ) {
				$woocommerce_context['post'] = Timber::get_post();
			} elseif ( is_archive() ) {
				// Add shop page to context
				if ( is_shop() ) {
					$woocommerce_context['post'] = Timber::get_post( wc_get_page_id( 'shop' ) );
				}

				if ( is_product_taxonomy() ) {
					$woocommerce_context['term'] = Timber::get_term( get_queried_object() );
				}
			}

            if (is_cart() || is_checkout()) {

                $woocommerce_context['customer'] = WC()->customer;
                $woocommerce_context['countries'] = WC()->countries;
            }

			// Always add cart to context
			$woocommerce_context['cart'] = WC()->cart;

            $woocommerce_context['woocommerce'] = WC();

			self::$contextCache = apply_filters( 'timber/woocommerce/context', $woocommerce_context );
		}

		$context = array_merge( $context, self::$contextCache );

		return $context;
	}
}
