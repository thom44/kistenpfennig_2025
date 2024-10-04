<?php

namespace Drupal\optiback_import\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 *
 * @code
 * process:
 *   field_unit:
 *     -
 *       plugin: process_weight_number
 *       source: custom_unit
 *
 * @endcode
 *
 * @see DrupalmigratePluginMigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "process_weight_float"
 * )
 */
class ProcessWeightFloat extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $d8_value = NULL;

    if (isset($value) && $value != NULL) {
      // Transfoms weight to float format decimal with dot.
      $value = str_replace(',', '.', $value);
      $d8_value = floatval($value);
      // Alternative #$d8_value = number_format($value, 6,".","");
      //$d8_value = floatval($d8_value);
      //$d8_value = 5.55;
    }

    return $d8_value;
  }
}
