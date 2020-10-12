<?php
declare(strict_types=1);

namespace Sms77ShopwareApi;

use Enlight_Event_EventArgs;
use ReflectionClass;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use Sms77\Api\Exception\InvalidOptionalArgumentException;
use Sms77\Api\Validator\SmsValidator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class Sms77ShopwareApi extends Plugin {
    public static function getSubscribedEvents(): array {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Sms77Api'
            => 'onGetBackendController',
            'Shopware_Modules_Order_SendMail_FilterVariables' => 'onSaveOrder',
        ];
    }

    public function activate(ActivateContext $context): void {
        $context->scheduleClearCache(ActivateContext::CACHE_LIST_DEFAULT);
    }

    public function deactivate(DeactivateContext $context): void {
        $context->scheduleClearCache(DeactivateContext::CACHE_LIST_DEFAULT);
    }

    public function build(ContainerBuilder $container): void {
        $container->setParameter('sms77_shopware_api.plugin_dir', $this->getPath());

        parent::build($container);
    }

    public function install(InstallContext $installContext): void {
        $pluginManager =
            Shopware()->Container()->get('shopware_plugininstaller.plugin_manager');
        $pluginName =
            $pluginManager->getPluginByName((new ReflectionClass($this))->getShortName());
        $shop = Shopware()->Models()->getRepository(Shop::class)
            ->findOneBy(['default' => true]);
        $shopName = Shopware()->Config()->get('shop_name');

        $validShopName = true;
        try {
            (new SmsValidator(['p' => 'DUMMY_API_KEY', 'from' => $shopName]))->from();
        } catch (InvalidOptionalArgumentException $exception) {
            $validShopName = false;
        }

        $pluginManager->saveConfigElement($pluginName,
            'sms77from', $validShopName ? $shopName : 'sms77io', $shop);
        $pluginManager->saveConfigElement($pluginName,
            'sms77signaturePosition', 'append', $shop);

        parent::install($installContext);
    }

    public function onGetBackendController(): string {
        return __DIR__ . '/Controllers/Backend/Sms77Api.php';
    }

    public function onSaveOrder(Enlight_Event_EventArgs $args): void {
        $eventOptionsKey = 12;

        Util::sms(
            Util::getConfig(),
            Shopware()->Models()->getRepository(Order::class)->findOneBy(
                ['number' => $args->getReturn()['ordernumber']]),
            $eventOptionsKey,
            [$eventOptionsKey => 'SaveOrder',]
        );
    }
}
