<?php
namespace Drupal\wlw_order_token;

use Drupal\commerce_price\Price;
use Drupal\commerce_order\OrderTotalSummaryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\group\Entity\Group;
use Drupal\user\UserInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\taxonomy\Entity\Term;

class OrderTokenProvider {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface;
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer $renderer
   */
  protected $renderer;

  /**
   * The token service.
   *
   * @var Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The profile view builder.
   *
   * @var \Drupal\profile\ProfileViewBuilder $profileViewBuilder
   */
  protected $profileViewBuilder;

  /**
   * The order object.
   *
   * @var $order
   */
  protected $order;

  /**
   * The order total summery service.
   *
   * @var \Drupal\commerce_order\OrderTotalSummaryInterface
   */
  protected $orderTotalSummery;

  /**
   * OrderTokenProvider constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Render\Renderer $renderer
   * @param \Drupal\Core\Utility\Token $token
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    Renderer $renderer,
    Token $token,
    OrderTotalSummaryInterface $order_total_summary
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->token = $token;
    $this->profileViewBuilder = $this->entityTypeManager->getViewBuilder('profile');
    $this->orderTotalSummery = $order_total_summary;
  }

  /**
   * Sets the order object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   */
  protected function setOrder(OrderInterface $order) {
    if (!isset($order)) {
      $this->order = $order;
    }
  }

  /**
   * Prepares email with token replace.
   *
   * @param string $config_key.
   *   The configuration key
   * @param \Drupal\user\UserInterface $user
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return array
   *   The email subject and body;
   */
  public function getEmailConfigTokenReplaced($config_key, OrderInterface $order, UserInterface $user) {

    $data = [];

    // Retrieves email configuration.
    $mail_config = $this->configFactory->get($config_key);
    $email_subject = $mail_config->get('email_subject');
    $email_body = $mail_config->get('email_body');
    $bcc_email = $mail_config->get('bcc_email');
    $from_email = $mail_config->get('from_email');

    // Replaces the tokens in subject and body.
    $variables = [
      'user' => $user,
      'commerce_order' => $order
    ];

    $data['subject'] = $this->token->replace($email_subject, $variables, ['clear' => TRUE]);
    $data['body'] = $this->token->replace($email_body, $variables, ['clear' => TRUE]);
    $data['bcc'] = $bcc_email;
    $data['from'] = $from_email;

    return $data;
  }

  /**
   * Generates a list with assigned groups to the product variations.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return string
   *    A list of assigned groups.
   */
  public function getAssignedProductVariationGroups(OrderInterface $order) {

    $gids = [];
    $group_list = [];
    $product_ids = [];
    $markup = '';

    $order_items = $order->getItems();

    foreach ($order_items as $order_item) {

      $purchased_entity = $order_item->getPurchasedEntity();

      // Collect distinct product ids of the order.
      $product_id = $purchased_entity->get('product_id')->getValue()[0]['target_id'];
      $product_ids[$product_id] = $product_id;

      // Process product variations with field_group.
      if ($purchased_entity->hasField('field_group')) {

        foreach ($purchased_entity->get('field_group') as $field_group) {
          $gids[] = $field_group->getValue()['target_id'];
        }
      }
    }

    // Process products with field_group.
    foreach ($product_ids as $product_id) {

      $product = $this->entityTypeManager->getStorage('commerce_product')->load($product_id);

      if ($product->hasField('field_group') && $product->get('field_group')[0]) {

        foreach ($product->get('field_group') as $field_group) {
          $gids[] = $field_group->getValue()['target_id'];
        }
      }
    }

    // Prepares list with group names.
    foreach ($gids as $gid) {

      // Skip -none- option
      if (isset($gid) || $gid != '_none') {
        $group = Group::load($gid);

        // Gets reverenced parent term.
        $fields = $group->getFields();
        $field_media_parent = $fields['field_media_parent']->getValue();
        if (
          isset($field_media_parent[0]['target_id'])
        &&
          $tid = $field_media_parent[0]['target_id']
        ) {
          $group_list[$tid][$gid] = $group->label();
        }
      }
    }

    // We group the list with parent terms.
    foreach ($group_list as $tid => $groups) {

      // Creates an items list for each gid in one array.
      $items = [];
      foreach ($groups as $gid => $group) {
        $items[] = $group;
      }
      $term = Term::load($tid);

      $list[] = [
        '#theme' => 'item_list',
        '#title' => $term->get('name')->getValue()[0]['value'],
        '#list_type' => 'ul',
        '#items' => $items,
        '#attributes' => ['class' => 'wlw-assigned-groups-list'],
      ];
    }

    return $this->renderer->render($list);
  }

