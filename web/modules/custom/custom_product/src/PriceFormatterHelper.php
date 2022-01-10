<?php

namespace Drupal\custom_product;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Render\Renderer;

/**
 * Prepares complete formatted price with tax rate and label.
 */
class PriceFormatterHelper implements PriceFormatterHelperInterface {

  /**
   * The EntityTypeManager provider.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var $renderer
   */
  protected $renderer;

  /**
   * The config factory service.
   *
   * @var $configFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entity_manager, Renderer $renderer, ConfigFactory $config_factory) {
    $this->entityTypeManager = $entity_manager;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormattedPriceByVatiationId($variation_id) {

    $product_variation = $this->entityTypeManager->getStorage('commerce_product_variation')->load((int)$variation_id);

    return $this->getFormattedPriceByVatiation($product_variation);
  }

  /**
   * {@inheritDoc}
   */
  public function getFormattedPriceByVatiation($product_variation) {

    $tax_rate_value = NULL;

    $fields = $product_variation->getFields();

    // Gets formatted price value with currency symbol
    $price = $fields['price'][0]->getValue();

    $display_price = $this->getFormattedPriceByAmount($price);

    // Gets tax_rate label as string value.
    $field_tax_rate = $product_variation->get('field_tax_rate')[0]->getValue()['value'];

    $tax_rate_value = $this->getFormattedTaxRate($field_tax_rate);

    return [
      '#markup' => $display_price . $tax_rate_value,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormattedPriceByAmount($amount, $markup = TRUE) {

    if (empty($amount)) {
      return NULL;
    }

    $price = [
      '#type' => 'inline_template',
      '#template' => '{{ price|commerce_price_format }}',
      '#context' => [
        'price' => $amount,
      ],
      '#attributes' => [
        'class' => 'formatted-price',
      ]
    ];

    $price_formatted = $this->renderer->renderPlain($price)->__toString();

    if ($markup === TRUE) {

      $element['price'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $price_formatted,
        '#attributes' => [
          'class' => 'formatted-price',
        ]
      ];

    } else {
      $element['price'] = [
        '#markup' => $price_formatted,
      ];
    }

    $price_formatted = $this->renderer->renderPlain($element['price']);

    return $price_formatted->__toString();
  }

  /**
   * {@inheritDoc}
   */
  public function getFormattedTaxRate($field_tax_rate) {

    $tax_rate_value = NULL;

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

    if (!$tax_rate_value) {
      return NULL;
    }

    $tax_rate = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => ' inkl. ' . $tax_rate_value,
      '#attributes' => [
        'class' => 'tax-rate',
      ]
    ];

    $tax_formatted = $this->renderer->renderPlain($tax_rate);

    return $tax_formatted->__toString();
  }
}
