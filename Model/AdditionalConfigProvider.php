<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Payco\Payments\Model\Method\CreditCard;
use Payco\Payments\Model\Request;
use Payco\Payments\Model\Config\Config;

class AdditionalConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Payco\Payments\Model\Request
     */
    protected $request;
    /**
     * @var Config
     */
    protected $config;

    public function __construct(
        Request $request,
        Config $config
    )
    {
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        return [
            'payment' => array(
                CreditCard::CODE => [
                    'max_installments' => (float)$this->config->getMaxInstallments(),
                    'min_amount_installment' => (float)$this->config->getMinAmountInstallment(),
                    'apply_interest' => $this->config->applyInterest()
                ]
            ),
            'payco_payments' => [
                'public_key' => $this->getPublicKey()['public_key'],
                'key_id' => $this->getPublicKey()['key_id'],
                'showCpfWithPayment' => (boolean)$this->config->showCpfWithPayment()
            ]
        ];
    }

    /**
     * @return DataObject
     * @throws LocalizedException
     */
    private function getPublicKey()
    {
        return $this->request->request('/cryptography', [], 'GET');
    }
}
