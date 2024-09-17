<?php

declare(strict_types=1);

namespace Payco\Payments\Block\Checkout;

use Magento\Checkout\Block\Onepage\Success as SuccessBlock;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;

class Success extends SuccessBlock
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        OrderConfig $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Sales\Model\Order $orderFactory,
        \Payco\Payments\Model\Config\Config $config,
        array $data = []
    )
    {
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
        $this->_orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->_orderFactory
            ->loadByIncrementId($this->checkoutSession->getLastRealOrderId());
    }

    /**
     * @return mixed
     */
    public function getPaycoPixId()
    {
        $order = $this->getOrder();
        return $order->getPaycoPixId();
    }

    public function getCompanyName()
    {
        return $this->config->getCompanyName();
    }
}
