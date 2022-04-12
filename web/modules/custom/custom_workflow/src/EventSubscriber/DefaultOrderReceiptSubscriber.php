<?php

namespace Drupal\custom_workflow\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\custom_mail_ui\MailHelperInterface;
use Drupal\custom_order_token\OrderTokenProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends a receipt email when an order is placed.
 */
class DefaultOrderReceiptSubscriber implements EventSubscriberInterface {

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
   * @var \Drupal\custom_order_token\OrderTokenProvider $orderTokenProvider
   */
  protected $orderTokenProvider;

  /**
   * The mail handler.
   *
   * @var \Drupal\commerce\mailHelperInterface
   */
  protected $mailHelper;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger $messenger;
   */
  protected $messenger;

  /**
   * DefaultOrderReceiptSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\custom_order_token\OrderTokenProvider $order_token_provider
   * @param \Drupal\custom_mail_ui\mailHelperInterface $mail_handler
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    OrderTokenProvider $order_token_provider,
    MailHelperInterface $mail_helper,
    Messenger $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->orderTokenProvider = $order_token_provider;
    $this->mailHelper = $mail_helper;
    $this->messenger = $messenger;
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

    // Process only default orders.
    if ($order->bundle() != 'default') {
      return;
    }

    // Gets order checkbox value for not sending email to customer.
    //$block_email = $order->get('field_block_email')[0]->getValue()['value'];

    // Gets order type for order type configurations.
    $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());

    // Checks if email should be send.
    if ($order_type->shouldSendReceipt() && $block_email != 1) {
      $customer = $order->getCustomer();

      // Retrieves the configurable email text.
      $config = $this->orderTokenProvider->getEmailConfigTokenReplaced('custom_mail_ui.commerce_order_recipient', $order, $customer);
      /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */

      $bcc = $config['bcc'] ? $config['bcc'] : $order_type->getReceiptBcc();

      $from = $config['from'] ? $config['from'] : $order->getStore()->getEmail();

      $params = [
        'id' => 'custom_order_default_receipt',
        'from' => $from,
        'bcc' => $bcc,
        'order' => $order,
        'subject' => $config['subject'],
        'body' => $config['body'],
      ];

      $to = $order->getEmail();

      $mail = $this->mailHelper->sendMail('custom_workflow', 'custom_order_default_receipt', $to, 'de', $params);

      if ($mail) {
        $this->messenger->addStatus($this->t('The order receipt email was send to the user.'));
      } else {
        $this->messenger->addError($this->t('The order receipt email could not be send to the user.'));
      }
    }
  }
}
