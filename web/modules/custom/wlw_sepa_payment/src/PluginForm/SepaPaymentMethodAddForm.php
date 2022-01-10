<?php

namespace Drupal\wlw_sepa_payment\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

class SepaPaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'sepa_payment') {
      $form['payment_details'] = $this->buildSepaForm($form['payment_details'], $form_state);
    }

    // Attaching the iban field library not working.
    // @see wlw_sepa_payment.module
    // wlw_sepa_payment_form_commerce_checkout_flow_multistep_default_alter().
    //$form['#attached']['library'][] = 'wlw_sepa_payment/iban-field';

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  protected function buildSepaForm(array $element, FormStateInterface $form_state) {

    $element['#attributes']['class'][] = 'sepa-form';

    $element['sepa_payment_iban'] = [
      '#type' => 'textfield',
      '#title' => t('IBAN'),
      '#required' => TRUE,
    ];

    $element['sepa_payment_bic'] = [
      '#type' => 'textfield',
      '#title' => t('BIC'),
      '#required' => TRUE,
    ];

    $element['sepa_payment_confirm'] = [
      '#type' => 'checkbox',
      '#title' => t('SEPA Lastschriftmandat'),
      '#description' =>  t('Hiermit erteile ich die zur SEPA-Basislastschrift erforderliche Ermächtigung und Anweisungen.'),
      '#required' => TRUE,
    ];

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'sepa_payment') {
      $this->validateSepaForm($form['payment_details'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'sepa_payment') {
      $this->submitSepaForm($form['payment_details'], $form_state);
    }
  }


  /**
   * Validates the sepa form.
   *
   * @param array $element
   *   The credit card form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function validateSepaForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    if (!$values['sepa_payment_iban']) {
      $form_state->setError($element['sepa_payment_iban'], t('You have to enter an IBAN number.'));
    } elseif (strpos($values['sepa_payment_iban'], "DE") !== 0) {
      $form_state->setError($element['sepa_payment_iban'], t('The IBAN number must start with DE. There are only German IBAN numbers allowed. If your account is in a foreign country, then please contact us. <a href="mailto:info@wlw-bamberg.de">info@wlw-bamberg.de</a>.'));
    } elseif (strlen($values['sepa_payment_iban']) < 27) {
      $form_state->setError($element['sepa_payment_iban'], t('The IBAN number must have at least 22 characters and must be without hyphens.'));
    }

    if (!$values['sepa_payment_bic']) {
      $form_state->setError($element['sepa_payment_bic'], t('You have to enter a BIC number.'));
    }
    if ($values['sepa_payment_confirm'] != 1) {
      $form_state->setError($element['sepa_payment_confirm'], t('You have to confirm the sepa mandate.'));
    }
  }

  /**
   * Handles the submission of the credit card form.
   *
   * @param array $element
   *   The credit card form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function submitSepaForm(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    // Saves the values to the payment_method entity like the base class.
    // @note: Actually the values already saved in SepaPaymentGateway->creataPaymentMethod().
    $this->entity->sepa_payment_iban = $values['sepa_payment_iban'];
    $this->entity->sepa_payment_bic = $values['sepa_payment_bic'];
    $this->entity->sepa_payment_confirm = $values['sepa_payment_confirm'];
  }
}
