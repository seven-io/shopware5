<?php
declare(strict_types=1);

namespace Sms77ShopwareApi\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\Debug;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Exception;
use ReflectionClassConstant;
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

        $pluginConfig = Shopware()->Container()->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('Sms77ShopwareApi');
        if (!$pluginConfig['sms77enabled'] || !isset($pluginConfig['sms77apiKey'])) {
            return;
        }

        $newStatus =
            $eventArgs->getNewValue($isPaymentStatusChanged ? self::FIELD_PAYMENT_STATUS : self::FIELD_ORDER_STATUS);

        $isValidEvent = in_array($newStatus->getId(), $this->statusNamesToIds($pluginConfig['sms77events']), true);

        if (null !== $newStatus && $isValidEvent) {
            $extra = [];
            if (isset($pluginConfig['sms77from'])) {
                $extra['from'] = $pluginConfig['sms77from'];
            }

            $client = new Client($pluginConfig['sms77apiKey'], 'shopware');
            $to = '' === $order->getShipping()->getPhone()
                ? $order->getBilling()->getPhone()
                : $order->getShipping()->getPhone();
            $client->sms(
                $to,
                $this->getSmsText($newStatus->getId(), $pluginConfig),
                $extra);
        }
    }

    private function getSmsText(int $statusId, array $pluginConfig): ?string
    {
        $text = null;

        $mappings = [
            -1 => 'OrderStateCancelled',
            5 => 'OrderStateReadyForDelivery',
            7 => 'OrderStateCompletelyDelivered',
            8 => 'OrderStateClarificationRequired',
            13 => 'PaymentState1stReminder',
            14 => 'PaymentState2ndReminder',
            15 => 'PaymentState3rdReminder',
        ];

        if (array_key_exists($statusId, $mappings)) {
            $cfgKey = 'sms77textOn' . $mappings[$statusId];

            if (array_key_exists($cfgKey, $pluginConfig)) {
                $text = $pluginConfig[$cfgKey];
            }
        }

        if (array_key_exists('sms77signature', $pluginConfig) && mb_strlen($pluginConfig['sms77signature'])) {
            $text = 'prepend' === $pluginConfig['sms77signaturePosition']
                ? $text + $pluginConfig['sms77signature']
                : $pluginConfig['sms77signature'] + $text;
        }

        return $text;
    }

    private function statusNamesToIds(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            try {
                $reflection = new ReflectionClassConstant(Status::class, $name);

                if (strtoupper($name) === $reflection->getName()) {
                    $ids[] = $reflection->getValue();
                }
            } catch (Exception $exception) {
            }
        }

        return $ids;
    }
}