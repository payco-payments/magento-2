<?php
declare(strict_types=1);

namespace Payco\Payments\Api;

interface InterestInterface
{
  const INTEREST_AMOUNT = 'payco_interest_amount';
  const INTEREST_BASE_AMOUNT = 'base_payco_interest_amount';

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
