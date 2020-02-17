<?php
declare(strict_types=1);

namespace Sms77ShopwareApi;

use ReflectionClass;
use ReflectionException;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Sms77\Api\Exception\InvalidOptionalArgumentException;
use Sms77\Api\Validator\SmsValidator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Shopware\Models\Shop\Shop;

class Sms77ShopwareApi extends Plugin
{
    public function install(InstallContext $installContext)
    {
        $valid = true;
        $from = Shopware()->Config()->get('shop_name');
        $p = 'DUMMY_API_KEY';

        try {
            (new SmsValidator(compact('from', 'p')))->from();
        } catch (InvalidOptionalArgumentException $exception) {
            $valid = false;
        }

        if ($valid) {
            $pluginManager = Shopware()->Container()->get('shopware_plugininstaller.plugin_manager');

            $pluginName = $pluginManager->getPluginByName((new ReflectionClass($this))->getShortName());
            $shop = Shopware()->Models()->getRepository(Shop::class)->findOneBy(['default' => true]);

            $pluginManager->saveConfigElement(
                $pluginName,
                'from',
                Shopware()->Config()->get('shop_name'),
                $shop);

            $pluginManager->saveConfigElement(
                $pluginName,
                'signaturePosition',
                'append',
                $shop);
        }

        parent::install($installContext);
    }

    public function build(ContainerBuilder $container)
    {
        $container->setParameter('sms77_shopware_api.plugin_dir', $this->getPath());

        parent::build($container);
    }
}
