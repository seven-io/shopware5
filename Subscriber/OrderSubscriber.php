<?php declare(strict_types=1);

namespace Sms77ShopwareApi\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Sms77ShopwareApi\Util;

class OrderSubscriber implements EventSubscriber
{
    private const MAPPINGS = [
        -1 => 'OrderStateCancelled',
        5 => 'OrderStateReadyForDelivery',
        7 => 'OrderStateCompletelyDelivered',
        8 => 'OrderStateClarificationRequired',
        13 => 'PaymentState1stReminder',
        14 => 'PaymentState2ndReminder',
        15 => 'PaymentState3rdReminder',
    ];
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

        if (!$isPaymentStatusChanged
            && !$eventArgs->hasChangedField(self::FIELD_ORDER_STATUS)) {
            return;
        }

        /** @var Order $order */
        $order = $eventArgs->getEntity();
        if (!$order instanceof Order) {
            return;
        }

        $pluginConfig = Util::getConfig();
        if (!Util::shouldSend($pluginConfig)) {
            return;
        }

        $key = $isPaymentStatusChanged
            ? self::FIELD_PAYMENT_STATUS : self::FIELD_ORDER_STATUS;
        $newStatus = $eventArgs->getNewValue($key);

        $statusId = $newStatus->getId();
        $pairs = Util::getClassConstantPairs($pluginConfig, Status::class);
        $isValidEvent = in_array($statusId, $pairs, true);

        if (null !== $newStatus && $isValidEvent) {
            Util::sms($pluginConfig, $order, $statusId, self::MAPPINGS);
        }
    }
}