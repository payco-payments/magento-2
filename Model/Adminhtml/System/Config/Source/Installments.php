<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model\Adminhtml\System\Config\Source;

class Installments implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return array_map(
            function ($i) {
                return [
                    'value' => $i,
                    'label' => sprintf('%dx', $i)
                ];
            },
            range(1, 12)
        );
    }
}
