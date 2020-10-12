<?php declare(strict_types=1);

namespace Sms77ShopwareApi\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Sms77ShopwareApi\Util;

class OrderSubscriber implements EventSubscriber {
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

    public function getSubscribedEvents(): array {
        return [Events::preUpdate];
    }

    public function preUpdate(PreUpdateEventArgs $evArgs): void {
        /** @var Status|null $newStatus */
        $newStatus = null;

        $isPaymentStatusChanged = $evArgs->hasChangedField(self::FIELD_PAYMENT_STATUS);

        if (!$isPaymentStatusChanged
            && !$evArgs->hasChangedField(self::FIELD_ORDER_STATUS)) {
            return;
        }

        /** @var Order $order */
        $order = $evArgs->getEntity();
        if (!$order instanceof Order) {
            return;
        }

        $cfg = Util::getConfig();
        if (!Util::shouldSend($cfg)) {
            return;
        }

        $key = $isPaymentStatusChanged
            ? self::FIELD_PAYMENT_STATUS : self::FIELD_ORDER_STATUS;
        $newStatus = $evArgs->getNewValue($key);

        $statusId = $newStatus->getId();
        $isValidEvent = in_array(
            $statusId, Util::getClassConstantPairs($cfg, Status::class), true);

        if (null !== $newStatus && $isValidEvent) {
            Util::sms($cfg, $order, $statusId, self::MAPPINGS);
        }
    }
}