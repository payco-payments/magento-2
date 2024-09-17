<?php

namespace Payco\Payments\Api;

interface GuestInterestInterface
{
  /**
   * Interest
   *
   * @param string $cartId
   * @param string $brand
   * @param string $selectedInstallment
   * 
   * @return array
   */
  public function execute($cartId, $brand, $selectedInstallment);
}
