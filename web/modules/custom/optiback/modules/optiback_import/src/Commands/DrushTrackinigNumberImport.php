<?php

namespace Drupal\optiback_import\Commands;

use Drush\Commands\DrushCommands;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\optiback_import\ProcessTrackingNumberInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 * @see https://www.axelerant.com/blog/how-to-write-custom-drush-9-commands-for-drupal-8
 */
class DrushTrackinigNumberImport extends DrushCommands {

  /**
   * The tracking number process service.
   *
   * @var \Drupal\optiback_import\ProcessTrackingNumberInterface
   */
  protected $processTrackingNumber;

  public function __construct(ProcessTrackingNumberInterface $process_tracking_number) {
    $this->processTrackingNumber = $process_tracking_number;
  }

  /**
   * @return void
   */
  public function run() {

    // Process invoices
    $output = $this->processTrackingNumber->run();

    $this->output()->writeln($output);
  }

}
