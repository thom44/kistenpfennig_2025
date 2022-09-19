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
 *       plugin: process_field_sort
 *       source: custom_tax
 *
 * @endcode
 *
 * @see DrupalmigratePluginMigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "process_title"
 * )
 */
class ProcessTitle extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $d8_value = NULL;

    if (isset($value) && $value != NULL) {
      if (is_numeric($value)) {
        $d8_value = intval($value);
      }
    }

    return $d8_value;
  }
}
