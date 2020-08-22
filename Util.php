<?php declare(strict_types=1);

namespace Sms77ShopwareApi;

use Exception;
use ReflectionClassConstant;
use Shopware\Models\Order\Order;
use Sms77\Api\Client;

class Util {
    public static function shouldSend(array $config): bool {
        return !(!$config['sms77enabled'] || !isset($config['sms77apiKey']));
    }

    public static function getConfig(): array {
        return Shopware()
            ->Container()
            ->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('Sms77ShopwareApi');
    }

    public static function getSmsText(int $statusId, array $config, array $mappings): ?string {
        $text = null;

        if (array_key_exists($statusId, $mappings)) {
            $cfgKey = 'sms77textOn' . $mappings[$statusId];

            if (array_key_exists($cfgKey, $config)) {
                $text = $config[$cfgKey];
            }
        }

        if (array_key_exists('sms77signature', $config)
            && '' !== $config['sms77signature']) {
            $text = 'prepend' === $config['sms77signaturePosition']
                ? $text + $config['sms77signature']
                : $config['sms77signature'] + $text;
        }

        return $text;
    }

    public static function getClassConstantPairs(array $config, $classOrObject): array {
        $ids = [];

        foreach ($config['sms77events'] as $name) {
            try {
                $reflection = new ReflectionClassConstant($classOrObject, $name);

                if (strtoupper($name) === $reflection->getName()) {
                    $ids[] = $reflection->getValue();
                }
            } catch (Exception $exception) {
            }
        }

        return $ids;
    }

    public static function sms(array $config, Order $order, int $id, array $mappings): string {
        return (self::getClient($config))->sms(
            self::getPhoneFromOrder($order),
            self::getSmsText($id, $config, $mappings),
            self::getExtras($config));
    }

    public static function getPhoneFromOrder(Order $order): string {
        return '' === $order->getShipping()->getPhone()
            ? $order->getBilling()->getPhone()
            : $order->getShipping()->getPhone();
    }

    public static function getClient(array $config): Client {
        return new Client($config['sms77apiKey'], 'shopware');
    }

    public static function getExtras(array $config): array {
        $extras = [];

        if (isset($config['sms77from'])) {
            $extras['from'] = $config['sms77from'];
        }

        return $extras;
    }
}