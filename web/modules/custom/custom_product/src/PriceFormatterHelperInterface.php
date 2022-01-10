<?php

namespace Drupal\custom_product;

/**
 * Retrieves Date for Product List.
 */
interface PriceFormatterHelperInterface {

  /**
   * Gets the complete formatted Price By variation_id.
   *
   * @param $variation_id
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFormattedPriceByVatiationId($variation_id);

  /**
   * Gets the complete formatted Price By variation object.
   *
   * @param $product_variation
   * @return string
   */
  public function getFormattedPriceByVatiation($product_variation);

  /**
   * Gets formatted price by amount value.
   *
   * @param $amount
   * @param $markup (optional) TRUE|FALSE
   *   TRUE returns price in html markup.
   * @return string
   */
  public function getFormattedPriceByAmount($amount, $markup = TRUE);

  /**
   * Gets tax rate with label from raw tax rate field value.
   *
   * @param $field_tax_rate
   * @return string|null
   */
  public function getFormattedTaxRate($field_tax_rate);

}
