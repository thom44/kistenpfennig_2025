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
 *       plugin: process_custom_unit
 *       source: custom_unit
 *
 * @endcode
 *
 * @see DrupalmigratePluginMigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "process_custom_unit"
 * )
 */
class ProcessCustomUnit extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $d8_value = NULL;

    // Left Optiback => Right Drupal.
    $unit_map = [
      'MB' => 'mg',
      'LB' => 'lb',
      'g'  => 'g',
      'Kg' => 'kg',
      'OZ' => 'oz'
    ];

    if (isset($value) && $value != NULL) {
      $d8_value = $unit_map[$value];
    }

    return $d8_value;
  }
}
