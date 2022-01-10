<?php

namespace Drupal\wlw_contract;

use Drupal\commerce_order\Entity\OrderInterface;

interface ContractFilesInterface {

  /**
   * Collects all contract files of the course products of an order.
   *   Returns no double contracts if two products have the same contract.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @return mixed
   *   Array with:
   *   - The title string
   *   - The file object
   *   - The file stdClass for mail attachemant.
   */
  public function collectContractsFromOrder(OrderInterface $order);
}