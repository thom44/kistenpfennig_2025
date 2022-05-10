<?php

namespace Drupal\custom_order_cancel\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * User Form to cancel own order.
 *
 * @internal
 */
class CancelOrderForm extends FormBase {

  use StringTranslationTrait;

  /**
   * The current order.
   *
   * @var: Drupal\Commerce
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_order_cancel_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $order = NULL) {

    $allowed = FALSE;

    $this->order = $order;

    $state = $order->getState()->getValue()['value'];

    if ($state == 'canceled' || $state == 'completed') {
      $form['header'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Nicht mehr möglich'),
      ];
      return $form;
    }

    // Calculate if the order is allowed to cancel.
    $deadline = 16;
    $hour = date("H");
    $date = date("Ymd");
    $date_yesterday =  date("Ymd", strtotime("yesterday"));

    $hour_created = date("H", $order->getCreatedTime());
    $date_created = date("Ymd", $order->getCreatedTime());

    if ($hour >= $deadline) {
      // jetzt ist nach 16 uhr
      if ($date == $date_created) {
        // Die Bestellung ist von heute.
        if ($hour_created >= $deadline) {
          // Die Bestellung ist von heute nach 16 Uhr.
          $allowed = TRUE;
        }
      }

    } else {
      // jetzt ist noch vor 16 uhr.
      if ($date == $date_created) {
        // Die Bestellung ist von heute.
        $allowed = TRUE;
      }
      if ($date_yesterday == $date_created) {
        if ($hour_created >= $deadline) {
          // Die Bestellung ist von gestern nach 16 Uhr.
          $allowed = TRUE;
        }
      }
    }

    if ($allowed === TRUE) {
      $form['submit'][1] = [
        '#type' => 'submit',
        '#value' => $this->t('Stornieren'),
        '#button_type' => 'primary',
        '#attributes' => array('onclick' => 'if(!confirm("Wollen Sie die Bestellung wirklich stornieren? Der Vorgang kann nicht Rückgängig gemacht werden.")){return false;}')
      ];
    } else {
      $form['header'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Nicht mehr möglich'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->order->getState()->applyTransitionById('cancel');
    $this->order->save();

    return $form;
  }
}
