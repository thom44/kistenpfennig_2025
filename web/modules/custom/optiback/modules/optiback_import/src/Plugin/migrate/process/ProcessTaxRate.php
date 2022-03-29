<?php

namespace Drupal\optiback_import\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\optiback\ObtibackConfigInterface;

/**
 *
 * @code
 * process:
 *   field_tax:
 *     -
 *       plugin: process_tax_rate
 *       source: custom_tax
 *
 * @endcode
 *
 * @see DrupalmigratePluginMigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "process_tax_rate"
 * )
 */
class ProcessTaxRate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $d8_value = NULL;

    // Left Optiback => Right Drupal.
    $tax_map = [
      '19' => ObtibackConfigInterface::DRUPAL_TAX_DE_19,
      '7' => ObtibackConfigInterface::DRUPAL_TAX_DE_7,
    ];
    if (isset($value) && $value != NULL) {
      foreach ($tax_map as $key => $drupal_tax_rate) {
        // @todo: Check optiback format for tax rate.
        if ($value == $key) {
          $d8_value = $drupal_tax_rate;
        }
      }
    }

    return $d8_value;
  }
}
