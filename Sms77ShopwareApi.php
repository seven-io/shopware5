<?php
declare(strict_types=1);

namespace Sms77ShopwareApi;

use ReflectionClass;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Models\Shop\Shop;
use Sms77\Api\Exception\InvalidOptionalArgumentException;
use Sms77\Api\Validator\SmsValidator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Sms77ShopwareApi extends Plugin
{
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('sms77_shopware_api.plugin_dir', $this->getPath());

        parent::build($container);
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Sms77Api' => 'onGetBackendController'
        ];
    }

    public function install(InstallContext $installContext)
    {
        $pluginManager = Shopware()->Container()->get('shopware_plugininstaller.plugin_manager');
        $pluginName = $pluginManager->getPluginByName((new ReflectionClass($this))->getShortName());
        $shop = Shopware()->Models()->getRepository(Shop::class)->findOneBy(['default' => true]);

        $saveConfigElement = function (string $name, $value) use ($pluginManager, $pluginName, $shop) {
            $pluginManager->saveConfigElement($pluginName, 'sms77' . $name, $value, $shop);
        };

        $shopName = Shopware()->Config()->get('shop_name');
        $validShopName = true;
        try {
            (new SmsValidator(['p' => 'DUMMY_API_KEY', 'from' => $shopName]))->from();
        } catch (InvalidOptionalArgumentException $exception) {
            $validShopName = false;
        }

        $saveConfigElement('from', $validShopName ? $shopName : 'sms77io');

        $saveConfigElement('signaturePosition', 'append');

        $saveConfigElement('type', 'direct');

        parent::install($installContext);
    }

    public function onGetBackendController()
    {
        return __DIR__ . '/Controllers/Backend/Sms77Api.php';
    }
}
