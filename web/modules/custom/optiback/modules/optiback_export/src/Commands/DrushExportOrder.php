<?php

namespace Drupal\optiback_export\Commands;

use Drupal\optiback_export\RunExportInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 * @see https://www.axelerant.com/blog/how-to-write-custom-drush-9-commands-for-drupal-8
 */
class DrushExportOrder extends DrushCommands {

  /**
   * The run export service.
   *
   * @var \Drupal\optiback_export\RunExportInterface
   */
  protected $runExport;

  public function __construct(RunExportInterface $run_export) {
    $this->runExport = $run_export;
  }

  /**
   * Run's a csv export of commerce orders.
   *
   * @param string $env
   *   The environment the command is executed. dev|prod
   *
   * @command optiback_export:run_export
   * @aliases run_export
   * @usage run_export dev|prod
   */
  public function export($env = 'prod') {

      $out =  $this->runExport->run($env);

      $this->output()->writeln('Export started.' . $out);
  }

}
