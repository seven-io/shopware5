<?php
declare(strict_types=1);

namespace Sms77ShopwareApi;

class DocumentCreatedEvents {
    public const DOCUMENT_CREATED_INVOICE = 1;
    public const DOCUMENT_CREATED_DELIVERY_NOTICE = 2;
    public const DOCUMENT_CREATED_CREDIT = 3;
    public const DOCUMENT_CREATED_CANCELLATION = 4;
}