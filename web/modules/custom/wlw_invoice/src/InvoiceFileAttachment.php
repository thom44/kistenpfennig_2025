<?php

namespace Drupal\wlw_invoice;

use Drupal\commerce_invoice\InvoiceGeneratorInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;

class InvoiceFileAttachment implements InvoiceFileAttachmentInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The commerce_invoice_generator.
   *
   * @var Drupal\commerce_invoice\InvoiceGenerator;
   */
  protected $invoiceGenerator;

  /**
   * The wlw_invoice.print_builder.
   *
   * @var Drupal\wlw_invoice\InvoicePrintBuilder;
   */
  protected $printBuilder;

  /**
   * The Entity print plugin manager.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger $messenger;
   */
  protected $messenger;

  /**
   * OrderReceiptMail constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\commerce_invoice\InvoiceGeneratorInterface $invoice_generator
   * @param \Drupal\commerce_invoice\InvoicePrintBuilderInterface $print_builder
   * @param \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface $plugin_manager
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, InvoiceGeneratorInterface $invoice_generator, InvoicePrintBuilderInterface $print_builder, EntityPrintPluginManagerInterface $plugin_manager, Messenger $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->invoiceGenerator = $invoice_generator;
    $this->printBuilder = $print_builder;
    $this->pluginManager = $plugin_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoiceFile(OrderInterface $order) {

    $invoice_id = $this->generateInvoice($order);

    $invoice = $this->entityTypeManager->getStorage('commerce_invoice')->load($invoice_id);

    /** @var \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine */
    $print_engine = $this->pluginManager->createSelectedInstance('pdf');

    // Generates an saves the pdf-invoice as temporary file like in download method.
    // @see \Drupal\commerce_invoice\Controller\InvoiceController
    // We use our own printBuilder service to have control over file name
    // and file location.
    // Original Service:
    // @see \Drupal\commerce_invoice\InvoicePrintBuilder
    // Custom Service: \Drupal\wlw_invoice\InvoicePrintBuilder;
    $file = $this->printBuilder->savePrintableInvoice($invoice, $print_engine);

    return $file;
  }

  public function getCreditFile(OrderInterface $order) {

    $invoice_id = $this->orderHasInvoice($order);

    if ($invoice_id === FALSE) {
      $this->messenger->addStatus($this->t('This Order @oid must have first a invoice before you can create a credit.', [
        '@oid' => $order->id(),
      ]));
      return FALSE;

    } else {
      $invoice = $this->entityTypeManager->getStorage('commerce_invoice')->load($invoice_id);

      /** @var \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine */
      $print_engine = $this->pluginManager->createSelectedInstance('pdf');

      // Generates an saves the pdf-invoice as temporary file like in download method.
      // @see \Drupal\commerce_invoice\Controller\InvoiceController
      // We use our own printBuilder service to have control over file name
      // and file location.
      // Original Service:
      // @see \Drupal\commerce_invoice\InvoicePrintBuilder
      // Custom Service: \Drupal\wlw_invoice\InvoicePrintBuilder;
      $file = $this->printBuilder->savePrintableCredit($invoice, $print_engine);
    }


    return $file;
  }

  /**
   * {@inheritDoc}
   */
  public function generateInvoice(OrderInterface $order) {

    $invoice_id = $this->orderHasInvoice($order);

    // Generates a new invoice if not exists.
    if ($invoice_id === FALSE) {
      $store = $order->getStore();
      $profile = $order->getBillingProfile();
      // @var \Drupal\commerce_invoice\Entity\InvoiceInterface|null
      $invoice = $this->invoiceGenerator->generate([$order], $store, $profile);
      $invoice_id = $invoice->id();

      $invoice_no = $invoice->getInvoiceNumber();
      $this->messenger->addStatus($this->t('A new invoice with number @no was created.', [
        '@no' => $invoice_no,
      ]));
    }

    return $invoice_id;
  }

  /**
   * {@inheritDoc}
   */
  public function orderHasInvoice(OrderInterface $order) {

    // Checks if the invoice is already created.
    $invoice_storage = $this->entityTypeManager->getStorage('commerce_invoice');
    $invoice_ids = $invoice_storage->getQuery()
      ->condition('orders', [$order->id()], 'IN')
      ->accessCheck(FALSE)
      ->execute();

    // Returns false if no invoice exists.
    if (empty($invoice_ids)) {
      return FALSE;
    }

    return $invoice_id = reset($invoice_ids);
  }
}
