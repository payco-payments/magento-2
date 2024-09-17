<?php

declare(strict_types=1);

namespace Payco\Payments\Block;

use Magento\Framework\View\Element\Template;

class Form extends \Magento\Payment\Block\Form
{
    public function __construct(Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }
}
