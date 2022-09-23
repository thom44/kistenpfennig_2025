<?php

namespace Drupal\optiback_import\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field value destination plugin.
 *
 * @MigrateDestination(
 *   id = "field_value"
 * )
 */
class FieldValue extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Config destination object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entityTypeManager = $entityTypeManager;
    $this->supportsRollback = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $entityType = $row->getDestinationProperty('entity_type');
    $entityId = $row->getDestinationProperty('entity_id');
    $fieldName = $row->getDestinationProperty('field_name');
    $value = $row->getDestinationProperty('value');
    $valueHash = sha1(\serialize($value));

    if ($entityId === false) {
      // @todo: Add log message. SKU doesn't exist.
      return NULL;
    }

    $entity = $this->entityTypeManager
      ->getStorage($entityType)
      ->load($entityId);
    $currentValue = $entity->get($fieldName)->getValue();

    $long_field = [
      'field_ingredient',
      'field_allergene',
      'body'
      ];
    if (in_array($fieldName,$long_field)) {
      $currentValue[0]['format'] = 'full_html';
    }
    // Encodes all characters in Value.
    $currentValue[0]['value'] = utf8_encode($value);

    $entity->get($fieldName)->setValue($currentValue);

    $entity->save();

    return [
      $entityType,
      $entityId,
      $fieldName,
      $valueHash,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // @todo Define the fields available on destination.
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    $ids['entity_id']['type'] = 'string';
    $ids['field_name']['type'] = 'string';
    $ids['value_hash']['type'] = 'string';
    return $ids;
  }

  /**
   * Get whether this destination is for translations.
   *
   * @return bool
   *   Whether this destination is for translations.
   */
  protected function isTranslationDestination() {
    return !empty($this->configuration['translations']);
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    // @todo Remove value from entity on rollback using the hash stored.
  }

}
