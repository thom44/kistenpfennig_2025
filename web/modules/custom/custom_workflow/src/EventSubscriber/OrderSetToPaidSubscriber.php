<?php

namespace Drupal\custom_workflow\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderSetToPaidSubscriber implements EventSubscriberInterface {

  /**
   * The payments to process.
   * @note: For debugging add 'example_offsite_payment' (not working)
   */
  public $paymentsToProcess = [
    'paypal'
  ];

  /**
   * The request stack service.
   *
   * $var Symfony\Component\HttpFoundation\RequestStack;
   */
  protected $requestStack;

  /**
   * OrderCompleteSubscriber constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The event fired after saving a new order.
    $events = ['commerce_order.commerce_order.update' => ['setStateIfPaid', -100]];

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function setStateIfPaid(OrderEvent $event) {

    $request = $this->requestStack->getCurrentRequest();

    // Skip this process, if order update was triggered from order edit form.
    // We want to change the status automatically only when off-site payment
    // is triggert from checkout process. Maybe there is a cleaner solution.
    if ($request->get('form_id') == 'commerce_order_default_edit_form') {
      return;
    }

    $order = $event->getOrder();

    if ($order->getState()->getId() == 'validation') {

      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
      $payment_gateway = $order->get('payment_gateway')->entity;
      if (!$payment_gateway) {
        // The payment gateway is unknown.
        return;
      }

      if (!in_array($payment_gateway->id(), $this->paymentsToProcess)) {
        // The payment gateway is not in the list.
        return;
      }

      // @todo: Test if with paypal the order isPaid() is true.
      if ($order->isPaid()) {
        $order->getState()->applyTransitionById('validate');

        // Saves the order with the new state.
        $order->save();
      }
    }
  }
}
