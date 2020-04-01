<?php declare(strict_types=1);

use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Shopware\Components\CSRFWhitelistAware;
use Sms77\Api\Client;

class Shopware_Controllers_Backend_Sms77Api extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    public function preDispatch()
    {
        $this->get('template')->addTemplateDir(__DIR__ . '/../../Resources/views/');
    }

    public function postDispatch()
    {
        $this->View()->assign([
            'csrfToken' => $this->container->get('BackendSession')->offsetGet('X-CSRF-Token'),
        ]);
    }

    public function indexAction()
    {
        $modelManager = $this->getModelManager();

        $pluginConfig = Shopware()
            ->Container()
            ->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('Sms77ShopwareApi');

        $apiKey = $pluginConfig['sms77apiKey'];

        $allCustomerGroups =
            $modelManager->getRepository('Shopware\Models\Customer\Group')->findAll();
        $activeCountries =
            $modelManager->getRepository('Shopware\Models\Country\Country')->findBy(['active' => 1]);
        $request = $this->Request();

        $infos = [];

        if ($request->isPost()) {
            $failed = [];
            $sent = [];

            $countries = array_map(function ($c) {
                return (int)$c;
            }, $request->getParam('countries'));

            $from = $request->getParam('from');
            if (!mb_strlen($from)) {
                $from = $pluginConfig['sms77from'];
                $from = mb_strlen($from) ? $from : 'sms77io';
            }

            $getCustomers = function () use ($request, $countries, $modelManager) {
                $customerGroups = $request->getParam('customerGroups');

                $customerRepo = $modelManager->getRepository('Shopware\Models\Customer\Customer');

                $findOpts = ['active' => 1];

                if (count($customerGroups)) {
                    $findOpts = array_merge($findOpts, ['groupKey' => $customerGroups]);
                }

                return $customerRepo->findBy($findOpts);
            };

            $client = new Client($apiKey, 'shopware');

            foreach ($getCustomers() as $customer) {
                $defaultShippingAddress = $customer->getDefaultShippingAddress();
                $defaultBillingAddress = $customer->getDefaultBillingAddress();

                $defaultAddress = is_null($defaultShippingAddress) ? $defaultBillingAddress : $defaultShippingAddress;

                if (count($countries)) {
                    if (!in_array($defaultAddress->getCountry()->getId(), $countries)) {
                        continue;
                    }
                }

                $defaultShippingPhone = $defaultShippingAddress->getPhone();

                $phone =
                    $defaultShippingPhone
                        ? $defaultShippingPhone
                        : $defaultBillingAddress->getPhone();

                if (!$phone) {
                    continue;
                }

                $text = $request->getParam('text');
                $signature = $pluginConfig['sms77signature'];
                if (mb_strlen($signature)) {
                    $signaturePosition = $pluginConfig['sms77signaturePosition'];

                    $text = 'prepend' === $signaturePosition
                        ? $signature . $text
                        : $text . $signature;
                }

                try {
                    $extras = array_merge(compact( 'from'), ['json' => true]);

                    $res = $client->sms($phone, $text, $extras);
                    $res = (array)json_decode($res);

                    $sent[] = $res;

                    if ('100' !== $res['success']) {
                        $failed[] = $res;
                    }
                } catch (Exception $exception) {
                    $failed[] = $exception->getMessage();
                }
            }

            if (!count($failed) && !count($sent)) {
                $infos[] = 'No customers found with for given configuration.';
            }

            $this->View()->assign(compact('failed', 'sent'));
        }

        $this->View()->assign([
            'customerGroups' => array_map(function ($cGroup) {
                return [
                    'label' => $cGroup->getName(),
                    'id' => $cGroup->getKey(),
                ];
            }, $allCustomerGroups),
            'countries' => array_map(function ($c) {
                return [
                    'label' => ucfirst(strtolower($c->getIsoName())),
                    'id' => $c->getId(),
                ];
            }, $activeCountries),
            'infos' => $infos,
        ]);
    }

    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }
}