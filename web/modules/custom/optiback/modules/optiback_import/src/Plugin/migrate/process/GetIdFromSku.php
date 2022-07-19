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
 *   id = "get_id_from_sku"
 * )
 */
class GetIdFromSku extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $d8_value = NULL;

    if (isset($value) && $value != NULL) {

      // Switch to default database
      \Drupal\Core\Database\Database::setActiveConnection('default');
      $db = \Drupal\Core\Database\Database::getConnection();

      $query = $db->select('commerce_product_variation_field_data', 'vfd');
      $query->fields('vfd', [
        'product_id'
      ]);
      $query->condition('vfd.sku', $value);
      $d8_value = $query->execute()->fetchField();
    }
    // Returns product_id as entity_id.
    return $d8_value;
  }
}
