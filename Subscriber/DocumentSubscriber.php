<?php declare(strict_types=1);

namespace Sms77ShopwareApi\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Shopware\Components\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Repository as OrderRepository;
use Sms77ShopwareApi\Util;

class DocumentSubscriber implements SubscriberInterface {
    private const MAPPINGS = [
        1 => 'DocumentCreatedInvoice',
        2 => 'DocumentCreatedDeliveryNotice',
        3 => 'DocumentCreatedCredit',
        4 => 'DocumentCreatedCancellation',
    ];

    /** @var Logger $logger */
    private $logger;

    /** @var OrderRepository $orderRepo */
    private $orderRepo;

    public function __construct(
        Logger $logger,
        ModelManager $modelManager
    ) {
        $this->logger = $logger;
        $this->orderRepo = $modelManager->getRepository(Order::class);
    }

    public static function getSubscribedEvents(): array {
        return [
            'Shopware_Controllers_Backend_Order::createDocumentAction::after'
            => 'onCreateDocument',
        ];
    }

    public function onCreateDocument(Enlight_Hook_HookArgs $args): void {
        $cfg = Util::getConfig();

        $this->logger->info('Preparing to dispatch SMS', $cfg);

        $request = $args->getSubject()->Request();
        $docType = (int)$request->getParam('documentType');

        $this->isValidEvent($cfg, $docType) && Util::sms(
            $cfg,
            $this->orderRepo->find($request->getParam('orderId')),
            $docType,
            self::MAPPINGS);
    }

    private function isValidEvent(array $cfg, int $documentType): bool {
        $cls = new class {
            public const DOCUMENT_CREATED_INVOICE = 1;
            public const DOCUMENT_CREATED_DELIVERY_NOTICE = 2;
            public const DOCUMENT_CREATED_CREDIT = 3;
            public const DOCUMENT_CREATED_CANCELLATION = 4;
        };

        $values = Util::getClassConstantPairs($cfg, $cls);

        if (in_array($documentType, $values)) {
            return true;
        }

        $this->logger->warning(
            "Invalid document subscription event '$documentType' for dispatch SMS", $cfg);

        return false;
    }
}