<?php
namespace Timber\Integration\Woocommerce;

use Timber\Integration\IntegrationInterface;


class WoocommerceIntegration implements IntegrationInterface
{
    public function should_init() : bool
    {
        return true;
    }

    public function init(): void
    {
        remove_action('init', ['WC_Template_Loader', 'init']);
    }
}