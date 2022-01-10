<?php

namespace Drupal\wlw_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom checkout pane.
 *
 * @CommerceCheckoutPane(
 *   id = "wlw_shipping_info_checkout_pane",
 *   label = @Translation("WLW Shipping Info"),
 *   display_label = @Translation("Versandart"),
 *   wrapper_element = "fieldset",
 * )
 */
class ShippingInfoCheckoutPane extends CheckoutPaneBase {

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
        'custom_message' => 'This is my custom message.',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationSummary() {
    return $this->t('Custom message: @custom_message', ['@custom_message' => $this->configuration['custom_message']]);
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['custom_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Custom message'),
      '#default_value' => $this->configuration['custom_message'],
      '#format' => 'full_html',
      '#rows' => 4,
      '#cols' => 5,
      '#resizable' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['custom_message'] = $values['custom_message']['value'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    $text = $this->t(
      '<div class="shipping-message">@custom_message</div>',
      [
        '@custom_message' => new FormattableMarkup($this->configuration['custom_message'], [])
      ]
    );

    $image = '<div class="shipping-image"><img src="/modules/custom/shop/wlw_checkout/images/dhl.png" alt="DHL Logo"></div>';

    $pane_form['message'] = [
      '#markup' => $text . $image,
    ];
    return $pane_form;
  }

}
