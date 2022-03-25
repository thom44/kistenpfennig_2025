<?php

namespace Drupal\custom_workflow\EventSubscriber;

use Drupal\commerce\MailHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\wlw_contract\ContractFilesInterface;
use Drupal\wlw_order_mail_log\MailLoggerInterface;
use Drupal\wlw_order_token\OrderTokenProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends a receipt email when an order is placed.
 */
class CourseOrderReceiptSubscriber implements EventSubscriberInterface {

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
   * The MailLogger service.
   *
   * @var \Drupal\wlw_order_mail_log\MailLoggerInterface
   */
  protected $mailLogger;

  /**
   * The ContractFiles service
   */
  protected $contractFiles;

  /**
   * DefaultOrderReceiptSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\wlw_order_token\OrderTokenProvider $order_token_provider
   * @param \Drupal\commerce\MailHandlerInterface $mail_handler
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    OrderTokenProvider $order_token_provider,
    MailHandlerInterface $mail_handler,
    Messenger $messenger,
    MailLoggerInterface $mail_logger,
    ContractFilesInterface $contract_files) {
    $this->entityTypeManager = $entity_type_manager;
    $this->orderTokenProvider = $order_token_provider;
    $this->mailHandler = $mail_handler;
    $this->messenger = $messenger;
    $this->mailLogger = $mail_logger;
    $this->contractFiles = $contract_files;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.place.post_transition' => ['sendOrderReceipt', -100]];

    return $events;
  }

  /**
   * Sends an order receipt email.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function sendOrderReceipt(WorkflowTransitionEvent $event) {

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    // Process only course orders.
    if ($order->bundle() != 'course') {
      return;
    }

    // Gets order checkbox value for not sending email to customer.
    $block_email = $order->get('field_block_email')[0]->getValue()['value'];

    // Gets order type for order type configurations.
    $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());

    // Checks if email should be send.
    if ($order_type->shouldSendReceipt() && $block_email != 1) {
      $customer = $order->getCustomer();

      // Retrieves the configurable email text.
      $config = $this->orderTokenProvider->getEmailConfigTokenReplaced('wlw_mail_ui.commerce_order_course_recipient', $order, $customer);
      /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */

      $bcc = $config['bcc'] ? $config['bcc'] : $order_type->getReceiptBcc();

      $from = $config['from'] ? $config['from'] : $order->getStore()->getEmail();

      // Collects the contracts of the courses in the order.
      $contracts = $this->contractFiles->collectContractsFromOrder($order);
      // Gets the prepared file std class of the contracts.
      $files = array_column($contracts, 'file_std_class');

      $body = [
        '#theme' => 'wlw_mail_ui_email_wrapper',
        '#message' => $config['body'],
      ];

      $params = [
        'id' => 'wlw_order_course_receipt',
        'from' => $from,
        'bcc' => $bcc,
        'order' => $order,
        'files' => $files,
      ];

      $to = $order->getEmail();

      $mail = $this->mailHandler->sendMail($to, $config['subject'], $body, $params);
      if ($mail) {
        $this->messenger->addStatus($this->t('The course order receipt email was send to the user.'));

        // Saves order mail log.
        $this->mailLogger->saveLogEntry([
          'order_id' => $order->id(),
          'email_to' => $to,
          'subject' => $config['subject'],
          'email_key' => $params['id'],
          'email_from' => $from,
          'email_bcc' => $bcc,
        ]);
      } else {
        $this->messenger->addError($this->t('The course order receipt email could not be send to the user.'));
      }
    }
  }
}
