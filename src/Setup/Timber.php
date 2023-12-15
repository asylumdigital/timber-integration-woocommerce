<?php

namespace Timber\Integration\Woocommerce\Setup;

use Timber\Integration\Woocommerce\Timber\Product;

class Timber
{
    public function __construct()
    {
        add_filter('timber/locations', function($paths) {
            $paths[] = [
                trailingslashit(dirname(dirname(dirname(__FILE__)))) . 'resources/views/woocommerce'
            ];

            return $paths;
        }, 110);

        add_filter('timber/post/classmap', function ($classmap) {
            $classmap = [

                'product' => Product::class,
            ];

            return $classmap;
        });

        add_filter('timber/twig', function($twig) {

            $twig->addFilter(new \Twig\TwigFilter('esc_attr', 'esc_attr'));

            $twig->addFunction(new \Twig\TwigFunction('callstatic', function ($class, $method, ...$args) {
                if (!class_exists($class)) {
                    throw new \Exception("Cannot call static method $method on Class $class: Invalid Class");
                }

                if (!method_exists($class, $method)) {
                    throw new \Exception("Cannot call static method $method on Class $class: Invalid method");
                }

                return forward_static_call_array([$class, $method], $args);
            }));

            return $twig;
        });
    }
}
