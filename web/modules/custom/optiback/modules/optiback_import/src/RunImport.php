<?php

namespace Drupal\optiback_import;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\optiback_import\ProcessInvoiceInterface;
use Drupal\optiback_import\ProcessTrackingNumberInterface;

/**
 * Prepares complete formatted price with tax rate and label.
 */
class RunImport implements RunImportInterface {

  /**
   * The process Invoice service.
   *
   * @var \Drupal\optiback_import\ProcessInvoice
   */
  protected $processInvoice;

  /**
   * The process tracking number service.
   *
   * @var \Drupal\optiback_import\ProcessTrackingNumberInterface
   */
  protected $processTrackingNumber;

  public function __construct(
    ProcessInvoiceInterface $process_invoice,
    ProcessTrackingNumberInterface $process_tracking_number
  ) {
    $this->processInvoice = $process_invoice;
    $this->processTrackingNumber = $process_tracking_number;

  }

  /**
   * {@inheritdoc}
   */
  public function run($logfile = '') {

    // Here we run the complete import pipeline.

    $message = '';

    $backup_dir = ObtibackConfigInterface::OPTIBACK_BAK;
    $db_user = ObtibackConfigInterface::DB_USER;
    $db_name = ObtibackConfigInterface::DB_NAME;
    $db_pwd = ObtibackConfigInterface::DB_PWD;
    $drush = ObtibackConfigInterface::DRUSH;

    // Run database backup.
    $cmd = 'mysqldump -u ' . $db_user . ' -p' . $db_pwd . ' ' . $db_name . ' > ' . $backup_dir . '/' . $db_name . '_' . date("Y-m-d") . '.sql';

    $message .= $this->shellExecWithError($cmd, 'The mysqldump failed.');

    // Sets site in maintance mode.
    $cmd = $drush . ' state:set system.maintenance_mode 1';

    $message .= $this->shellExecWithError($cmd, 'The site could not set to maintenance_mode 1.');

    // Sets all products to status 0 = unpublished.
    // We use direct query for performance reason.
    $query = \Drupal::database()->update('commerce_product_field_data');
    $query->fields([
      'status' => 0
    ]);
    $query->condition('status', 1);
    #$query->execute();

    /*
     * The way per entityQuery.
    $pids = \Drupal::entityQuery('commerce_product')->condition('status', 1)->execute();
    $products = Product::loadMultiple($pids);
    foreach ($products as $product) {
      $product->status = 0;
      $product->save();
    }

    $pvids = \Drupal::entityQuery('commerce_product_variation')->condition('status', 1)->execute();
    $product_variations = ProductVariation::loadMultiple($pvids);
    foreach ($product_variations as $product_var) {
      $product_var->status = 0;
      $product_var->save();
    }
    */

    // Run product variation migration.
    $cmd = $drush . ' migrate:import optiback_import_product_variation --update';

    #$message .= $this->shellExecWithError($cmd, 'The migration optiback_import_product_variation failed.');

    // Runs product migration.
    $cmd = $drush . ' migrate:import optiback_import_product --update';

    #$message .= $this->shellExecWithError($cmd, 'The migration optiback_import_product failed.');

    // Copy and process new invoices.
    #$message .= $this->processInvoice->run();

    // Import tracking numbers.
    #$message .= $this->processTrackingNumber->run();

    // Removes maintance mode.
    $cmd = $drush . ' state:set system.maintenance_mode 0';

    $message .= $this->shellExecWithError($cmd, 'The site could not set to maintenance_mode 0.');

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  protected function shellExecWithError($cmd, $message) {

    $result = exec($cmd);

    if (
      strpos($result,"error") !== FALSE
      ||
      strpos($result,"failed") !== FALSE
    ) {
      return $message . "\n". $result . "\n";
    }

    return $result . "\n";
  }
}
