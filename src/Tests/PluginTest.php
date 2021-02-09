<?php declare(strict_types=1);

namespace Sms77ShopwareApi\Tests;

use ReflectionClass;
use Shopware\Components\Test\Plugin\TestCase;
use Sms77ShopwareApi\Sms77ShopwareApi as Plugin;

class PluginTest extends TestCase {
    protected static $ensureLoadedPlugins = [
        'Sms77ShopwareApi' => [],
    ];

    public function testCanCreateInstance() {
        /** @var Plugin $plugin */
        $plugin = Shopware()->Container()->get('kernel')
            ->getPlugins()[(new ReflectionClass(Plugin::class))->getShortName()];

        $this->assertInstanceOf(Plugin::class, $plugin);
    }
}
