<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model\Quote\Total;

use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Payco\Payments\Api\InterestInterface;

class Interest extends AbstractTotal
{
    /**
     * Collect grand total address amount
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     */
    protected $quoteValidator = null;

    public function __construct(\Magento\Quote\Model\QuoteValidator $quoteValidator)
    {
        $this->quoteValidator = $quoteValidator;
    }

    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        parent::collect($quote, $shippingAssignment, $total);

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        $currentMethod = $quote->getPayment()->getMethod();
        if ($currentMethod !== "payco_payments_credit_card") {
            $this->clearValues($quote, $total);
            return $this;
        }

        $interest = $quote->getPaycoInterestAmount();
        $baseInterest = $quote->getBasePaycoInterestAmount();

        $total->setPaycoInterestAmount($interest);
        $total->setBasePaycoInterestAmount($baseInterest);

        $total->setTotalAmount(InterestInterface::INTEREST_AMOUNT, $interest);
        $total->setBaseTotalAmount(InterestInterface::INTEREST_BASE_AMOUNT, $baseInterest);

        $total->setGrandTotal($total->getGrandTotal());
        $total->setBaseGrandTotal($total->getBaseGrandTotal());

        return $this;
    }

    protected function clearValues($quote, $total)
    {
        $total->setTotalAmount('subtotal', 0);
        $total->setBaseTotalAmount('subtotal', 0);
        $total->setTotalAmount('tax', 0);
        $total->setBaseTotalAmount('tax', 0);
        $total->setTotalAmount('discount_tax_compensation', 0);
        $total->setBaseTotalAmount('discount_tax_compensation', 0);
        $total->setTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setSubtotalInclTax(0);
        $total->setBaseSubtotalInclTax(0);

        $interest = 0;
        $total->setTotalAmount(InterestInterface::INTEREST_AMOUNT, $interest);
        $total->setBaseTotalAmount(InterestInterface::INTEREST_BASE_AMOUNT, $interest);
        $quote->setData(InterestInterface::INTEREST_AMOUNT, $interest);
        $quote->setData(InterestInterface::INTEREST_BASE_AMOUNT, $interest);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param Address\Total $total
     * @return array|null
     */
    /**
     * Assign subtotal amount and label to address object
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param Address\Total $total
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        $interest = $quote->getPaycoInterestAmount();

        if ($interest) {
            $result = [
                'code' => $this->getCode(),
                'title' => $this->getLabel(),
                'value' => $interest,
            ];
        }

        return $result;
    }

    /**
     * Get Subtotal label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Additional');
    }
}
