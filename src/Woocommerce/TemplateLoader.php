<?php

namespace Timber\Integration\Woocommerce\Woocommerce;

use Timber\Loader;
use Timber\Timber;
use Timber\LocationManager;

class TemplateLoader
{
    /**
     * Store the shop page ID.
     *
     * @var integer
     */
    private static $shop_page_id = 0;

    /**
     * Store whether we're processing a product inside the_content filter.
     *
     * @var boolean
     */
    private static $in_content_filter = false;

    /**
     * Is WooCommerce support defined?
     *
     * @var boolean
     */
    private static $theme_support = false;

    public static function init()
    {
        self::$theme_support = wc_current_theme_supports_woocommerce_or_fse();
        self::$shop_page_id  = wc_get_page_id( 'shop' );

        // Supported themes.
        if ( self::$theme_support ) {
            add_filter( 'template_include', [__CLASS__, 'template_loader']);
            add_filter( 'comments_template', [__CLASS__, 'comments_template_loader']);

            // Loads gallery scripts on Product page for FSE themes.
            // if ( wc_current_theme_is_fse_theme() ) {
            //     self::add_support_for_product_page_gallery();
            // }
        } else {
            // Unsupported themes.
            // TODO: Handle unsupported themes? Probably not required as we add theme support!
            // add_action( 'template_redirect', array( __CLASS__, 'unsupported_theme_init' ) );
        }


    }

    public static function template_loader($template)
    {
        $default_file = self::get_template_loader_default_file();

        $locations = apply_filters('timber/locations', []);

        $search_files = self::get_template_loader_files( $default_file );

        $f = [];

        foreach (array_merge(...$locations) as $path) {
            foreach ($search_files as $file) {
                $f[] = trailingslashit($path) . $file;
            }
        }

        $caller = LocationManager::get_calling_script_dir();
		$loader = new Loader( $caller );
		return $loader->choose_template($f) ?: $template;
    }

    public static function comments_template_loader($template)
    {
        if ( get_post_type() !== 'product' ) {
			return $template;
		}

        $locations = apply_filters('timber/locations', []);

        global $product;
        Timber::render('single-product-reviews.twig', [
            'product' => $product,
        ]);


        return dirname(dirname(dirname(__FILE__))) . '/resources/empty.php';
    }

