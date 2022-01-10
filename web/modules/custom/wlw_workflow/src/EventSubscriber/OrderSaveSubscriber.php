<?php

namespace Drupal\wlw_workflow\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends a receipt email when an order is placed.
 */
class OrderSaveSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new OrderReceiptSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_order\Mail\OrderReceiptMailInterface $order_receipt_mail
   *   The mail handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.commerce_order.update' => ['onSaveOrder', -100]];

    return $events;
  }

  /**
   * Resets field_block_email to unchecked.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The event we subscribed to.
   */
  public function onSaveOrder(OrderEvent $event) {
    $order = $event->getOrder();
    // Save order in this context not possible because:
    // Error: Maximum function nesting level of '256' reached, aborting
    // Same in update orderEvents. Order presave event is to early for
    // using the value in DefaultOrderReceiptSubscriber.
    //$order->set('field_block_email',['value' => 0]);
    //$order->save();
    // @todo: Use entity api after bug is fixed.
    $order_id = $order->get('order_id')[0]->getValue()['value'];
    $connection = $this->database;
    $connection->update('commerce_order__field_block_email')
      ->fields([
        'field_block_email_value' => 0,
      ])
      ->condition('entity_id', $order_id)
      ->execute();
  }
}
