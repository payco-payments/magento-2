<?php
declare(strict_types=1);

namespace Payco\Payments\Api;

interface SimulateInterface
{
    /**
     * Simulate interest
     *
     * @param float $amount
     * @return array
     */
    public function execute($amount);
}
