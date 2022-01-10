<?php

namespace Drupal\wlw_invoice;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Generates Invoice and PDFs for email attachment.
 */
interface InvoiceFileAttachmentInterface {

  /**
   * Creates pdf-invoice file object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return \Drupal\file\Entity\File
   *   The pdf invoice file object.
   */
  public function getInvoiceFile(OrderInterface $order);

  /**
   * Generates the invoice if not exists.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return int
   *   The invoice id.
   */
  public function generateInvoice(OrderInterface $order);

  /**
   * Checks if the order has already a invoice.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return FALSE|int
   *   FALSE when the order has no invoice|The invoice id
   *   when a invoice exists.
   */
  public function orderHasInvoice(OrderInterface $order);

  /**
   * Creates pdf-credit file object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return \Drupal\file\Entity\File
   *   The pdf invoice file object.
   */
  public function getCreditFile(OrderInterface $order);

}