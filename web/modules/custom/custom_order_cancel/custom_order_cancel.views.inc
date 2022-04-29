<?php

/**
 * Implements hook_views_data_alter().
 */
function custom_order_cancel_views_data_alter(array &$data) {
  $data['commerce_order']['cancel_order_views_field'] = array(
    'title' => t('Commerce Order Cancel Button'),
    'group' => t('Bestellung'),
    'field' => array(
      'title' => t('Commerce Order Cancel Button'),
      'help' => t('Adds a time controlled order cancel button.'),
      'id' => 'cancel_order_views_field',
    ),
  );
}
