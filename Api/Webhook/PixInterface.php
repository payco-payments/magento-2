<?php

declare(strict_types=1);

namespace Payco\Payments\Api\Webhook;

interface PixInterface
{
    /**
     * Execute webhook
     *
     * @return mixed
     */
    public function execute();
}
