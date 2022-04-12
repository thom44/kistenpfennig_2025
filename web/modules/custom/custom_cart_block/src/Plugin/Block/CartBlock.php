<?php

namespace Drupal\custom_cart_block\Plugin\Block;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\custom_product\PriceFormatterHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a cart block.
 *
 * @Block(
 *   id = "custom_commerce_cart",
 *   admin_label = @Translation("custom Cart Block"),
 *   category = @Translation("Commerce")
 * )
 */
class CartBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The price formatter helper service.
   *
   * @var \Drupal\custom_product\PriceFormatterHelper
   */
  protected $priceFormatterHelper;

  /**
   * Constructs a new CartBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CartProviderInterface $cart_provider, EntityTypeManagerInterface $entity_type_manager, PriceFormatterHelper $price_formatter_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->cartProvider = $cart_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->priceFormatterHelper = $price_formatter_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_cart.cart_provider'),
      $container->get('entity_type.manager'),
      $container->get('custom_product.price_formatter_helper')
    );
  }

  /**
   * Builds the cart block.
   *
   * @return array
   *   A render array.
   */
  public function build() {
    $cachable_metadata = new CacheableMetadata();
    $cachable_metadata->addCacheContexts(['user', 'session']);

    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      // There is a chance the cart may have converted from a draft order, but
      // is still in session. Such as just completing check out. So we verify
      // that the cart is still a cart.
      return $cart->hasItems() && $cart->cart->value;
    });

    $count = 0;
    $total = 0;
    $currency_code = '';
    $total_formatted = '';
    if (!empty($carts)) {
      foreach ($carts as $cart_id => $cart) {
        // Gets total price from each cart.
        $total += (float) $cart->get('total_price')->getValue()[0]['number'];
        $currency_code = $cart->get('total_price')->getValue()[0]['currency_code'];

        foreach ($cart->getItems() as $order_item) {
          $count += (int) $order_item->getQuantity();
        }
        $cachable_metadata->addCacheableDependency($cart);
      }
    }

    if ($count > 0) {
      // Prepares formatted total price from all carts.
      $total_summery = [
        'number' => $total,
        'currency_code' => $currency_code,
      ];
      $total_formatted = $this->priceFormatterHelper->getFormattedPriceByAmount($total_summery, FALSE);
    }

    return [
      '#attached' => [
        'library' => ['commerce_cart/cart_block'],
      ],
      '#theme' => 'custom_commerce_cart_block',
      '#icon' => [
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'custom_cart_block') . '/icons/cart.svg',
        '#alt' => $this->t('Shopping cart'),
      ],
      '#count' => $count,
      '#total' => $total_formatted,
      '#url' => Url::fromRoute('commerce_cart.page')->toString(),
      '#cache' => [
        'contexts' => ['cart'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['cart']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $cart_cache_tags = [];

    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCarts();
    foreach ($carts as $cart) {
      // Add tags for all carts regardless items or cart flag.
      $cart_cache_tags = Cache::mergeTags($cart_cache_tags, $cart->getCacheTags());
    }
    return Cache::mergeTags($cache_tags, $cart_cache_tags);
  }

}
