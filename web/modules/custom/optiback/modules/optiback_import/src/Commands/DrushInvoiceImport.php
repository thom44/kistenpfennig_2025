<?php

namespace Drupal\optiback_import\Commands;

use Drush\Commands\DrushCommands;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\optiback_import\ProcessInvoiceInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 * @see https://www.axelerant.com/blog/how-to-write-custom-drush-9-commands-for-drupal-8
 */
class DrushInvoiceImport extends DrushCommands {

  /**
   * The run export service.
   *
   * @var \Drupal\optiback_import\ProcessInvoice
   */
  protected $processInvoice;

  public function __construct(ProcessInvoiceInterface $process_invoice) {
    $this->processInvoice = $process_invoice;
  }

  /**
   * Copys all pdf invoices from optiback to private file folder.
   *
   * @command optiback_import:copy_invoice
   * @aliases copy_invoice
   * @usage copy_invoice
   */
  public function run() {

    // Process invoices
    $output = $this->processInvoice->run();

    $this->output()->writeln($output);
  }

}
