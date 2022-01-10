<?php

namespace Drupal\custom_product;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Replaces the add to cart message.
 */
class CustomProductServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Remove the server side add to cart messaging.
    if ($container->hasDefinition('commerce_cart.cart_subscriber')) {
      $container->removeDefinition('commerce_cart.cart_subscriber');
    }
  }

}
