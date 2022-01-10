<?php

namespace Drupal\wlw_invoice;

use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\entity_print\Plugin\PrintEngineInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Handles data preparation for PDF invoices.
 */
interface InvoicePrintPreparerInterface {

  /**
   * Renders the invoice as a printed document and save to disk.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice
   *   The invoice.
   *
   * @return array
   *   The render array of invoice data.
   */
  public function preparePrintable(InvoiceInterface $invoice);

}
