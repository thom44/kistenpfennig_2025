<?php

namespace Drupal\custom_product\EventSubscriber;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_product\Entity\Product;

/**
 * Class AddToCartSubscriber
 * @package Drupal\custom_product\EventSubscriber
 */
class AddToCartSubscriber implements EventSubscriberInterface {

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

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CartManagerInterface $commerce_cart_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->commerceCartManager = $commerce_cart_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CartEvents::CART_ENTITY_ADD][] = array('onAddToCart');
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onAddToCart(CartEntityAddEvent $event) {

    $variation = $event->getEntity();

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
      foreach ($cart->getItems() as $order_item) {
        foreach($order_item->getPurchasedEntity() as $purchased_entity){
          if ($purchased_entity->getName() == 'variation_id') {
            $val = $purchased_entity->getValue();
            // The correct functionality depends on the CartQuantityLimiter.
            // If you remove it, you have to prevent adding the product a second time.
            // Also the messages has to be handled.

            $productsInCart[] = $val[0]['value'];
          }
        }
      }

      foreach ($varIds AS $vid) {
        // The current variation we do not add to cart again.
        if ($vid != $currentVariationId) {
          // Checks if the product variation is already in cart,
          // to prevent endless loop.
          if (!in_array($vid, $productsInCart)) {
            $commerce_product_variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
            $variation = $commerce_product_variation_storage->load($vid);
            // Add's the variation to cart.
            $this->commerceCartManager->addEntity($cart, $variation, 1, FALSE, FALSE);
          }
        }
      }
    }
  }
}
