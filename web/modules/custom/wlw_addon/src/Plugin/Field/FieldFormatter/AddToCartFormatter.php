<?php

namespace Drupal\wlw_addon\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Renderer;

/**
 * Plugin implementation of the 'addon_commerce_add_to_cart' formatter.
 *
 * @FieldFormatter(
 *   id = "addon_commerce_add_to_cart",
 *   label = @Translation("WLW Multible Product add to cart form"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class AddToCartFormatter extends FormatterBase {

  /**
   * @var $formBuilder Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * @var $renderer
   */
  protected $renderer;

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FormBuilder $form_builder, Renderer $renderer) {
      parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
      $this->formBuilder = $form_builder;
      $this->renderer = $renderer;

  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('form_builder'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritDoc}
   */
  public static function defaultSettings() {
    return [
      'combine' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $product_variations = [];

    foreach ($items as $delta => $item) {
      $product_variations[] = $item->target_id;
    }

    // Loads custom add to cart form for addon products.
    $addToCartForm = $this->formBuilder->getForm('Drupal\wlw_addon\Form\AddOnProductAddToCartForm', $product_variations);

    $elements[0]['add_to_cart_form'] = $addToCartForm;

    return $elements;
  }

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $has_cart = \Drupal::moduleHandler()->moduleExists('commerce_cart');
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $has_cart && $entity_type == 'commerce_product' && $field_name == 'variations';
  }

}
