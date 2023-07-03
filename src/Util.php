<?php declare(strict_types=1);

namespace SevenShopwareApi;

use Exception;
use ReflectionClassConstant;
use Shopware\Components\Logger;
use Shopware\Models\Order\Order;
use Sms77\Api\Client;

class Util {
    public static function getConfig(): array {
        return Shopware()
            ->Container()
            ->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('SevenShopwareApi');
    }

    public static function getClassConstantPairs(array $config, $classOrObject): array {
        $ids = [];

        foreach ($config['sevenevents'] as $name) {
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
        /** @var Logger $logger */
        $logger = Shopware()->Container()->get('pluginlogger');

        if (!self::shouldSend($config)) {
            $logger->warning('Should not dispatch SMS', $config);

            return "";
        }

        $to = self::getPhoneFromOrder($order);

        if (!$to) {
            return "301";
        }

        $response = (self::getClient($config))->sms(
            $to,
            self::getSmsText($ev, $config, $mappings, $order),
            self::getExtras($config));

        $logger->info('After dispatch SMS', [
            'config' => $config,
            'response' => $response,
        ]);

        return $response;
    }

    public static function shouldSend(array $config): bool {
        return !(!$config['sevenenabled'] || !isset($config['sevenapiKey']));
    }

    public static function getPhoneFromOrder(Order $order): ?string {
        $shipping = $order->getShipping();
        $billing = $order->getBilling();

        if (null === $shipping) {
            return null === $billing ? null : $billing->getPhone();
        }

        return $shipping->getPhone();
    }

    public static function getClient(array $config): Client {
        return new Client($config['sevenapiKey'], 'shopware');
    }

    public static function getSmsText(int $status, array $cfg, array $mappings, Order $order): ?string {
        $text = null;

        if (array_key_exists($status, $mappings)) {
            $cfgKey = 'seventextOn' . $mappings[$status];

            if (array_key_exists($cfgKey, $cfg)) {
                $text = $cfg[$cfgKey];
            }
        }

        if (array_key_exists('sevensignature', $cfg)
            && '' !== $cfg['sevensignature']) {
            $text = 'prepend' === $cfg['sevensignaturePosition']
                ? $text . $cfg['sevensignature']
                : $cfg['sevensignature'] . $text;
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

        if (isset($config['sevenfrom'])) {
            $extras['from'] = $config['sevenfrom'];
        }

        return $extras;
    }
}
