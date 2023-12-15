<?php

namespace Timber\Integration\Woocommerce\Woocommerce;

use Timber\Loader;
use Timber\Timber;
use Timber\LocationManager;
use Timber\Integration\Woocommerce\Utility\Location;


class PartialLoader
{
    use Location;

    public function __construct()
    {
        add_filter('wc_get_template', [$this, 'renderTemplate'], 10, 3 );
		add_filter('wc_get_template_part', [$this, 'renderTemplatePart'], 10, 3 );
    }

    public function renderTemplate($template, $template_name, $args)
    {
        // if (dirname($template_name) === 'emails') {
        //     return $template;
        // }

        if (apply_filters('asylum/timber_woocommerce/return_default_template', false, $template)) {
            return $template;
        }


        $twigFile = str_replace('.php', '.twig', basename($template));

        if (dirname($template_name)) {
            $twigFile = trailingslashit( dirname($template_name) ) . $twigFile;
        }


        global $product, $post;

        if ( ! $product ) {
            $product = wc_setup_product_data( $post );
        }

        $twigFile = $this->locateTemplate($twigFile);

        // We can access the context here without performance loss, because it was already cached.
		$context = Timber::context();

		// Add current product to context.
		if ($product instanceof \WC_Product) {
			$context['product'] = $product;
			$context['post_id'] = $product->get_id();
			$context['post']    = Timber::get_post($product->get_id());
		}

        // MOD - Fix related.twig - there was passed parent product ID for related/upsells(?) products
        if ($post && $product && $post->ID !== $product->get_id()) {
            $post_object = get_post($product->get_id());
            setup_postdata( $GLOBALS['post'] =& $post_object );
            $context['product'] = $product;
            $context['post_id'] = $product->get_id();
            $context['post']    = Timber::get_post($product->get_id());

        }

        // Add WC to the context
        // Add the arguments for the WooCommerce template.
		$context['wc'] = self::convertObjects( $args );


        // need to add a comment if its a reviews thing
        // might be a bit unelegant
        switch (basename($template_name)) {
            case 'review-rating.php':
            case 'review-meta.php':

                if (empty($context['wc']['comment'])) {
                    global $comment;
                    $context['wc']['comment'] = $comment;
                }
                break;
        }


        if (array_key_exists('debug', $_GET)) {

            dump('renderTemplate', $template, $template_name, $args);
        }

        // dd($twigFile);
        // dump($twigFile);
        Timber::render($twigFile, $context);

        // dd(dirname(__FILE__));
        return $this->returnEmpty();
    }

    public function renderTemplatePart($template, $slug, $name = '')
    {

        if (array_key_exists('debug', $_GET)) {

            dump('renderTemplatePart', $template, $slug, $name);
        }

        global $post, $product;

		if ( ! $product ) {
			$product = wc_setup_product_data( $post );
		}



        // We can access the context here without performance loss, because it was already cached.
        $context = Timber::context();

        // Add current product to context.
        if ($product instanceof \WC_Product) {
            $context['product'] = $product;
            $context['post_id'] = $product->get_id();
            $context['post']    = Timber::get_post($product->get_id());
        }

        // MOD - Fix related.twig - there was passed parent product ID for related/upsells(?) products
        if ($post->ID !== $product->get_id()) {
            $post_object = get_post($product->get_id());
            setup_postdata( $GLOBALS['post'] =& $post_object );
            $context['product'] = $product;
            $context['post_id'] = $product->get_id();
            $context['post']    = Timber::get_post($product->get_id());

        }

        $twigFile = $this->locateTemplate("{$slug}-{$name}.twig");

        Timber::render($twigFile, $context);
        return $this->returnEmpty();
    }

    public static function convertObjects($args)
    {
        foreach ($args as &$arg) {
            if ($arg instanceof \WP_Term) {
                $arg = Timber::get_term($arg);
            }

        }

        $args = self::maybeMakeCollection($args);

        return $args;
    }

    protected function locateTemplate($fileName)
    {
        $locations = apply_filters('timber/locations', []);

        $search = [];

        foreach ($locations as $location) {
            $search[] = trailingslashit($location[0]) . $fileName;
            $search[] = trailingslashit($location[0]) . 'woocommerce/' . $fileName;
        }

        // $search = array_map(fn($location) => $location[0] . '/' . $fileName, $locations);

        $caller = LocationManager::get_calling_script_dir( 1 );
		$loader = new Loader( $caller );
		$twigFile   = $loader->choose_template( $search );
        return $twigFile;
    }

    public static function maybeMakeCollection($args = [])
    {
        $collections = [];

        // Loop through args and add to collections array if it’s an array of WC_Product objects
		foreach ( $args as $key => $arg ) {
			// Bailout if not array or not array of WC_Product objects
			if ( ! is_array( $arg ) || empty( $arg ) || ! isset( $arg[0] )
				|| ! is_object( $arg[0] ) || ! is_a( $arg[0], 'WC_Product' ) ) {
				continue;
			}

			$collections[] = $key;
		}

		// Bailout early if there are no collections
		if ( empty( $collections ) ) {
			return $args;
		}

		// Convert product post collections into PostCollections
		foreach ( $collections as $collection ) {
			$posts = $args[ $collection ];

			/**
			 * A post collection currently needs a WP_Post object to work with.
			 *
			 * They will be converted to Product objects in the Post Collection using class maps.
			 *
			 * @todo Improve this in Timber 2.0.
			 */
			$posts = array_map( function( $post ) {
				if ( $post instanceof \WC_Product ) {
					return $post->get_id();
				}

				return $post;
			}, $posts );



			$args[ $collection ] = Timber::get_posts($posts);
		}

        return $args;
    }

    /**
     * Return path to empty file
     *
     * @return string
     */
    public function returnEmpty()
    {
        return dirname(dirname(dirname(__FILE__))) . '/resources/empty.php';
    }
}
