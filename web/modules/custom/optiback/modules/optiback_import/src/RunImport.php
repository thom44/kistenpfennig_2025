<?php

namespace Drupal\optiback_import;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\optiback\OptibackHelperInterface;
use Drupal\optiback\OptibackLoggerInterface;

/**
 * Prepares complete formatted price with tax rate and label.
 */
class RunImport implements RunImportInterface {

  use StringTranslationTrait;

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

  /**
   * The optiback helper service.
   *
   * @var \Drupal\optiback\OptibackHelperInterface
   */
  protected $optibackHelper;

  /**
   * The optiback logger service.
   *
   * @var \Drupal\optiback\OptibackLoggerInterface
   */
  protected $optibackLogger;

  /**
   * The logger service.
   *
   * @var Drupal\Core\Logger\LoggerChannelFactoryInterface $logger;
   */
  protected $logger;

  /**
   * The process Credit service.
   *
   * @var \Drupal\optiback_import\ProcessCreditInterface
   */
  protected $processCredit;


  public function __construct(
    ProcessInvoiceInterface $process_invoice,
    ProcessTrackingNumberInterface $process_tracking_number,
    OptibackHelperInterface $optiback_helper,
    OptibackLoggerInterface $optiback_logger,
    LoggerChannelFactoryInterface $logger,
    ProcessCreditInterface $process_credit
  ) {
    $this->processInvoice = $process_invoice;
    $this->processTrackingNumber = $process_tracking_number;
    $this->optibackHelper = $optiback_helper;
    $this->optibackLogger = $optiback_logger;
    $this->logger = $logger;
    $this->processCredit = $process_credit;
  }

  /**
   * {@inheritdoc}
   */
  public function run($env = 'prod', $options = ['product' => TRUE,'invoice' => TRUE,'tracking' => TRUE]) {

    // Here we run the complete import pipeline.

    $message = '';

    // Backup /in directory
    $message .= $this->optibackHelper->dirBackup(ObtibackConfigInterface::OPTIBACK_OUT, 'OPTIBACK_OUT');


    if ($env == 'dev') {
      $drush = ObtibackConfigInterface::DEV_DRUSH;
    } else {
      $drush = ObtibackConfigInterface::DRUSH;
    }

    $message .= $this->optibackHelper->dbBackup($env);

    if ($options['product']) {

      // Sets site in maintance mode, only for migration.
      $cmd = $drush . ' state:set system.maintenance_mode 1';

      $message .= $this->optibackHelper->shellExecWithError($cmd, 'The site could not set to maintenance_mode 1.');

      // Sets all products to status 0 = unpublished.
      // We use direct query for performance reason.
      $query = \Drupal::database()->update('commerce_product_field_data');
      $query->fields([
        'status' => 0
      ]);
      $query->condition('status', 1);
      $query->execute();

      // Run product variation migration.
      $cmd = $drush . ' migrate:import optiback_import_product_variation --update';

      $message .= $this->optibackHelper->shellExecWithError($cmd, 'The migration optiback_import_product_variation failed.');

      // Runs product migration.
      $cmd = $drush . ' migrate:import optiback_import_product --update';

      $message .= $this->optibackHelper->shellExecWithError($cmd, 'The migration optiback_import_product failed.');

      // Removes maintance mode.
      $cmd = $drush . ' state:set system.maintenance_mode 0';

      $message .= $this->optibackHelper->shellExecWithError($cmd, 'The site could not set to maintenance_mode 0.');

    }

    if ($options['invoice']) {
      // Copy and process new invoices.
      $message .= $this->processInvoice->run();

      // Copy and process new credits.
      $message .= $this->processCredit->run();
    }

    if ($options['tracking']) {
      // Import tracking numbers.
      $message .= $this->processTrackingNumber->run();
    }

    $this->optibackLogger->addLog($message, 'status');

    $params = [
      'subject' => 'Drupal Optiback Import',
      'body' => 'Meldungen beim Drupal Import<br>' . $message,
    ];

    $mail = $this->optibackLogger->sendMail($params);

    if ($mail) {
      $message .= $this->t('The optiback import email was send to the site owner.');
      $this->logger->get('optiback_import')->info($message);
    } else {
      $message .= $this->t('The optiback import email could not be send to the site owner.');
      $this->logger->get('optiback_import')->error($message);
    }

    $message .= $this->optibackHelper->backupCleanup();

    return $message;
  }
}
