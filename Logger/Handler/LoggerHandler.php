<?php

declare(strict_types=1);

namespace Payco\Payments\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use \Monolog\Logger;

class LoggerHandler extends Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/paycopayments.log';
}