  /**
   * Gets the payment method label of the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return string
   *    The payment method label.
   */
  public function getPaymentMethodLabel(OrderInterface $order) {

    $payment_gateway = $order->get('payment_gateway');
    $gateway = $payment_gateway->getValue('target_id')[0]['target_id'];

    $config_key = 'commerce_payment.commerce_payment_gateway.' . $gateway;
    $config = $this->configFactory->get($config_key);
    $label = $config->get('label');

    return $label;
  }

  /**
   * The rendered billing address of the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return string
   *    The rendered billing address of the order.
   */
  public function getRenderedBillingAddress(OrderInterface $order) {

    $billing_profile = $order->getBillingProfile();
    $billing_profile_view = $this->profileViewBuilder->view($billing_profile);

    return $this->renderer->render($billing_profile_view);
  }

  /**
   * The rendered shipping profile of the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return string
   *    The rendered shipping address of the order.
   */
  public function getRenderedShippingAddress(OrderInterface $order) {

    $field_shipping_profile = $order->get('field_shipping_profile');
    if ($field_shipping_profile[0]) {
      // Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem
      $profile_id = $field_shipping_profile[0]->getValue()['target_id'];
      //$revision_id = $order->get('field_shipping_profile')[0]->getValue()['target_revision_id'];

      $shipping_profile = $this->entityTypeManager->getStorage('profile')->load($profile_id);
      if ($shipping_profile) {
        $shipping_profile_view = $this->profileViewBuilder->view($shipping_profile);
        return $this->renderer->render($shipping_profile_view);
      }
    } else {
      return $this->t('Wie Rechnungsanschrift');
    }
  }

  /**
   * The order item table with totals.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return string
   *    The order item table with totals.
   */
  public function getOrderItemTable(OrderInterface $order) {

    $totals = [];
    $order_items_data = [];
    $tax_rate_value = 0;

    $items = $order->getItems();

    $i = 0;
    foreach ($items as $order_item) {
      // Saves the complete item to use some functions directly in the template.
      $order_items_data[$i]['item'] = $order_item;

      $purchased_entity = $order_item->getPurchasedEntity();
      //$variation_id = $purchased_entity->get('variation_id');
      $field_tax_rate = $purchased_entity->get('field_tax_rate')[0]->getValue()['value'];

      // Retrieves tax_rate values from configuration object
      $tax = explode('|', $field_tax_rate);
      $tax_type = $tax[0];
      $tax_rate_id = $tax[1];

      if ($data = $this->configFactory->get('commerce_tax.commerce_tax_type.' . $tax_type)) {
        foreach ($data->get('configuration')['rates'] as $rate) {
          if ($rate['id'] == $tax_rate_id) {
            $order_items_data[$i]['tax_label'] = $rate['label'];
            $tax_rate_value = $rate['percentage'];
            $order_items_data[$i]['tax_rate'] = $tax_rate_value * 100;
          }
        }
      }

      // Prepares price Netto and tax amount.
      $price = $order_item->getTotalPrice();
      $price_value = $price->getNumber();
      $currency_code = $price->getCurrencyCode();

      $tax_percentage = $tax_rate_value * 100;
      $tax_amount = round(($price_value / (100 + $tax_percentage)) * $tax_percentage, 2);
      // Creates an price object to use the commerce_price_formatter in the template.
      $order_items_data[$i]['tax_amount'] = new Price($tax_amount, $currency_code);

      $net_amount = round($price_value - $tax_amount, 2);
      $order_items_data[$i]['net_amount'] = new Price($net_amount, $currency_code);

      $i++;
    }

    $totals = $this->orderTotalSummery->buildTotals($order);
    foreach ($totals['adjustments'] as $key => $adjustment) {
      if ($adjustment['type'] == 'tax') {
        $totals['adjustments'][$key]['label'] = 'Enthält ' . ($adjustment['percentage']*100) . '% ' . $adjustment['label'];
      }
    }

    $output = [
      '#theme' => 'order_email_item_table',
      '#order_entity' => $order,
      '#totals' => $totals,
      '#order_items_data' => $order_items_data,
      '#attached' => ['library' => ['wlw_order_token/order-item-table']],
    ];

    return $this->renderer->render($output);
  }
}
