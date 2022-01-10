<?php

namespace Drupal\wlw_sepa_payment\Plugin\Commerce\PaymentMethodType;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the SEPA depit payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "sepa_payment",
 *   label = @Translation("Add SEPA Payment"),
 * )
 */
class SepaPayment extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {

    $args = [
      '@iban' => $payment_method->get('sepa_payment_iban')->getValue()[0]['value'],
      '@bic' => $payment_method->get('sepa_payment_bic')->getValue()[0]['value'],
    ];

    return $this->t('SEPA Lastschrift:<br>IBAN @iban<br>BIC: @bic', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['sepa_payment_iban'] = BundleFieldDefinition::create('string')
      ->setLabel(t('IBAN'))
      ->setDescription(t('The bank account IBAN.'))
      ->setRequired(TRUE);

    $fields['sepa_payment_bic'] = BundleFieldDefinition::create('string')
      ->setLabel(t('BIC'))
      ->setDescription(t('The bank BIC number'))
      ->setRequired(TRUE);

    $fields['sepa_payment_confirm'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('SEPA Mandate'))
      ->setDescription(t('The SEPA Mandate Confirmation.'))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array(
          'display_label' => TRUE,
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
