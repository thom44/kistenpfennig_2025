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
   * @param string $logfile
   *   The logfile of the import.
   *
   * @command optiback_export:export
   * @aliases optex
   * @options test Whether or not an extra message should be displayed to the user.
   * @usage optex --test
   *   Display 'Hello Akanksha!' and a message.
   */
  public function export($logfile, $options = ['test' => FALSE]) {
    if ($options['test']) {
      // Test only code.
      $this->output()->writeln('This is only a test run.');
    }
    else {
      $out =  $this->runExport->run($logfile);
      $this->output()->writeln('Export started.' . $out);
    }
  }

}
