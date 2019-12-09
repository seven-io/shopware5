<?php
declare(strict_types=1);

namespace Sms77ShopwareApi\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\Debug;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Sms77\Api\Client;

class OrderSubscriber implements EventSubscriber
{
    private const FIELD_PAYMENT_STATUS = 'paymentStatus';
    private const FIELD_ORDER_STATUS = 'orderStatus';

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [Events::preUpdate];
    }

    private function log($data): void
    {
        $debug = Debug::dump($data, 2, true, false);
        Shopware()->Container()->get('pluginlogger')->info($debug);
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        /** @var Status|null $newStatus */
        $newStatus = null;

        $isPaymentStatusChanged = $eventArgs->hasChangedField(self::FIELD_PAYMENT_STATUS);

        if (!$isPaymentStatusChanged && !$eventArgs->hasChangedField(self::FIELD_ORDER_STATUS)) {
            return;
        }

        /** @var Order $order */
        $order = $eventArgs->getEntity();
        if (!$order instanceof Order) {
            return;
        }

        $config = Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('Sms77ShopwareApi');
        if (!$config['enabled'] || !isset($config['apiKey'])) {
            return;
        }

        $newStatus = $eventArgs->getNewValue($isPaymentStatusChanged ? self::FIELD_PAYMENT_STATUS : self::FIELD_ORDER_STATUS);

        if (null !== $newStatus && in_array($newStatus->getId(), $this->statusNamesToIds($config['events']), true)) {
            $extra = [];
            if (isset($config['from'])) {
                $extra['from'] = $config['from'];
            }

            $client = new Client($config['apiKey']);
            $client->sms(
                '' === $order->getShipping()->getPhone() ? $order->getBilling()->getPhone() : $order->getShipping()->getPhone(),
                $this->getSmsText($newStatus->getId(), $config),
                $extra);
        }
    }

    private function getSmsText(int $statusId, array $config): ?string
    {
        $text = null;

        $mappings = [
            -1 => 'OrderStateCancelled',
            5 => 'OrderStateReadyForDelivery',
            7 => 'OrderStateCompletelyDelivered',
            8 => 'OrderStateClarificationRequired',
            13 => 'PaymentState1stReminder',
            14 => 'PaymentState2ndReminder',
            15 => 'PaymentState3rdReminder'
        ];

        if (array_key_exists($statusId, $mappings)) {
            $cfgKey = 'textOn' . $mappings[$statusId];

            if (array_key_exists($cfgKey, $config)) {
                $text = $config[$cfgKey];
            }
        }

        return $text;
    }

    private function statusNamesToIds(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            try {
                $reflection = new \ReflectionClassConstant(Status::class, $name);
                if (strtoupper($name) === $reflection->getName()) {
                    $ids[] = $reflection->getValue();
                }
            } catch (\Exception $exception) {
            }
        }

        return $ids;
    }
}