    /**
     * Get the default filename for a template except if a block template with
     * the same name exists.
     *
     * @since  3.0.0
     * @since  5.5.0 If a block template with the same name exists, return an
     * empty string.
     * @since  6.3.0 It checks custom product taxonomies
     * @return string
     */
    private static function get_template_loader_default_file() {
        if (
            is_singular( 'product' ) &&
            ! self::has_block_template( 'single-product' )
        ) {
            $default_file = 'single-product.twig';
        } elseif ( is_product_taxonomy() ) {
            $object = get_queried_object();

            if ( self::taxonomy_has_block_template( $object ) ) {
                $default_file = '';
            } else {
                if ( taxonomy_is_product_attribute( $object->taxonomy ) ) {
                    $default_file = 'taxonomy-product-attribute.twig';
                } elseif ( is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) {
                    $default_file = 'taxonomy-' . $object->taxonomy . '.twig';
                } elseif ( ! self::has_block_template( 'archive-product' ) ) {
                    $default_file = 'archive-product.twig';
                } else {
                    $default_file = '';
                }
            }
        } elseif (
            ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) &&
            ! self::has_block_template( 'archive-product' )
        ) {
            $default_file = self::$theme_support ? 'archive-product.twig' : '';
        } else {
            $default_file = '';
        }
        return $default_file;
    }

    /**
     * Checks whether a block template with that name exists.
     *
     * **Note: ** This checks both the `templates` and `block-templates` directories
     * as both conventions should be supported.
     *
     * @since  5.5.0
     * @param string $template_name Template to check.
     * @return boolean
     */
    private static function has_block_template( $template_name ) {
        if ( ! $template_name ) {
            return false;
        }

        $has_template            = false;
        $template_filename       = $template_name . '.html';
        // Since Gutenberg 12.1.0, the conventions for block templates directories have changed,
        // we should check both these possible directories for backwards-compatibility.
        $possible_templates_dirs = array( 'templates', 'block-templates' );

        // Combine the possible root directory names with either the template directory
        // or the stylesheet directory for child themes, getting all possible block templates
        // locations combinations.
        $filepath        = DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template_filename;
        $legacy_filepath = DIRECTORY_SEPARATOR . 'block-templates' . DIRECTORY_SEPARATOR . $template_filename;
        $possible_paths  = array(
            get_stylesheet_directory() . $filepath,
            get_stylesheet_directory() . $legacy_filepath,
            get_template_directory() . $filepath,
            get_template_directory() . $legacy_filepath,
        );

        // Check the first matching one.
        foreach ( $possible_paths as $path ) {
            if ( is_readable( $path ) ) {
                $has_template = true;
                break;
            }
        }

        /**
         * Filters the value of the result of the block template check.
         *
         * @since x.x.x
         *
         * @param boolean $has_template value to be filtered.
         * @param string $template_name The name of the template.
         */
        return (bool) apply_filters( 'woocommerce_has_block_template', $has_template, $template_name );
    }

    /**
     * Checks whether a block template for a given taxonomy exists.
     *
     * **Note:** This checks both the `templates` and `block-templates` directories
     * as both conventions should be supported.
     *
     * @param object $taxonomy Object taxonomy to check.
     * @return boolean
     */
    private static function taxonomy_has_block_template( $taxonomy ) : bool {
        if ( taxonomy_is_product_attribute( $taxonomy->taxonomy ) ) {
            $template_name = 'taxonomy-product_attribute';
        } else {
            $template_name = 'taxonomy-' . $taxonomy->taxonomy;
        }

        return self::has_block_template( $template_name );
    }

    /**
     * Get an array of filenames to search for a given template.
     *
     * @since  3.0.0
     * @param  string $default_file The default file name.
     * @return string[]
     */
    private static function get_template_loader_files( $default_file ) {
        $templates   = apply_filters( 'woocommerce_template_loader_files', array(), $default_file );
        // $templates[] = 'woocommerce.php';

        if ( is_page_template() ) {
            $page_template = get_page_template_slug();

            if ( $page_template ) {
                $validated_file = validate_file( $page_template );
                if ( 0 === $validated_file ) {
                    $templates[] = $page_template;
                } else {
                    error_log( "WooCommerce: Unable to validate template path: \"$page_template\". Error Code: $validated_file." ); // phpcs:ignore WordPress.twig.DevelopmentFunctions.error_log_error_log
                }
            }
        }

        if ( is_singular( 'product' ) ) {
            $object       = get_queried_object();
            $name_decoded = urldecode( $object->post_name );
            if ( $name_decoded !== $object->post_name ) {
                $templates[] = "single-product-{$name_decoded}.twig";
                $templates[] = WC()->template_path() . "single-product-{$name_decoded}.twig";
            }

            $templates[] = "single-product-{$object->post_name}.twig";
            $templates[] = WC()->template_path() . "single-product-{$object->post_name}.twig";
        }

        if ( is_product_taxonomy() ) {
            $object = get_queried_object();

            $templates[] = 'taxonomy-' . $object->taxonomy . '-' . $object->slug . '.twig';
            $templates[] = WC()->template_path() . 'taxonomy-' . $object->taxonomy . '-' . $object->slug . '.twig';
            $templates[] = 'taxonomy-' . $object->taxonomy . '.twig';
            $templates[] = WC()->template_path() . 'taxonomy-' . $object->taxonomy . '.twig';

            if ( taxonomy_is_product_attribute( $object->taxonomy ) ) {
                $templates[] = 'taxonomy-product_attribute.twig';
                $templates[] = WC()->template_path() . 'taxonomy-product_attribute.twig';
                $templates[] = $default_file;
            }

            if ( is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) {
                $cs_taxonomy = str_replace( '_', '-', $object->taxonomy );
                $cs_default  = str_replace( '_', '-', $default_file );
                $templates[] = 'taxonomy-' . $object->taxonomy . '-' . $object->slug . '.twig';
                $templates[] = WC()->template_path() . 'taxonomy-' . $cs_taxonomy . '-' . $object->slug . '.twig';
                $templates[] = 'taxonomy-' . $object->taxonomy . '.twig';
                $templates[] = WC()->template_path() . 'taxonomy-' . $cs_taxonomy . '.twig';
                $templates[] = $cs_default;
            }
        }

        $templates[] = $default_file;
        if ( isset( $cs_default ) ) {
            $templates[] = WC()->template_path() . $cs_default;
        }
        $templates[] = WC()->template_path() . $default_file;

        return array_unique( $templates );
    }


}
