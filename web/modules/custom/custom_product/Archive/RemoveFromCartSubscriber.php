<?php

namespace Drupal\custom_product\EventSubscriber;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartOrderItemRemoveEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Messenger\Messenger;

/**
 * Class AddToCartSubscriber
 * @package Drupal\custom_product\EventSubscriber
 */
class RemoveFromCartSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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

  /**
   * {@inheritdoc}
   */
  public function __construct(CartManagerInterface $commerce_cart_manager, Messenger $messenger) {
    $this->commerceCartManager = $commerce_cart_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CartEvents::CART_ORDER_ITEM_REMOVE][] = array('onRemoveFromCart');
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onRemoveFromCart(CartOrderItemRemoveEvent $event) {

    $currentOrderItem = $event->getOrderItem();
    $variation = $currentOrderItem->getPurchasedEntity();

    // Gets product_id from the added product.
    $productIds = $variation->get('product_id')->getValue();
    $productId = $productIds[0]['target_id'];
    // Gets the variation_id from the added variation.
    $variationIds = $variation->get('variation_id')->getValue();
    $currentVariationId = $variationIds[0]['value'];
    // Gets the product object.
    $product = Product::load($productId);
    // Gets the product type.
    $types = $product->get('type')->getValue();
    $type = $types[0]['target_id'];

    if ($type == 'combi_product') {
      // Loads all product variation of this combiproduct.
      $varIds = $product->getVariationIds();

      // Gather first the product variations which already in cart.
      $cart = $event->getCart();
      $productsInCart = [];
      $itemsInCart = [];
      foreach ($cart->getItems() as $order_item) {
        foreach($order_item->getPurchasedEntity() as $purchased_entity){
          if ($purchased_entity->getName() == 'variation_id') {
            $val = $purchased_entity->getValue();
            $productsInCart[] = $val[0]['value'];
            $itemsInCart[$val[0]['value']] = $order_item;
          }
        }
      }

      foreach ($varIds AS $vid) {
        // The current variation we do not remove from cart again.
        if ($vid != $currentVariationId) {
          // Checks if the product variation is in cart.
          if (in_array($vid, $productsInCart)) {
            // Removes the variation from the cart.
            $this->commerceCartManager->removeOrderItem($cart, $itemsInCart[$vid]);
            // Creates am message for the user
            $this->messenger->addMessage($this->t('@entity was also removed from <a href=":url">your cart</a> because it belongs to the removed product.', [
              '@entity' => $itemsInCart[$vid]->label(),
              ':url' => Url::fromRoute('commerce_cart.page')->toString(),
            ]));
          }
        }
      }
    }
  }
}
