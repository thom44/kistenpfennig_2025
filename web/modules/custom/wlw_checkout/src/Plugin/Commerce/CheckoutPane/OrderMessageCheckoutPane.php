<?php

namespace Drupal\wlw_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Provides a custom checkout pane.
 *
 * @CommerceCheckoutPane(
 *   id = "wlw_order_message_checkout_pane",
 *   label = @Translation("Anmerkungen"),
 *   display_label = @Translation("Anmerkungen"),
 *   wrapper_element = "fieldset",
 * )
 *
 */
class OrderMessageCheckoutPane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $form_display = EntityFormDisplay::collectRenderDisplay($this->order, 'checkout_notes');
    $form_display->buildForm($this->order, $pane_form, $form_state);
    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $form_display = EntityFormDisplay::collectRenderDisplay($this->order, 'checkout_notes');
    $form_display->extractFormValues($this->order, $pane_form, $form_state);
    $form_display->validateFormValues($this->order, $pane_form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $form_display = EntityFormDisplay::collectRenderDisplay($this->order, 'checkout_notes');
    $form_display->extractFormValues($this->order, $pane_form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $markup = '';

    if (
        $this->order->hasField('field_customer_comment')
      &&
        $field_customer_comment = $this->order->get('field_customer_comment')[0]
    ) {
      $markup .= $this->t('<h4>Anmerkungen</h4>');
      $markup .= $field_customer_comment->getValue()['value'];

    }

    if (
        $this->order->hasField('field_qualification')
      &&
        $field_qualification = $this->order->get('field_qualification')[0]
    ) {

        $markup .= $this->t('<h4>Vorbildung</h4>');
        $markup .= $field_qualification->getValue()['value'];

    }

    if ($markup) {
      return [
        '#markup' => $markup,
      ];
    }

    return [];
  }
}
