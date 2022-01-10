<?php

namespace Drupal\wlw_course\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\wlw_product\PriceFormatterHelperInterface;
use Drupal\wlw_product_list\ProductListBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides the order item add to cart form.
 */
class CourseAddToCartForm extends FormBase {

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
   * Keep track of how many times the form
   * is placed on a page.
   *
   * @var int
   */
  protected static $instanceId;

  /**
   * The price formatter helper service.
   *
   * @var ProductListBuilderInterface
   */
  protected $priceFormatterHelper;

  /**
   * The Constructor
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, EntityTypeManager $entity_manager, Renderer $renderer, ConfigFactory $config_factory, PriceFormatterHelperInterface $price_formatter_helper) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->entityManager = $entity_manager;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->priceFormatterHelper = $price_formatter_helper;

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
      $container->get('config.factory'),
      $container->get('wlw_product.price_formatter_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    // Creates an unique form id.
    if (empty(self::$instanceId)) {
      self::$instanceId = 1;
    }
    else {
      self::$instanceId++;
    }

    return 'wlw_course_add_to_cart_form_' . self::$instanceId;
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $variations = []) {

    // Ensure the form ID is prepared.
    // for debugging: $form_id = $this->getFormId();

    $options = [];
    // @var array The variations to be displayed depending on the remote field.
    $display = [];
    // @var array The preselect configuration of each variation.
    $preselect = [];
    // @var NULL|string the total taxrate on product level.
    $total_tax = NULL;

    if (!is_array($variations)) {
      $variations = [$variations];
    }

    // Creates hidden form instance field.
    $form['form_instance'] = [
      '#type' => 'hidden',
      '#name' => 'form_instance',
      '#value' => self::$instanceId,
      '#attributes' => [
        'class' => ['form-instance']
      ],
    ];

    // Creates DOM Element for JQuery price display.
    $form['price'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '0,00 €',
      '#attributes' => [
        'id' => 'jquery-calculated-price-' . self::$instanceId,
        'class' => [
          'field--name-price',
          'field--type-commerce-price',
        ]
      ],
    ];

    foreach ($variations AS $variation_id) {

      $category_id = 0;
      $preselect[$variation_id] = FALSE;
      $is_option = NULL;

      $product_variation = $this->entityManager->getStorage('commerce_product_variation')->load((int)$variation_id);

      $fields = $product_variation->getFields();

      // Process field options.
      if (
        isset($fields['field_is_option'])
      &&
        isset($fields['field_is_option']->getValue()[0]['value'])
      ) {
        $is_option = $fields['field_is_option']->getValue()[0]['value'];
      }

      if (
        isset($fields['field_display_dependency'])
          &&
        isset($fields['field_display_dependency']->getValue()[0]['value'])
      ) {
        // Saves display configuration of the variation.
        $display[$variation_id] = $fields['field_display_dependency']->getValue()[0]['value'];
      }

      if (
        isset($fields['field_preselect'])
        &&
        isset($fields['field_preselect']->getValue()[0]['value'])
      ) {
        // Saves preselect option configuration of the variation.
        if ($fields['field_preselect']->getValue()[0]['value'] == 1) {
          $preselect[$variation_id] = TRUE;
        }
      }

      // Gets formatted price value with currency symbol
      $element['price'] = $this->priceFormatterHelper->getFormattedPriceByVatiationId($variation_id);

      $price_formatted = $this->renderer->renderPlain($element['price']);
      $display_price =  $price_formatted->__toString();

      if (
        isset($fields['field_tax_rate'])
        &&
        !empty($fields['field_tax_rate']->getValue())
      ) {
        $tax_rate = $this->priceFormatterHelper->getFormattedTaxRate($fields['field_tax_rate']->getValue()[0]['value']);

        // We save the tax rate from the default variation for total taxrate of the product.
        // The default variation is first variation which is not a option.
        if ($total_tax === NULL && !$is_option) {
          if ($tax_rate) {
            $total_tax = $tax_rate;
          } else {
            $total_tax = $this->t('Umsatzsteuerfrei');
          }
        }
      }

      $title = $product_variation->getTitle();

      if (
        $product_variation->hasField('field_option_category')
      &&
        isset($product_variation->get('field_option_category')[0])
      ) {
        // Retrieves category id and name.
        $category_id = $product_variation->get('field_option_category')[0]->getValue()['target_id'];
      }

      $label = $title . ' - ' . $display_price;

      if ($is_option == 1) {
        // Creat's options array with structure and the variation display string with price.
        $options[$category_id][$variation_id] = $label;
      } else {

        // Add's variant as hidden field to be added to the cart.
        $form['default_variation_' . $variation_id] = [
          '#type' => 'hidden',
          '#name' => 'add_variation_' . $variation_id, // This name is used in jQuery
          '#value' => $variation_id,
          '#attributes' => [
            'class' => ['product-variation-default-value']
          ],
        ];
      }

      // Saves product id's to use in submit handler.
      if (empty($this->productId)) {
        $this->productId = $product_variation->get('product_id')[0]->getValue()['target_id'];
      }
      // Saves variation entities to use in submit handler.
      if (!array_key_exists($variation_id, $this->variationEntity)) {
        $this->variationEntity[$variation_id] = $product_variation;
      }

      // Hidden field with price value for jQuery calculation.
      $form['price_add_variation_' . $variation_id] = [
        '#type' => 'hidden',
        '#name' => 'price_add_variation_' . $variation_id, // This name is used in jQuery
        '#value' => $fields['price'][0]->getValue()['number'],
        '#attributes' => [
          'class' => ['product-variation-price-value','count-price']
        ],
      ];
    }

    // Tax rate postfix for total product price display.
    $form['tax'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $total_tax,
      '#attributes' => [
        'class' => [
          'course-tax-postfix',
        ]
      ],
    ];

    // Creates with category grouped option fields.
    foreach ($options AS $category_id => $option) {

      if ($category_id < 1) {
        continue;
      }

      $category = $this->getTermData($category_id);

      $form['category_' . $category_id] = [
        '#type' => 'fieldset',
        '#title' => t($category['name']),
        '#attributes' => [
          'class' => ['wlw-checkbox-fieldset','cat_' . $category_id],
        ],
        '#required' => $category['is_required'],
      ];

      foreach ($option AS $variation_id => $variation_label) {

        $class = ['wlw-checkbox-products'];

        // Add's display dependency options classes used in jquery.
        // @note: We can not use #states, because to set invisible checkboxes
        //   to unchecked toggles to checked if it is visible.
        // @see: https://drupal.stackexchange.com/questions/136957/undesired-toggle-effect-with-states-and-checkboxes
        if (isset($display[$variation_id])) {
          $length = strlen($display[$variation_id]);
          if ($length == 2) {
            $class[] = 'display_' . substr($display[$variation_id],0,1);
          } else {
            $class[] = 'display_option_' . $display[$variation_id];
          }
        }

        if ($category['is_required']) {
          $class[] = 'group-is-reqired';
        }

        $form['category_' . $category_id]['variation_' . $variation_id] = [
          '#type' => 'checkbox',
          '#title' => $variation_label,
          '#name' => 'variation_' . $variation_id, // This name is used in jQuery
          '#attributes' => [
            'class' => $class,
          ],
          '#default_value' =>$preselect[$variation_id],
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

    $form['#attached']['library'][] = 'wlw_course/price_calc';

    $form['#theme'] = 'wlw_course_add_to_cart_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $required_categories = [];

    // Gets the category required configuration.
    // @note: We have to implement required group functionality in this custom
    // way, because checkboxes can not be used with logic in jQuery.
    foreach ($form as $key => $value) {
      // Checks if it is a category.
      if (strpos($key,'category_') !== FALSE) {
        // Checks if the category is required.
        if ($form[$key]['#required'] == 1) {
          // Gets the variations of the required category.
          foreach ($form[$key] as $variation_id => $variation_values) {
            if (strpos($variation_id,'variation_') !== FALSE) {

              // Saves the values of the variations in the required category.
              $required_categories[$key][] = $values[$variation_id];
            }
          }
        }
      }
    }

    // Checks if all required categories hat at least on checked variation.
    foreach ($required_categories as $category => $variation) {

      $has_checked_variations = FALSE;

      foreach ($variation as $value) {
        if ($value == 1) {
          $has_checked_variations = TRUE;
        }
      }

      if ($has_checked_variations === FALSE) {
        // Gets term data from term id.
        $category_data = $this->getTermData(substr($category,9));

        $form_state->setErrorByName($category, $this->t('In group @category must at leas be on option selected.',['@category' => $category_data['name']]));
      }
    }
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
    $cart = $this->cartProvider->getCart('course', $store);
    // If the user has no cart in this store, we creat an new one.
    if (!$cart) {
      $cart = $this->cartProvider->createCart('course', $store);
    }
    // Gets all form values.
    $values = $form_state->getValues();

    // Loops through all available variations.
    foreach ($this->variationEntity as $variation) {
      $variation_id = $variation->id();

      // Add's the default variations to the cart.
      if (isset($values['default_variation_' . $variation_id])) {
        $this->cartManager->addEntity($cart, $variation);
      }

      // Checks if the variation is an option and is checked.
      if (
        isset($values['variation_' . $variation_id])
      &&
        $values['variation_' . $variation_id]
      ) {
        // Add's the product variation options to the cart.
        $this->cartManager->addEntity($cart, $variation);
      }

    }
  }

  /**
   * Retrieves the term field data from tid.
   *
   * @param $tid The term id.
   *
   * @return bool|array if term field data.
   */
  protected function getTermData($tid) {

    $data = [];
    $data['is_required'] = FALSE;

    if ($tid < 1) {
      return FALSE;
    }

    if ($term = Term::load($tid)) {

      $data['name'] = $term->get('name')->getValue()[0]['value'];

      if (
        $term->hasField('field_required')
        &&
        isset($term->get('field_required')->getValue()[0]['value'])
      ) {
        $data['is_required'] = $term->get('field_required')->getValue()[0]['value'];
      }

    }

    return $data;
  }
}
