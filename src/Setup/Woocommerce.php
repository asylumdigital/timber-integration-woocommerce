<?php

namespace Timber\Integration\Woocommerce\Setup;

use Timber\Integration\Woocommerce\Woocommerce\Media;
use Timber\Integration\Woocommerce\Woocommerce\Context;
use Timber\Integration\Woocommerce\Woocommerce\PartialLoader;
use Timber\Integration\Woocommerce\Woocommerce\TemplateLoader;

class Woocommerce
{
    protected static $contextCache = [];

    public function __construct()
    {
        // shows templates in woocommerce status as twig
        add_filter('wc_get_template', function ($file, $file2, $arr, $path, $pluginPath) {
            if (is_admin()) {

                $fileName = str_replace('.php', '.twig', $file);
                $file = trailingslashit(get_stylesheet_directory()) . 'app/Resources/views/' . $path . $fileName;

                if (!file_exists($file)) {
                    $file = trailingslashit(dirname(dirname(dirname(__FILE__)))) . 'resources/views/' . $path . $fileName;

                }
                // error_log($file2);
                // error_log($file);
            }

            return $file;
        }, 10, 5);

        if (is_admin() && ($_GET['page'] ?? false) !== 'codemanas-woocommerce-preview-emails') {
            return;
        }

        remove_action('init', ['WC_Template_Loader', 'init']);
        add_action('init', [TemplateLoader::class, 'init']);
        new PartialLoader;
        new Context;
        new Media;
    }
}
