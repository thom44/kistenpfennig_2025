<?php

namespace Drupal\wlw_invoice;

use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\entity_print\Plugin\PrintEngineInterface;

/**
 * Handles generating PDFS for invoices and credits.
 */
interface InvoicePrintBuilderInterface {

  /**
   * Renders the invoice as a printed document and save to disk.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice
   *   The invoice.
   * @param \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine
   *   The print engine plugin to use.
   * @param array $params
   *   Array with keys
   *   - 'filename' string The filename.
   *   - 'uri' string The drupal schema path.
   *   - 'pdf_output_type' string invoice|credit
   *     Differs the pdf data preparation in preprocess function.
   *
   * @return \Drupal\file\FileInterface|null
   *   The invoice PDF file, FALSE it could not be created.
   */
  public function savePrintable(InvoiceInterface $invoice, PrintEngineInterface $print_engine, $params);

  /**
   * Prepares filename and uri for to create invoice with savePrintable.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice
   *   The invoice.
   * @param \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine
   *   The print engine plugin to use.
   * @param string $scheme
   *   (optional) The Drupal scheme, defaults to 'private'.
   *
   * @return \Drupal\file\FileInterface|null
   *   The invoice PDF file, FALSE it could not be created.
   */
  public function savePrintableInvoice(InvoiceInterface $invoice, PrintEngineInterface $print_engine, $scheme = 'private');

  /**
   * Prepares filename and uri for to create credit with savePrintable.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice
   *   The invoice.
   * @param \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine
   *   The print engine plugin to use.
   * @param string $scheme
   *   (optional) The Drupal scheme, defaults to 'private'.
   *
   * @return \Drupal\file\FileInterface|null
   *   The invoice PDF file, FALSE it could not be created.
   */
  public function savePrintableCredit(InvoiceInterface $invoice, PrintEngineInterface $print_engine, $scheme = 'private');

}
