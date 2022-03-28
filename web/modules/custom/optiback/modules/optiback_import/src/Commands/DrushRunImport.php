<?php

namespace Drupal\optiback_import\Commands;

use Drush\Commands\DrushCommands;
use Drupal\optiback_import\RunImportInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 * @see https://www.axelerant.com/blog/how-to-write-custom-drush-9-commands-for-drupal-8
 */
class DrushRunImport extends DrushCommands {

  /**
   * The run export service.
   *
   * @var \Drupal\optiback_import\RunImportInterface
   */
  protected $runImport;

  public function __construct(RunImportInterface $run_import) {
    $this->runImport = $run_import;
  }

  /**
   * Run's the complete import process.
   *
   * @command optiback_import:run_import
   * @aliases run_import
   * @usage run_import
   */
  public function run() {

    // Process invoices
    $output = $this->runImport->run();

    $this->output()->writeln($output);
  }

}
