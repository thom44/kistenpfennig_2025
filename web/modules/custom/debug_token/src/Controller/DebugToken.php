<?php

namespace Drupal\debug_token\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An example controller.
 */
class DebugToken extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   */
  public function content() {

    $tokenProvider = \Drupal::service('custom_order_token.order_token_provider');

    $entityTypeManager = \Drupal::service('entity_type.manager');

    $order = $entityTypeManager->getStorage('commerce_order')->load(81);


    foreach ($order->getItems() as $key => $order_item) {

      $purchased_entity = $order_item->getPurchasedEntity();

    }
    $customer = $order->getCustomer();

    $config = $tokenProvider->getEmailConfigTokenReplaced('custom_mail_ui.commerce_order_recipient', $order, $customer);

    $build = [
      '#markup' => $this->t('Hello World!'),
    ];
    return $build;
  }

}
