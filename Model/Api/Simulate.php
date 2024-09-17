<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model\Api;

class Simulate implements \Payco\Payments\Api\SimulateInterface
{
    /** @var \Payco\Payments\Api\RequestInterface */
    protected $request;

    /** @var \Magento\Framework\Pricing\Helper\Data */
    protected $pricingHelper;

    public function __construct(
        \Payco\Payments\Api\RequestInterface $request,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper
    )
    {
        $this->request = $request;
        $this->pricingHelper = $pricingHelper;
    }

    /** @inheritDoc */
    public function execute($amount)
    {
        $response = $this->request->request(
            'interest/simulation',
            [
                'amount' => $amount
            ],
            'POST',
            true
        );
        $installments = [];
        foreach ($response->getData('simulation') as $key => $installment) {
            $formattedValue = $this->pricingHelper->currency($installment['valor_parcela'], true, false); // Format the price
            $installments[] = [
                "value" => $installment['parcelas'],
                "label" => __('%1x of %2 (with interest)', $installment['parcelas'], $formattedValue)
            ];
        }
        return $installments;
    }
}
