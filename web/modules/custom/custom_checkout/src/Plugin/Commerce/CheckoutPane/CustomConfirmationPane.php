<?php

namespace Drupal\custom_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Provides a custom checkout pane.
 *
 * @CommerceCheckoutPane(
 *   id = "custom_confirmation_checkout_pane",
 *   label = @Translation("Confirmation"),
 *   display_label = @Translation(""),
 *   wrapper_element = "fieldset",
 * )
 *
 */
class CustomConfirmationPane extends CheckoutPaneBase {

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
        'custom_confirmation_text' => $this->t('Text zur Einwilligung.'),
      ];
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['custom_confirmation_text'] = $values['custom_confirmation_text'];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['custom_confirmation_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text der Einwilligung.'),
      '#default_value' => $this->configuration['custom_confirmation_text'],
      '#resizable' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $form_display = EntityFormDisplay::collectRenderDisplay($this->order, 'custom_confirmation');
    $form_display->extractFormValues($this->order, $pane_form, $form_state);

    // Dirty bugfix for missing order number. Otherwise validation breaks.
    $this->order->set('order_number',$this->order->id());
    $this->order->save();

    $form_display->validateFormValues($this->order, $pane_form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $form_display = EntityFormDisplay::collectRenderDisplay($this->order, 'custom_confirmation');
    $form_display->extractFormValues($this->order, $pane_form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    $pane_form['field_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => t($this->configuration['custom_confirmation_text']),
      '#options' => array(
        '1' => t('Yes'),
        '0' => t('No')
      ),
      '#required' => TRUE
    ];

    return $pane_form;
  }

}
