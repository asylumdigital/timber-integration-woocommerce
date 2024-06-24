<?php

namespace Timber\Integration\Woocommerce\Timber;

use WP_Post;

class Product extends \Timber\Post
{
    protected $product;

    /**
     * @var string What does this class represent in WordPress terms?
     */
    public $object_type = 'product';

    /**
     * @var string What does this class represent in WordPress terms?
     */
    public static $representation = 'product';

    public function setup() {
		parent::setup();

        // Do your own thing.
        $this->product = apply_filters( 'timber/integration/woocommerce/product', wc_get_product($this->wp_object), $this->wp_object() );

        // dump($this->id);;
        // dump($product);

        // $product = wc_get_product($this->wp_object);

		return $this;
	}

    public function get_wc_product()
    {
        if (!is_a($this->product, 'WC_Product')) {
            $this->product = wc_get_product($this->id);
        }

        return $this->product;
    }

}
