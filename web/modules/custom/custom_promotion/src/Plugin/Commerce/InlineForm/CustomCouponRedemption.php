<?php

namespace Drupal\custom_promotion\Plugin\Commerce\InlineForm;

use Drupal\commerce_promotion\Plugin\Commerce\InlineForm\CouponRedemption;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides custom validation messages for coupon redemption.
 */
class CustomCouponRedemption extends CouponRedemption {

  /**
   * {@inheritdoc}
   */
  public function validateInlineForm(array &$inline_form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'] ?? '';
    $button_type = $triggering_element['#button_type'] ?? NULL;

    if ($button_name !== 'apply_coupon' && $button_type !== 'primary') {
      return;
    }

    $coupon_code_parents = array_merge($inline_form['#parents'], ['code']);
    $coupon_code = trim((string) $form_state->getValue($coupon_code_parents));
    $coupon_code_path = implode('][', $coupon_code_parents);

    if ($coupon_code === '') {
      if ($button_name === 'apply_coupon') {
        $form_state->setErrorByName(
          $coupon_code_path,
          $this->t('Please provide a coupon code.')
        );
      }
      return;
    }

    $coupon_storage = $this->entityTypeManager
      ->getStorage('commerce_promotion_coupon');
    $coupon = $coupon_storage->loadEnabledByCode($coupon_code);

    if (!$coupon) {
      $form_state->setErrorByName(
        $coupon_code_path,
        $this->t('Der eingegebene Gutscheincode %code ist nicht gültig.', [
          '%code' => $coupon_code,
        ])
      );
      return;
    }

    $order = $this->entityTypeManager
      ->getStorage('commerce_order')
      ->load($this->configuration['order_id']);

    if (!$order) {
      return;
    }

    $promotion = $coupon->getPromotion();

    foreach ($order->get('coupons')->referencedEntities() as $referenced_coupon) {
      if ($referenced_coupon->id() == $coupon->id()) {
        return;
      }

      if ($referenced_coupon->getPromotionId() == $coupon->getPromotionId()) {
        $form_state->setErrorByName(
          $coupon_code_path,
          $this->t('Der Gutschein %code kann auf diese Bestellung nicht angewendet werden.', [
            '%code' => $coupon_code,
          ])
        );
        return;
      }
    }

    /*
     * Check promotion dates before checking promotion availability.
     */
    $calculation_date = $order->getCalculationDate();
    $timezone = $calculation_date->getTimezone()->getName();
    $date_formatter = \Drupal::service('date.formatter');

    if (!$promotion) {
      $form_state->setErrorByName(
        $coupon_code_path,
        $this->t('Dieser Gutschein %code kann derzeit nicht eingelöst werden.', [
          '%code' => $coupon_code,
        ])
      );
      return;
    }

    if (!$promotion->isEnabled()) {
      $form_state->setErrorByName(
        $coupon_code_path,
        $this->t('Die Aktion %promotion ist derzeit nicht aktiv.', [
          '%promotion' => $promotion->label(),
        ])
      );
      return;
    }

    $promotion_start_date = $promotion->getStartDate();

    if (
      $promotion_start_date &&
      $promotion_start_date->getTimestamp() > $calculation_date->getTimestamp()
    ) {
      $form_state->setErrorByName(
        $coupon_code_path,
        $this->t('Die Aktion %promotion kann erst ab %date eingelöst werden.', [
          '%promotion' => $promotion->label(),
          '%date' => $date_formatter->format(
            $promotion_start_date->getTimestamp(),
            'custom',
            'd.m.Y'
          ),
        ])
      );
      return;
    }

    $promotion_end_date = $promotion->getEndDate();

    /*
     * Check coupon dates.
     */
    $coupon_start_date = $coupon->getStartDate($timezone);

    if (
      $coupon_start_date &&
      $coupon_start_date->getTimestamp() > $calculation_date->getTimestamp()
    ) {
      $form_state->setErrorByName(
        $coupon_code_path,
        $this->t('Dieser Gutschein %code kann erst ab %date eingelöst werden.', [
          '%code' => $coupon_code,
          '%date' => $date_formatter->format(
            $coupon_start_date->getTimestamp(),
            'custom',
            'd.m.Y'
          ),
        ])
      );
      return;
    }

    $coupon_end_date = $coupon->getEndDate($timezone);

    if (
      $coupon_end_date &&
      $coupon_end_date->getTimestamp() <= $calculation_date->getTimestamp()
    ) {
      $form_state->setErrorByName(
        $coupon_code_path,
        $this->t('Dieser Gutschein %code ist am %date abgelaufen.', [
          '%code' => $coupon_code,
          '%date' => $date_formatter->format(
            $coupon_end_date->getTimestamp(),
            'custom',
            'd.m.Y'
          ),
        ])
      );
      return;
    }

    if (
      $promotion_end_date &&
      $promotion_end_date->getTimestamp() <= $calculation_date->getTimestamp()
    ) {
      $form_state->setErrorByName(
        $coupon_code_path,
        $this->t('Die Aktion %promotion ist am %date abgelaufen.', [
          '%promotion' => $promotion->label(),
          '%date' => $date_formatter->format(
            $promotion_end_date->getTimestamp(),
            'custom',
            'd.m.Y'
          ),
        ])
      );
      return;
    }

    $usage = \Drupal::service('commerce_promotion.usage');
    $usage_limit = $coupon->getUsageLimit();
    $customer_usage_limit = $coupon->getCustomerUsageLimit();

    if ($usage_limit && $usage->loadByCoupon($coupon) >= $usage_limit) {
      $promotion_name = $promotion ? $promotion->label() : '';

      $message = $promotion_name
        ? 'Der Gutschein %code der Aktion %promotion wurde bereits vollständig eingelöst.'
        : 'Der Gutschein %code wurde bereits vollständig eingelöst.';

      $args = [
        '%code' => $coupon_code,
      ];
      if ($promotion_name) {
        $args['%promotion'] = $promotion_name;
      }

      $form_state->setErrorByName($coupon_code_path, $this->t($message, $args));
      return;
    }

    if ($customer_usage_limit && $order->getEmail()) {
      $usage_count = $usage->loadByCoupon($coupon, $order->getEmail());

      if ($usage_count >= $customer_usage_limit) {
        $form_state->setErrorByName(
          $coupon_code_path,
          $this->t('Sie haben diesen Gutschein %code bereits verwendet.', [
            '%code' => $coupon_code,
          ])
        );
        return;
      }
    }

    if (!$promotion || !$promotion->available($order)) {

    /*
     * Check promotion usage limits.
     */
    $promotion_usage_limit = $promotion->getUsageLimit();
    $promotion_customer_usage_limit = $promotion->getCustomerUsageLimit();

    if (
      $promotion_usage_limit &&
      $usage->load($promotion) >= $promotion_usage_limit
    ) {
      $form_state->setErrorByName(
        $coupon_code_path,
        $this->t('Die Aktion %promotion wurde bereits vollständig eingelöst.', [
          '%promotion' => $promotion->label(),
        ])
      );
      return;
    }

    if ($promotion_customer_usage_limit && $order->getEmail()) {
      $promotion_customer_usage_count = $usage->load(
        $promotion,
        $order->getEmail()
      );

      if ($promotion_customer_usage_count >= $promotion_customer_usage_limit) {
        $form_state->setErrorByName(
          $coupon_code_path,
          $this->t('Sie haben die Aktion %promotion bereits vollständig genutzt.', [
            '%promotion' => $promotion->label(),
          ])
        );
        return;
      }
    }

      $promotion_name = $promotion ? $promotion->label() : '';

      $message = $promotion_name
        ? 'Die Aktion %promotion ist derzeit nicht aktiv.'
        : 'Dieser Gutschein %code kann derzeit nicht eingelöst werden.';

      $args = $promotion_name
        ? ['%promotion' => $promotion_name]
        : ['%code' => $coupon_code];

      $form_state->setErrorByName($coupon_code_path, $this->t($message, $args));
      return;
    }


    if (!$coupon->available($order)) {
      $form_state->setErrorByName(
        $coupon_code_path,
        $this->t('Der Gutschein %code kann auf diese Bestellung nicht angewendet werden.', [
          '%code' => $coupon_code,
        ])
      );
      return;
    }

    if (!$promotion->applies($order)) {
      $form_state->setErrorByName(
        $coupon_code_path,
        $this->t('Der Gutschein %code kann auf diese Bestellung nicht angewendet werden.', [
          '%code' => $coupon_code,
        ])
      );
      return;
    }

    $inline_form['code']['#coupon_id'] = $coupon->id();
  }

}
