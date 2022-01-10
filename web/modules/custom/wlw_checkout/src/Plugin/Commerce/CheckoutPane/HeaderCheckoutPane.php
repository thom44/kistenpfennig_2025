<?php

namespace Drupal\wlw_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom checkout pane for an checkout header with info text.
 *
 * @CommerceCheckoutPane(
 *   id = "wlw_header_checkout_pane",
 *   label = @Translation("WLW Order Header"),
 *   display_label = @Translation(""),
 *   wrapper_element = "fieldset",
 * )
 */
class HeaderCheckoutPane extends CheckoutPaneBase {

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
        'wlw_checkout_header' => $this->t('Standard Bestellung'),
        'wlw_checkout_info' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationSummary() {
    return $this->t('Custom header: @wlw_checkout_header', ['@wlw_checkout_header' => $this->configuration['wlw_checkout_header']]);
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['wlw_checkout_header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Checkout Header'),
      '#default_value' => $this->configuration['wlw_checkout_header'],
      '#resizable' => TRUE,
    ];
    $form['wlw_checkout_info'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Checkout Info'),
      '#default_value' => $this->configuration['wlw_checkout_info'],
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
      $this->configuration['wlw_checkout_header'] = $values['wlw_checkout_header'];
      $this->configuration['wlw_checkout_info'] = $values['wlw_checkout_info']['value'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    $markup = $this->t('<h2 class="wlw-checkout-header">@wlw_checkout_header</h2>', ['@wlw_checkout_header' => $this->configuration['wlw_checkout_header']]);

    // Renders html from text_format field without escaping.
    $markup .= $this->t(
      '<div class="wlw-checkout-info">@wlw_checkout_info</div>',
      [
        '@wlw_checkout_info' => new FormattableMarkup($this->configuration['wlw_checkout_info'], [])
      ]
    );
    $pane_form['header'] = [
      '#markup' => $markup,
    ];
    return $pane_form;
  }

}
