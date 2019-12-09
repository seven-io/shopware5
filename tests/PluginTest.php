<?php

namespace Sms77ShopwareApi\Tests;

use Sms77ShopwareApi\Sms77ShopwareApi as Plugin;
use Shopware\Components\Test\Plugin\TestCase;

class PluginTest extends TestCase
{
    protected static $ensureLoadedPlugins = [
        'Sms77ShopwareApi' => []
    ];

    public function testCanCreateInstance()
    {
        /** @var Plugin $plugin */
        $plugin = Shopware()->Container()->get('kernel')->getPlugins()['Sms77ShopwareApi'];

        $this->assertInstanceOf(Plugin::class, $plugin);
    }
}
