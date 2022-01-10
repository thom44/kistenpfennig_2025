<?php

namespace Drupal\wlw_invoice\Mail;

use Drupal\commerce\MailHandlerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\wlw_order_mail_log\MailLoggerInterface;
use Drupal\wlw_order_token\OrderTokenProvider;
use Drupal\wlw_invoice\InvoiceFileAttachmentInterface;

/**
 * Sends a receipt email when an order is placed.
 */
class OrderInvoiceMail implements OrderInvoiceMailInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The order token provider service.
   *
   * @var \Drupal\wlw_order_token\OrderTokenProvider $orderTokenProvider
   */
  protected $orderTokenProvider;

  /**
   * The mail handler.
   *
   * @var \Drupal\commerce\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger $messenger;
   */
  protected $messenger;


  /**
   * The invoice_file_attachment service.
   *
   * @var \Drupal\wlw_invoice\InvoiceFileAttachmentInterface
   */
  protected $invoiceFileAttachment;

  /**
   * The MailLogger service.
   *
   * @var \Drupal\wlw_order_mail_log\MailLoggerInterface
   */
  protected $mailLogger;

  /**
   * DefaultOrderInvoiceSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\wlw_order_token\OrderTokenProvider $order_token_provider
   * @param \Drupal\commerce\MailHandlerInterface $mail_handler
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\wlw_invoice\InvoiceFileAttachmentInterface $invoice_file_attachment
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    OrderTokenProvider $order_token_provider,
    MailHandlerInterface $mail_handler,
    Messenger $messenger,
    InvoiceFileAttachmentInterface $invoice_file_attachment,
    MailLoggerInterface $mail_logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->orderTokenProvider = $order_token_provider;
    $this->mailHandler = $mail_handler;
    $this->messenger = $messenger;
    $this->invoiceFileAttachment = $invoice_file_attachment;
    $this->mailLogger = $mail_logger;
  }

  /**
   * {@inheritDoc}
   */
  public function send(OrderInterface $order) {

    // On courses we skip invoice generation.
    $type = $order->get('type')->getValue()[0]['target_id'];
    if ($type == 'course') {
      return;
    }

    // Gets order checkbox value for not sending email to customer.
    $block_email = $order->get('field_block_email')[0]->getValue()['value'];

    // Gets order type for order type configurations.
    $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());

    // Generates a invoice if not exists.
    $this->invoiceFileAttachment->generateInvoice($order);

    // Checks if email should be send.
    if ($order_type->shouldSendReceipt() && $block_email != 1) {

      $customer = $order->getCustomer();

      // Retrieves the configurable email text.
      $config = $this->orderTokenProvider->getEmailConfigTokenReplaced('wlw_mail_ui.order_invoice', $order, $customer);

      $bcc = $config['bcc'] ? $config['bcc'] : $order_type->getReceiptBcc();

      $from = $config['from'] ? $config['from'] : $order->getStore()->getEmail();

      // Saves the invoice as pdf and returns file object.
      $file = $this->invoiceFileAttachment->getInvoiceFile($order);

      $body = [
        '#theme' => 'wlw_mail_ui_email_wrapper',
        '#message' => $config['body'],
      ];

      $params = [
        'id' => 'wlw_order_invoice',
        'from' => $from,
        'bcc' => $bcc,
        'order' => $order,
        'files' => [$file]
      ];

      $to = $order->getEmail();

      $mail = $this->mailHandler->sendMail($to, $config['subject'], $body, $params);
      if ($mail) {
        $this->messenger->addStatus($this->t('The order invoice email was send to the user.'));

        // Saves order mail log.
        $this->mailLogger->saveLogEntry([
          'order_id' => $order->id(),
          'email_to' => $to,
          'subject' => $config['subject'],
          'email_key' => $params['id'],
          'email_from' => $from,
          'email_bcc' => $bcc,
        ]);
      }
      else {
        $this->messenger->addError($this->t('The order invoice email could not be send to the user.'));
      }
    }
  }
}
