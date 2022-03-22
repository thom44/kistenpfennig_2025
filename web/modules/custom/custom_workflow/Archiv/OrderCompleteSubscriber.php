<?php

namespace Drupal\custom_workflow\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderCompleteSubscriber implements EventSubscriberInterface {

  /**
   * The payments to process.
   * @note: For debugging add 'example_offsite_redirect'
   */
  public $paymentsToProcess = [
    'paypal_default',
    'sofort_banking',
    'example_offsite_redirect'
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
   * Validates the order after it is full paid.
   *   The order has to be saved first, that the place transition is fully
   *   completed. Thats why we can only validate the order after the order
   *   entity is saved.
   *   Problems with other events:
   *   - We can not use commerce_order.order.paid event, because it breaks the
   *     process and orders stay as cart.
   *   - We can not use commerce_order.place.post_transition event, because it
   *     doesn't work.
   *   - We can not use commerce_order.commerce_order.presave event, becaus the
   *     order will not completly places and the EventSubscribers for place
   *     transition not triggered.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
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

      if ($order->isPaid()) {
        $order->getState()->applyTransitionById('validate');

        // Saves the order with the new state.
        $order->save();
      }
    }
  }
}
