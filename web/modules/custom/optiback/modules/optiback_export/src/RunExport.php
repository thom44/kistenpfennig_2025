<?php

namespace Drupal\optiback_export;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Prepares complete formatted price with tax rate and label.
 */
class RunExport implements RunExportInterface {

  /**
   * The run export service.
   *
   * @var \Drupal\optiback_export\OptibackOrderExport
   */
  protected $optibackOrderExport;

  public function __construct(OptibackOrderExport $optiback_order_export) {
    $this->optibackOrderExport = $optiback_order_export;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('optiback_export.optiback_order_export')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run($logfile = '') {
    return $this->optibackOrderExport->export($logfile);
  }
}
