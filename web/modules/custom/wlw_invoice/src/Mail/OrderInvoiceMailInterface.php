<?php

namespace Drupal\wlw_invoice\Mail;


use Drupal\commerce_order\Entity\OrderInterface;

interface OrderInvoiceMailInterface {

  /**
   * Sends order invoice email.
   *   This method is usable in other locations.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function send(OrderInterface $order);

}