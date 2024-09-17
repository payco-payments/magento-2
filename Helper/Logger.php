<?php
declare(strict_types=1);

namespace Payco\Payments\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;

class Logger extends AbstractHelper
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        Context $context
    )
    {
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @param string $message
     * @param array $array
     * @return void
     */
    public function debug($message, $array = [])
    {
        $this->logger->debug($message, $array);
    }

    /**
     * @param $message
     * @param array $array
     * @return void
     */
    public function error($message, $array = [])
    {
        $this->logger->error($message, $array);
    }
}
