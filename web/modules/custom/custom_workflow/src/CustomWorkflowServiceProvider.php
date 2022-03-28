<?php

namespace Drupal\custom_workflow;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Removes existing event subscribers.
 */
class CustomWorkflowServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Remove the commerce_order event subscriber which send's the order receipt message.
    if ($container->hasDefinition('commerce_order.order_receipt_subscriber')) {
      // Remove complete service
      $container->removeDefinition('commerce_order.order_receipt_subscriber');
    }
  }
}
