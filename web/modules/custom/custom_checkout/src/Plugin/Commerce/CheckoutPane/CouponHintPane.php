<?php

namespace Drupal\custom_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a coupon hint checkout pane.
 *
 * @CommerceCheckoutPane(
 *   id = "custom_coupon_hint",
 *   label = @Translation("Coupon hint"),
 *   display_label = @Translation(""),
 *   default_step = "_sidebar",
 *   wrapper_element = "container",
 * )
 */
class CouponHintPane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['message'] = [
      '#markup' => '<div class="coupon-hint">
        <strong>' . $this->t('Gutscheincode') . '</strong>
        <p>' . $this->t('Sie können Ihren Gutscheincode im nächsten Schritt eingeben.') . '</p>
      </div>',
    ];

    return $pane_form;
  }

}
