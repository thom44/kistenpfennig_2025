<?php

namespace Drupal\wlw_addon\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides the order item add to cart form.
 */
class AddOnProductAddToCartForm extends FormBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The EntityTypeManager provider.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * @var $renderer
   */
  protected $renderer;

  /**
   * @var $configFactory
   */
  protected $configFactory;

  /**
   * @var $productId interger
   */
  private $productId;

  /**
   * @var $variationEntity array
   */
  protected $variationEntity = [];

  /**
   * The Constructor
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, EntityTypeManager $entity_manager, Renderer $renderer, ConfigFactory $config_factory) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->entityManager = $entity_manager;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wlw_addon_product_add_to_cart_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $variations = []) {

    $options = [];
    $tax_rate_value = '';

    if (!is_array($variations)) {
      $variations = [$variations];
    }
    // Creates DOM Element for JQuery price display.
    $form['price'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '0,00 €',
      '#attributes' => [
        'id' => 'jquery-calculated-price',
        'class' => [
          'field--name-price',
          'field--type-commerce-price',
        ]
      ],
    ];
    foreach ($variations AS $variation_id) {

      $product_variation = $this->entityManager->getStorage('commerce_product_variation')->load((int)$variation_id);

      // Gets formatted price value with currency symbol
      $fields = $product_variation->getFields();
      // @todo: try simple way
      // $price_value = $price->getNumber();
      // $currency_code = $price->getCurrencyCode();
      $price = $fields['price'][0]->getValue();
      $element['price'] = [
        '#type' => 'inline_template',
        '#template' => '{{ price|commerce_price_format }}',
        '#context' => [
          'price' => $price,
        ],
      ];
      $price_formatted = $this->renderer->renderPlain($element['price']);
      $display_price =  $price_formatted->__toString();

      // Gets tax_rate label as string value.
      // @todo: Find a clean an simple way to retrieve the label.
      $field_tax_rate = $product_variation->get('field_tax_rate')[0]->getValue()['value'];
      $tax = explode('|',$field_tax_rate);
      $tax_type = $tax[0];
      $tax_rate_id = $tax[1];
      if ($data = $this->configFactory->get('commerce_tax.commerce_tax_type.' . $tax_type)) {
        foreach ($data->get('configuration')['rates'] as $rate) {
          if ($rate['id'] == $tax_rate_id) {
            $tax_rate_value = $rate['label'];
          }
        }
      }

      $title = $product_variation->getTitle();

      // Retrieves category id and name.
      $category_id = $product_variation->get('field_addon_category')[0]->getValue()['target_id'];

      // Creat's options array with structure and the variation display string with price.
      $options[$category_id][$variation_id] = $title . ' - ' . $display_price . ' inkl. ' . $tax_rate_value;

      // Saves product id's to use in submit handler.
      if (empty($this->productId)) {
        $this->productId = $product_variation->get('product_id')[0]->getValue()['target_id'];
      }
      // Saves variation entities to use in submit handler.
      if (!array_key_exists($variation_id, $this->variationEntity)) {
        $this->variationEntity[$variation_id] = $product_variation;
      }

      // Hidden field with price value for jQuery calculation.
      $form['price_variation_' . $variation_id] = [
        '#type' => 'hidden',
        '#name' => 'price_variation_' . $variation_id, // This name is used in jQuery
        '#value' => $price['number'],
        '#attributes' => [
          'class' => ['product-variation-price-value']
        ],
      ];
    }

    foreach ($options AS $category_id => $option) {
      // Retrieves taxonomy data from id.
      $term = Term::load($category_id);
      # Retrives term extra field value
      $catagory_term = $term->get('name')->getValue();
      # The only possible offset 0 by single field.
      $catagory_name = $catagory_term[0]['value'];

      $form[$category_id] = [
        '#type' => 'fieldset',
        '#title' => t($catagory_name),
        '#attributes' => [
          'class' => ['wlw-checkbox-fieldset','cat_' . $category_id],
        ],
      ];

      foreach ($option AS $variation_id => $variation_label) {
        $form[$category_id]['variation_' . $variation_id] = [
          '#type' => 'checkbox',
          '#title' => $variation_label,
          '#name' => 'variation_' . $variation_id, // This name is used in jQuery
          '#attributes' => [
            'class' => ['wlw-checkbox-products'],
          ],
        ];
      }
    }
    
    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to cart'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['button--add-to-cart'],
      ],
    ];

    $form['#attached']['library'][] = 'wlw_addon/price_calc';
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo: add form validation logic.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Gets the store and current cart.
    $product = $this->entityManager->getStorage('commerce_product')->load((int)$this->productId);
    $storeId = $product->get('stores')->getValue()[0]['target_id'];
    $store = $this->entityManager->getStorage('commerce_store')->load($storeId);
    
    // If the user has already a cart in this store, we use this one. 
    $cart = $this->cartProvider->getCart('default', $store);
    // If the user has no cart in this store, we creat an new one.
    if (!$cart) {
      $cart = $this->cartProvider->createCart('default', $store);
    }
    // Gets all form values.
    $values = $form_state->getValues();

    // Loops through all available variations.
    foreach ($this->variationEntity as $variation) {
      $variation_id = $variation->id();
        // Checks if the checkbox with the form element key is checked.
        $checked = $values['variation_' . $variation_id];
        if ($checked) {
          // Add's the product variation to the cart.
          $this->cartManager->addEntity($cart, $variation);
        }

    }
  }
}
