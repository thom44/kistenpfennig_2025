<?php

namespace Drupal\custom_workflow\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\wlw_invoice\Mail\OrderInvoiceMailInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends a invoice email when an order is placed.
 */
class DefaultOrderInvoiceSubscriber implements EventSubscriberInterface {

  /**
   * The order invoice mail service.
   *
   * @var \Drupal\wlw_invoice\Mail\OrderInvoiceMailInterface $orderInvoiceMail
   */
  protected $orderInvoiceMail;

  /**
   * DefaultOrderInvoiceSubscriber constructor.
   * @param \Drupal\wlw_workflow\EventSubscriber\OrderInvoiceMail $order_invoice_mail
   */
  public function __construct(OrderInvoiceMailInterface $order_invoice_mail) {
    $this->orderInvoiceMail = $order_invoice_mail;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.validate.post_transition' => ['sendOrderInvoice', -100]];

    return $events;
  }

  /**
   * Sends an order invoice email.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function sendOrderInvoice(WorkflowTransitionEvent $event) {

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    $this->orderInvoiceMail->send($order);
  }
}
