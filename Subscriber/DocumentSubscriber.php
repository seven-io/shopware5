<?php declare(strict_types=1);

namespace Sms77ShopwareApi\Subscriber;

use Enlight_Hook_HookArgs;
use Enlight\Event\SubscriberInterface;
use Shopware\Components\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Repository as OrderRepository;
use Sms77ShopwareApi\DocumentCreatedEvents;
use Sms77ShopwareApi\Util;
use Shopware\Models\Order\Order;

class DocumentSubscriber implements SubscriberInterface {
    /** @var Logger $logger */
    private $logger;

    /** @var OrderRepository $orderRepo */
    private $orderRepo;

    private const MAPPINGS = [
        1 => 'DocumentCreatedInvoice',
        2 => 'DocumentCreatedDeliveryNotice',
        3 => 'DocumentCreatedCredit',
        4 => 'DocumentCreatedCancellation',
    ];

    public function __construct(
        Logger $logger,
        ModelManager $modelManager
    ) {
        $this->logger = $logger;
        $this->orderRepo = $modelManager->getRepository(Order::class);
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     * @return array
     */
    public static function getSubscribedEvents() {
        return [
            'Shopware_Controllers_Backend_Order::createDocumentAction::after'
            => 'onCreateDocument',
        ];
    }

    public function onCreateDocument(Enlight_Hook_HookArgs $arguments): void {
        $config = Util::getConfig();

        $this->logger->info('Preparing to dispatch SMS', $config);

        if (!Util::shouldSend($config)) {
            $this->logger->warning('Should not dispatch SMS', $config);

            return;
        }

        $request = $arguments->getSubject()->Request();
        $documentType = $request->getParam('documentType');
        $order = $this->orderRepo->find($request->getParam('orderId'));
        $pairs = Util::getClassConstantPairs($config, new DocumentCreatedEvents);
        $isValidEvent = in_array($documentType, $pairs, true);

        if (!$isValidEvent) {
            $this->logger->warning('Invalid event for dispatch SMS', $config);

            return;
        }

        $response = Util::sms(
            $config,
            $order,
            $documentType,
            self::MAPPINGS);

        $this->logger->info('After dispatch SMS', [
            'config' => $config,
            'response' => $response,
        ]);
    }
}