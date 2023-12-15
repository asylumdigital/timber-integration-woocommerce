<?php

namespace Timber\Integration\Woocommerce\Files;

use Timber\Integration\Woocommerce\Setup\Core;
use Timber\Integration\Woocommerce\Setup\Timber;
use Timber\Integration\Woocommerce\Setup\Woocommerce;
use Timber\Integration\Woocommerce\WoocommerceIntegration;

// add_filter('timber/integrations', function (array $integrations): array {
//     $integrations[] = new WoocommerceIntegration();

//     return $integrations;
// });

new Core;
new Timber;
new Woocommerce;
