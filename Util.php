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

    public static function sms(array $config, Order $order, int $ev, array $mappings): string {

        return (self::getClient($config))->sms(
            self::getPhoneFromOrder($order),
            self::getSmsText($ev, $config, $mappings, $order),
            self::getExtras($config));
    }

    public static function getClient(array $config): Client {
        return new Client($config['sms77apiKey'], 'shopware');
    }

    public static function getPhoneFromOrder(Order $order): string {
        return '' === $order->getShipping()->getPhone()
            ? $order->getBilling()->getPhone()
            : $order->getShipping()->getPhone();
    }

    public static function getSmsText(int $status, array $cfg, array $mappings, Order $order): ?string {
        $text = null;

        if (array_key_exists($status, $mappings)) {
            $cfgKey = 'sms77textOn' . $mappings[$status];

            if (array_key_exists($cfgKey, $cfg)) {
                $text = $cfg[$cfgKey];
            }
        }

        if (array_key_exists('sms77signature', $cfg)
            && '' !== $cfg['sms77signature']) {
            $text = 'prepend' === $cfg['sms77signaturePosition']
                ? $text . $cfg['sms77signature']
                : $cfg['sms77signature'] . $text;
        }

        foreach (explode(' ', $text) as $word) {
            $word = str_replace(['.', ',', '?', '!', '¿'], '', $word); // remove endings

            if (!StringUtil::startsWith($word, '{{') || !StringUtil::endsWith($word, '}}')) {
                continue;
            }

            $placeholder = str_replace(['{', '}'], '', $word);
            $replace = static function ($replace) use ($placeholder, &$text) {
                $text = str_replace('{{' . $placeholder . '}}', $replace, $text);
            };

            if (false !== mb_strpos($placeholder, '->')) {
                $obj = $order;
                $parts = explode('->', $placeholder);

                foreach ($parts as $k => $part) {
                    $method = StringUtil::toGetter($part);

                    if (!method_exists($obj, $method)) {
                        break;
                    }

                    $obj = $obj->$method();

                    if ($k === count($parts) - 1) {
                        $replace($obj);
                    }
                }
            } else {
                $method = StringUtil::toGetter($placeholder);

                if (method_exists($order, $method)) {
                    $replace($order->$method());
                }
            }
        }

        return $text;
    }

    public static function getExtras(array $config): array {
        $extras = [];

        if (isset($config['sms77from'])) {
            $extras['from'] = $config['sms77from'];
        }

        return $extras;
    }
}