![Sms77.io Logo](https://www.sms77.io/wp-content/uploads/2019/07/sms77-Logo-400x79.png "sms77")
# Sms77.io Shopware5 Plugin

## About
A Shopware 5 plugin to programmatically make use of the Sms77.io SMS gateway. 

Currently supported events:
- DOCUMENT_CREATED_INVOICE (via onCreateDocument)
- DOCUMENT_CREATED_DELIVERY_NOTICE (via onCreateDocument)
- DOCUMENT_CREATED_CREDIT (via onCreateDocument)
- DOCUMENT_CREATED_CANCELLATION (via onCreateDocument)
- ORDER_STATE_CANCELLED
- ORDER_STATE_READY_FOR_DELIVERY
- ORDER_STATE_COMPLETELY_DELIVERED
- ORDER_STATE_CLARIFICATION_REQUIRED
- PAYMENT_STATE_1ST_REMINDER
- PAYMENT_STATE_2ND_REMINDER
- PAYMENT_STATE_3RD_REMINDER

## Installation
<figure>
<figcaption>Vie file upload</figcaption>

1. Download the [latest release](https://github.com/sms77io/shopware5-plugin/releases/latest)
2. Upload the *.zip file via the plugin manager and activate it
3. Enable the plugin from the configuration page
4. Set an API key and enable wanted events
![sms77io Shopware5 Plugin Configuration](https://www.sms77.io/wp-content/uploads/localhost_shopware_backend_-2.png)
</figure>

<figure>
<figcaption>Vie composer</figcaption>

1. Open a terminal and navigate to the root of your Shopware5 installation
2. Running ```composer require sms77/shopware5-plugin``` downloads the latest release
3. Log in to the Shopware backend and navigate to Configuration->Plugin Manager->Installed
4. Click the green install button on the right and wait for the installation to finish
5. Press the activate button in the opened plugin popup
6. Set an API key and enable wanted events
</figure>

### License
Please see [License File](LICENSE) for more information.

#### Need help?
You are more than welcome to contact us via [Sms77.io](https://www.sms77.io) in order to get assistance.