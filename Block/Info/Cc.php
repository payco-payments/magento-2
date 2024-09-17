<?php

declare(strict_types=1);

namespace Payco\Payments\Block\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Block\Info;

class Cc extends Info
{
    /**
     * @var string
     */
    protected $_template = 'Payco_Payments::info/cc.phtml';

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        Template\Context $context,
        array $data = []
    )
    {
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function getOrder()
    {
        return $this->getInfo()->getOrder();
    }
}
