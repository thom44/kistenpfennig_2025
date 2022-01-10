<?php

namespace Drupal\wlw_cart\Controller;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the cart page. NOT IN USER
 * @see \Drupal\wlw_cart\Routing\CartRouteSubscriber
 */
class CartController extends ControllerBase {

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CartController object.
   *
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   */
  public function __construct(CartProviderInterface $cart_provider, EntityTypeManagerInterface $entity_type_manager) {
    $this->cartProvider = $cart_provider;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Outputs a cart view for each non-empty cart belonging to the current user.
   *
   * @return array
   *   A render array.
   */
  public function cartPage() {
    $build = [];
    $order_type_title = '';
    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->addCacheContexts(['user', 'session']);


    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      return $cart->hasItems();
    });

    if (!empty($carts)) {
      $cart_views = $this->getCartViews($carts);
      foreach ($carts as $cart_id => $cart) {

        // Creates order type title for each cart view.
        $bundle = $cart->bundle();
        if ($bundle == 'default') {
          $order_type_title = $this->t('Produkt Bestellung');
        } elseif ($bundle == 'course') {
          $order_type_title = $this->t('Kursbuchung');
        }

        $build[$cart_id] = [
          '#prefix' => '<div class="cart cart-form">' . $order_type_title,
          '#suffix' => '</div>',
          '#type' => 'view',
          '#name' => $cart_views[$cart_id],
          '#arguments' => [$cart_id],
          '#embed' => TRUE,
        ];
        $cacheable_metadata->addCacheableDependency($cart);
      }
    }
    else {
      $build['empty'] = [
        '#theme' => 'commerce_cart_empty_page',
      ];
    }
    $build['#cache'] = [
      'contexts' => $cacheable_metadata->getCacheContexts(),
      'tags' => $cacheable_metadata->getCacheTags(),
      'max-age' => $cacheable_metadata->getCacheMaxAge(),
    ];

    return $build;
  }

  /**
   * Gets the cart views for each cart.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $carts
   *   The cart orders.
   *
   * @return array
   *   An array of view ids keyed by cart order ID.
   */
  protected function getCartViews(array $carts) {
    $order_type_ids = array_map(function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      return $cart->bundle();
    }, $carts);
    $order_type_storage = $this->entityTypeManager()->getStorage('commerce_order_type');
    $order_types = $order_type_storage->loadMultiple(array_unique($order_type_ids));
    $cart_views = [];
    foreach ($order_type_ids as $cart_id => $order_type_id) {
      /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
      $order_type = $order_types[$order_type_id];
      $cart_views[$cart_id] = $order_type->getThirdPartySetting('commerce_cart', 'cart_form_view', 'commerce_cart_form');
    }

    return $cart_views;
  }
}
