<?php

namespace Timber\Integration\Woocommerce\Utility;

use Timber\Loader;
use Timber\LocationManager;

trait Location
{
    protected static $locations = [];

    protected function getTemplateLocations($templates)
    {
        if (!is_array($templates)) {
            $templates = [$templates];
        }

        $f = [];


        $locations = self::$locations ?: apply_filters('timber/locations', []);

        foreach (array_merge(...$locations) as $path) {
            foreach ($templates as $file) {
                $f[] = trailingslashit($path) . $file;
            }
        }

        return $f;
    }

    protected function locateTemplate($search = [])
    {
        $caller = LocationManager::get_calling_script_dir(1);
		$loader = new Loader( $caller );
		return $loader->choose_template($search);
    }
}
