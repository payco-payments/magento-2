<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model\Api;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Payco\Payments\Api\InterestInterface;
use Payco\Payments\Api\RequestInterface;

class Interest implements InterestInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartTotalRepositoryInterface $cartTotalRepository,
        RequestInterface $request
    )
    {
        $this->cartRepository = $cartRepository;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function execute($cartId, $brand, $selectedInstallment)
    {
        $quote = $this->cartRepository->getActive($cartId);

        if (!$quote) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__('No quote found for given ID.'));
        }
        $quoteTotal = $this->cartTotalRepository->get($cartId);
        $grandTotal = $quoteTotal->getGrandTotal();
        $grandTotal -= $quote->getData(InterestInterface::INTEREST_AMOUNT);

        // Calculate the interest based on the selected installment and amount
        $calculatedInterest = $this->calculateInterest($grandTotal, $brand, $selectedInstallment);
        $interestAmount = $calculatedInterest['final_amount'] - $grandTotal;

        $quote->setData(InterestInterface::INTEREST_AMOUNT, $interestAmount);
        $quote->setData(InterestInterface::INTEREST_BASE_AMOUNT, $interestAmount);
        $this->cartRepository->save($quote);

        return [
            [
                "addition_amount" => $interestAmount
            ]
        ];
    }

    /**
     * Calculate interest based on the provided amount, brand, and selected installment.
     *
     * @param float $amount description
     * @param string $brand description
     * @param int $selectedInstallment description
     * @return array
     */
    private function calculateInterest($amount, $brand, $selectedInstallment)
    {
        $response = $this->request->request('interest', [
            "amount" => $amount,
            "installments" => $selectedInstallment,
            "brand" => $brand
        ], "POST", true);

        if (!$response['final_amount'] || $response['final_amount'] === 0) {
            return 0;
        }

        return $response->getData();
    }
}
