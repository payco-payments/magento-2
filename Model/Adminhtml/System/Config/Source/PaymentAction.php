<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model\Adminhtml\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentAction implements ArrayInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            'ac' => __('Autoriza e captura'),
            'pa' => __('Pré autorizado')
        ];
    }
}
