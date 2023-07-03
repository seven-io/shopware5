<?php declare(strict_types=1);

namespace SevenShopwareApi\Tests;

use ReflectionClass;
use Shopware\Components\Test\Plugin\TestCase;
use SevenShopwareApi\SevenShopwareApi as Plugin;

class PluginTest extends TestCase {
    protected static $ensureLoadedPlugins = [
        'SevenShopwareApi' => [],
    ];

    public function testCanCreateInstance() {
        /** @var Plugin $plugin */
        $plugin = Shopware()->Container()->get('kernel')
            ->getPlugins()[(new ReflectionClass(Plugin::class))->getShortName()];

        $this->assertInstanceOf(Plugin::class, $plugin);
    }
}
