<?php

namespace Drupal\optiback_import\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 *
 * @code
 * process:
 *   process_price_field:
 *     -
 *       plugin: optiback_price_format
 *       source: csv
 *
 * @endcode
 *
 * @see DrupalmigratePluginMigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "optiback_price_format"
 * )
 */
class OptibackPriceFormat extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $d8_value = NULL;

    if (isset($value) && $value != NULL) {
      // Transfoms price to float with decimal dot.
      $value = str_replace(',', '.', $value);
      $d8_value = floatval($value);
    }

    return $d8_value;
  }
}
