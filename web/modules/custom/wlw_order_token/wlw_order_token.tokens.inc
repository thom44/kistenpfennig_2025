<?php
/**
 * @file Profides token declaration for order token.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function wlw_order_token_token_info() {

  $info = [];

  $info['types']['recipient_comment'] = [
    'name' => t('Recipient Comment'),
    'description' => t('The comment from the recipient.')
  ];

  $info['tokens']['recipient_comment']['author'] = [
    'name' => t("The author of the comment"),
    'description' => t("The username of the comment author")
  ];

  $info['tokens']['recipient_comment']['uid'] = [
    'name' => t("The authors uid of the comment"),
    'description' => t("The uid of the comment author")
  ];

    return $info;
}

/**
 * Implements hook_token_info_alter().
 */
function  wlw_order_token_token_info_alter(&$data) {
  $data['tokens']['commerce_order']['wlw_order_payment_method'] = [
    'name' => t("WLW Payment Method Title"),
    'description' => t("The title of the payment method."),
  ];
  $data['tokens']['commerce_order']['wlw_order_items'] = [
    'name' => t('WLW Order Items'),
    'description' => t('Tokens of the order items as formatted table.'),
  ];
  $data['tokens']['commerce_order']['wlw_order_billing_address'] = [
    'name' => t('WLW Order billing address'),
    'description' => t('Tokens of the order billing addresse.'),
  ];
  $data['tokens']['commerce_order']['wlw_order_shipping_address'] = [
    'name' => t('WLW Order shipping address'),
    'description' => t('Tokens of the order shipping address.'),
  ];
  $data['tokens']['commerce_order']['wlw_product_variation_groups'] = [
    'name' => t('WLW Product Variations Groups'),
    'description' => t('Tokens of the assigned groups to the product variations in the order.'),
  ];

}

/**
 * Implements hook_tokens().
 */
function wlw_order_token_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];

  if ($type == 'commerce_order') {

    // @var \Drupal\wlw_order_token\OrderTokenProvider
    $order_token_provider = \Drupal::service('wlw_order_token.order_token_provider');

    $order = $data['commerce_order'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'wlw_order_billing_address':

          $replacements[$original] = $order_token_provider->getRenderedBillingAddress($order);

          break;
        case 'wlw_order_shipping_address':

          $replacements[$original] = $order_token_provider->getRenderedShippingAddress($order);

          break;
        case 'wlw_order_items':

           $replacements[$original] = $order_token_provider->getOrderItemTable($order);

          break;
        case 'wlw_order_payment_method':

          $replacements[$original] = $order_token_provider->getPaymentMethodLabel($order);

          break;
        case 'wlw_product_variation_groups':

          $replacements[$original] = $order_token_provider->getAssignedProductVariationGroups($order);

          break;
      }
    }
  }

  if ($type == 'recipient_comment') {

    $recipient_comment = $data['recipient_comment'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'author':

          $replacements[$original] = $recipient_comment->getAuthorName();

          break;
        case 'uid':

          $replacements[$original] = $recipient_comment->getOwnerId();

          break;
      }
    }
  }

  return $replacements;
}
