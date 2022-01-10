<?php

namespace Drupal\wlw_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\InlineFormManager;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_payment\PaymentOptionsBuilderInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom checkout pane.
 *
 * @CommerceCheckoutPane(
 *   id = "wlw_shipping_address_checkout_pane",
 *   label = @Translation("Shipping Address"),
 *   display_label = @Translation("Shipping Address"),
 *   wrapper_element = "fieldset",
 * )
 *
 */
class ShippingAddressCheckoutPane extends CheckoutPaneBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * The payment options builder.
   *
   * @var \Drupal\commerce_payment\PaymentOptionsBuilderInterface
   */
  protected $paymentOptionsBuilder;

  /**
   * The entityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;


  /**
   * Constructs a new PaymentInformation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   * @param \Drupal\commerce_payment\PaymentOptionsBuilderInterface $payment_options_builder
   *   The payment options builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, InlineFormManager $inline_form_manager, PaymentOptionsBuilderInterface $payment_options_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);
    $this->currentUser = $current_user;
    $this->inlineFormManager = $inline_form_manager;
    $this->paymentOptionsBuilder = $payment_options_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.commerce_inline_form'),
      $container->get('commerce_payment.options_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $shipping_profile_list = [];
    $enabled = NULL;
    $copy_to_address_book = TRUE;
    $uid = $this->currentUser->id();

    $inputs = $form_state->getUserInput();

    // Gets the value saved in the order on first call.
    $enabled = $this->order->get('field_add_shipping_address')[0]->getValue()['value'];

    if (isset($inputs['wlw_shipping_address_checkout_pane']['field_add_shipping_address'])) {
      // Gets form value on ajax refresh.
      $enabled = $inputs['wlw_shipping_address_checkout_pane']['field_add_shipping_address'];
    }

    $pane_form['field_add_shipping_address'] = [
      '#type' => 'checkbox',
      '#title' => 'Abweichende Lieferadresse eingeben.',
      '#default_value' => $enabled,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $pane_form['#id'],
      ],
    ];

    if ($enabled) {
      $shipping_profile_list = $this->entityTypeManager
        ->getStorage('profile')
        ->loadByProperties([
          'uid' => $uid,
          'type' => 'shipping',
          'is_default' => 1,
        ]);

      // Gets the only possible first default shipping profile from the array.
      $shipping_profile = reset($shipping_profile_list);

      if (!$shipping_profile) {
        $shipping_profile = $this->entityTypeManager->getStorage('profile')
          ->create([
            'type' => 'shipping',
            'uid' => $uid,
          ]);
      }

      // Only new profiles should have copy-to-address-book checkbox checked by default.
      if (
        isset($inputs['wlw_contact_address_checkout_pane']['contact_profile']['select_address'])
        &&
        $inputs['wlw_contact_address_checkout_pane']['contact_profile']['select_address'] != '_new'
      ) {
        $copy_to_address_book = FALSE;
      }
      // Sets profile entity flag according to the selected profile.
      $shipping_profile->setData('copy_to_address_book',$copy_to_address_book);

      $inline_form = $this->inlineFormManager->createInstance('customer_profile', [
        'profile_scope' => 'shipping',
        'available_countries' => $this->order->getStore()->getBillingCountries(),
        'address_book_uid' => $uid,
        // We need true to that the profile has profile_id in submitForm.
        'copy_on_save' => FALSE,
      ], $shipping_profile);

      $pane_form['shipping_profile'] = [
        '#parents' => array_merge($pane_form['#parents'], ['shipping_profile']),
        '#inline_form' => $inline_form,
      ];

      // The shipping_profile should always exist in form state
      if (!$form_state->has('shipping_profile')) {
        $form_state->set('shipping_profile', $inline_form->getEntity());
      }

      $pane_form['shipping_profile'] = $inline_form->buildInlineForm($pane_form['shipping_profile'], $form_state);

    }

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValues();

    if ($values) {
      // Saves the field value to the order.
      $enabled = $values['wlw_shipping_address_checkout_pane']['field_add_shipping_address'];
      $this->order->set('field_add_shipping_address', $enabled);

      if (isset($values['wlw_shipping_address_checkout_pane']['shipping_profile'])) {
        // Gets profile entity to save the profile id to the
        //   entity_reference_revisions field of the order.
        /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
        $inline_form = $pane_form['shipping_profile']['#inline_form'];
        /** @var \Drupal\profile\Entity\ProfileInterface $profile */
        $profile = $inline_form->getEntity();
        $profile_id = $profile->get('profile_id')[0]->getValue()['value'];
        $revision_id = $profile->get('revision_id')[0]->getValue()['value'];

        // Prepares the entity_reference_revisions field value.
        $profile_values = [
          'target_id' => $profile_id,
          'target_revision_id' => $revision_id,
        ];

        // Saves the profile values to the order.
        $this->order->set('field_shipping_profile', $profile_values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {

    // Displays only when it is visible. @see inheritdoc
    if (!$this->isVisible()) {
      return [];
    }

    if ($this->order->get('field_add_shipping_address')[0]->getValue()['value'] != 1) {
      //$label = $this->order->getFieldDefinition('field_add_shipping_address')->getLabel();
      return [];
    }

    if (!$this->order->hasField('field_shipping_profile')) {
      return [];
    }

    if (!$field_shipping_profile = $this->order->get('field_shipping_profile')[0]) {
      return [];
    }

    $shipping_profile_id = $field_shipping_profile->getValue()['target_id'];

    $shipping_profile = $this->entityTypeManager->getStorage('profile')->load($shipping_profile_id);

    $profile_view_builder = $this->entityTypeManager->getViewBuilder('profile');
    $summary = $profile_view_builder->view($shipping_profile, 'default');

    return $summary;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);
    return NestedArray::getValue($form, $parents);
  }
}