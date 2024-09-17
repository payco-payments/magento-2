<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model\Api;

use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Payco\Payments\Api\GuestInterestInterface;
use Payco\Payments\Api\InterestInterface;

class GuestInterest implements GuestInterestInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMask;

    /**
     * @var InterestInterface
     */
    protected $interest;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        InterestInterface $interest,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    )
    {
        $this->quoteIdMask = $quoteIdMaskFactory;
        $this->interest = $interest;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }

    /**
     * @inheritDoc
     */
    public function execute($cartId, $brand, $selectedInstallment)
    {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        return $this->interest->execute($cartId, $brand, $selectedInstallment);
    }
}
