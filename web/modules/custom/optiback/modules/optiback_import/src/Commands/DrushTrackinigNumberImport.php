<?php

namespace Drupal\optiback_import\Commands;

use Drush\Commands\DrushCommands;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\optiback_import\processTrackingNumberInterface;

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
   * @var \Drupal\optiback_import\processTrackingNumber
   */
  protected $processTrackingNumber;

  public function __construct(processTrackingNumberInterface $process_tracking_number) {
    $this->processTrackingNumber = $process_tracking_number;
  }

  /**
   * Copys all pdf invoices from optiback to private file folder.
   *
   * @command optiback_import:tracking_number
   * @aliases tracking_number
   * @usage tracking_number
   */
  public function run() {

    // Process invoices
    $output = $this->processTrackingNumber->run();

    $this->output()->writeln($output);
  }

}
