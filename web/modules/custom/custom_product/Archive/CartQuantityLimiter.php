<?php

namespace Drupal\custom_product\EventSubscriber;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Messenger\Messenger;

/**
 * Class CartQuantityLimiter
 * @package Drupal\custom_product\EventSubscriber
 */
class CartQuantityLimiter implements EventSubscriberInterface {

  use StringTranslationTrait;

  /*
   * The entity_type_manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager;
   */
  protected $entityTypeManager;

  /*
   * The commerce_cart_manager service.
   *
   * @var Drupal\commerce_cart\CartManagerInterface $commerceCartManager;
   */
  protected $commerceCartManager;

  /*
   * The messenger service.
   *
   * @var Drupal\Core\Messenger $messenger;
   */
  protected $messenger;

  /*
   * A list of product variation types that should be limited.
   *
   * @var array $variationsToLimit;
   */
  protected $variationsToLimit = [
    'default',
    'digital_plus_print',
    'combi_variation',
    'course_variation',
    'add_on'
  ];

  /*
   * The number of product variations which are allowed in cart.
   *
   * @var int $limit
   */
  protected $limit = 1;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CartManagerInterface $commerce_cart_manager, Messenger $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->commerceCartManager = $commerce_cart_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CartEvents::CART_ENTITY_ADD][] = array('limitQuantity');
    return $events;
  }

  /**
   * @param \Drupal\commerce_cart\Event\CartEntityAddEvent $event
   *
   * Limits the quantity of digital products to 1.
   */
  public function limitQuantity(CartEntityAddEvent $event) {

    $productAdded = TRUE;

    $variation = $event->getEntity();

    $type = $variation->get('type')->getValue();

    if (in_array($type[0]['target_id'],$this->variationsToLimit)) {

      $cart = $event->getCart();

      foreach ($cart->getItems() as $order_item) {
        if ($order_item->getQuantity() > 1) {
          // Limits the quantity to 1.
          $order_item->setQuantity($this->limit);
          // Creates a user message.
          $message = $this->t('This product can only @limit time purchased. It is already in the cart.', ['@limit' => $this->limit]);
          $this->messenger->addWarning($message);
          $productAdded = FALSE;
        }
      }
    }

    if ($productAdded === TRUE) {
      // The commerce_cart.cart_subscriber which displays the default add to cart message is removed.
      // We handle the add to cart message at this place instead.
      $this->messenger->addMessage($this->t('@entity added to <a href=":url">your cart</a>.', [
        '@entity' => $event->getEntity()->label(),
        ':url' => Url::fromRoute('commerce_cart.page')->toString(),
      ]));
    }
  }
}
