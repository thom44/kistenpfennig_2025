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
   * @param string $env
   *  The the environmant dev|prod (Optional).
   * @param bool $drush_option
   *   The option for import product|invoice|tracking or NULL for all (Optional).
   *
   * @command optiback_import:run_import
   * @aliases run_import
   * @usage run_import dev invoice
   *   Run import on local mashine
   */
  public function run($env = 'prod', $drush_option = NULL) {

    // Sets all options to true.
    $options = [
      'product' => TRUE,
      'invoice' => TRUE,
      'tracking' => TRUE
    ];

    if ($drush_option !== NULL) {

      foreach ($options as $key => $option) {
        if ($key == $drush_option) {
          // Sets selected option to true.
          $options[$key] = TRUE;
        } else {
          // Sets others to false.
          $options[$key] = FALSE;
        }
      }
    }

    $output = $this->runImport->run($env, $options);

    $this->output()->writeln($output);
  }

}
