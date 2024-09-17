<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Status\Collection as StatusFactory;
use Magento\Store\Model\ScopeInterface;

class Config {
    const XML_PATH_PAYCO_SHOW_CPF = 'payment/payco_payments/showCpf';
    const XML_PATH_PAYCO_COMPANY_NAME = 'payment/payco_payments/company_name';
    const XML_PATH_PAYCO_CLIENT_ID = 'payment/payco_payments/client_id';
    const XML_PATH_PAYCO_CLIENT_SECRET = 'payment/payco_payments/client_secret';
    const XML_PATH_PAYCO_PIX_EXPIRATION = 'payment/payco_payments_pix/expiration';
    const XML_PATH_PAYCO_STATUS_PAYMENT_APPROVED = 'payment/payco_payments/order_status_approved';
    const XML_PATH_PAYCO_STATUS_PAYMENT_PENDING = 'payment/payco_payments/order_status_pending';
    const XML_PATH_PAYCO_STATUS_PAYMENT_REJECTED = 'payment/payco_payments/order_status_rejected';
    const XML_PATH_PAYCO_STATUS_PAYMENT_CANCELLED = 'payment/payco_payments/order_status_cancelled';
    const XML_PATH_PAYCO_CC_PAYMENT_TYPE = 'payment/payco_payments_credit_card/type_payment_action';
    const XML_PATH_PAYCO_CC_APPLY_INTEREST = 'payment/payco_payments_credit_card/apply_interest';
    const XML_PATH_PAYCO_CC_MAX_INSTALLMENTS = 'payment/payco_payments_credit_card/max_installments';
    const XML_PATH_PAYCO_CC_MIN_AMOUNT_INSTALLMENTS = 'payment/payco_payments_credit_card/min_amount_installments';

    /**
     * @var StatusFactory
     */
    protected $statusFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        StatusFactory $statusFactory,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->statusFactory = $statusFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->getValues(self::XML_PATH_PAYCO_COMPANY_NAME);
    }

    /**
     * @return array
     */
    public function getCredentials()
    {
        $clientId = $this->getValues(self::XML_PATH_PAYCO_CLIENT_ID, ScopeInterface::SCOPE_WEBSITE);
        $clientSecret = $this->getValues(self::XML_PATH_PAYCO_CLIENT_SECRET, ScopeInterface::SCOPE_WEBSITE);

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ];
    }

    /**
     * @return string
     */
    public function getPixExpiration()
    {
        $pixExpiration = $this->getValues(self::XML_PATH_PAYCO_PIX_EXPIRATION);

        return $pixExpiration ?? '30';
    }

    /**
     * @return string
     */
    public function getOrderStatusPaymentApproved()
    {
        $status = $this->getValues(self::XML_PATH_PAYCO_STATUS_PAYMENT_APPROVED);
        return $status ?? 'processing';
    }

    /**
     * @return string
     */
    public function getOrderStatusPaymentPending()
    {
        $status = $this->getValues(self::XML_PATH_PAYCO_STATUS_PAYMENT_PENDING);
        return $status ?? 'pending';
    }

    /**
     * @return string
     */
    public function getOrderStatusPaymentRejected()
    {
        $status = $this->getValues(self::XML_PATH_PAYCO_STATUS_PAYMENT_REJECTED);
        return $status ?? 'canceled';
    }

    /**
     * @return string
     */
    public function getOrderStatusPaymentCanceled()
    {
        $status = $this->getValues(self::XML_PATH_PAYCO_STATUS_PAYMENT_CANCELLED);
        return $status ?? 'canceled';
    }

    /**
     * @return string
     */
    public function getPaymentType()
    {
        return $this->getValues(self::XML_PATH_PAYCO_CC_PAYMENT_TYPE);
    }

    /**
     * @return bool
     */
    public function applyInterest()
    {
        return (bool) $this->getValues(self::XML_PATH_PAYCO_CC_APPLY_INTEREST);
    }

    /**
     * @return mixed
     */
    public function getMaxInstallments()
    {
        return $this->getValues(self::XML_PATH_PAYCO_CC_MAX_INSTALLMENTS);
    }

    /**
     * @return mixed
     */
    public function getMinAmountInstallment()
    {
        return $this->getValues(self::XML_PATH_PAYCO_CC_MIN_AMOUNT_INSTALLMENTS);
    }

    /**
     * @return bool
     */
    public function showCpfWithPayment()
    {
        return $this->getValues(self::XML_PATH_PAYCO_SHOW_CPF);
    }

    /**
     * @param $status
     * @return mixed
     */
    public function _getAssignedState($status)
    {
        $collection = $this->statusFactory->joinStates()->addFieldToFilter('main_table.status', $status);
        $collectionItems = $collection->getItems();
        return array_pop($collectionItems)->getState();
    }

    public function getValues($path)
    {
        return $this->scopeConfig->getValue($path);
    }
}
