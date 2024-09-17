<?php
/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

declare(strict_types=1);

namespace Payco\Payments\Model\Adminhtml\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PixExpiration implements ArrayInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            '15' => __('15 minutes'),
            '30' => __('30 minutes'),
            '60' => __('1 hour'),
            '720' => __('12 hours'),
            '1440' => __('24 hours'),
        ];
    }
}
