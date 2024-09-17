<?php
declare(strict_types=1);

namespace Payco\Payments\Api;

interface RequestInterface
{
    /**
     * @param string $endpoint
     * @param string | array $payload
     * @param string $method
     * @param bool $isOpenRequest
     *
     * @return DataObject
     * @throws LocalizedException
     */
    public function request(string $endpoint, $payload, string $method = 'POST', bool $isOpenRequest = false);
}
