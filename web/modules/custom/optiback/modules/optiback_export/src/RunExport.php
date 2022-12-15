<?php

namespace Drupal\optiback_export;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\optiback\OptibackHelperInterface;
use Drupal\optiback\OptibackLoggerInterface;

/**
 * Prepares complete formatted price with tax rate and label.
 */
class RunExport implements RunExportInterface {

  use StringTranslationTrait;

  /**
   * The run export service.
   *
   * @var \Drupal\optiback_export\OptibackOrderExport
   */
  protected $optibackOrderExport;

  /**
   * The run cancel order service.
   *
   * @var \Drupal\optiback_export\OptibackCancelOrder
   */
  protected $optibackCancelOrder;

  /**
   * The optiback helper service.
   *
   * @var \Drupal\optiback\OptibackHelperInterface
   */
  protected $optibackHelper;

  /**
   * The optiback messenger service.
   *
   * @var \Drupal\optiback\OptibackLoggerInterface
   */
  protected $optibackmessenger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryr $logger;
   */
  protected $logger;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    OptibackOrderExport $optiback_order_export,
    OptibackHelperInterface $optiback_helper,
    OptibackLoggerInterface $optiback_messenger,
    LoggerChannelFactory $logger,
    OptibackCancelOrder $optiback_cancel_order
  ) {
    $this->optibackOrderExport = $optiback_order_export;
    $this->optibackHelper = $optiback_helper;
    $this->optibackmessenger = $optiback_messenger;
    $this->logger = $logger;
    $this->optibackCancelOrder = $optiback_cancel_order;
  }

  /**
   * {@inheritdoc}
   */
  public function run($env = 'prod') {

    $message = '';

    // Here we run the complete export pipeline.

    // @note: Optiback save this file only during processing orders.
    $filename = ObtibackConfigInterface::OPTIBACK_IN . '/optimo.txt';

    if (file_exists($filename)) {
      // Skip the process to prevent conflicts with optiback.
      $message .= $this->t('The optiback export was skipped because ' . $filename  . ' is in in-directiory.');
      $this->logger->get('optiback_export')->warning($message);
      return $message;
    }

    // Backup /in directory
    $message .= $this->optibackHelper->dirBackup(ObtibackConfigInterface::OPTIBACK_IN, 'OPTIBACK_IN');

    $drush = ObtibackConfigInterface::DRUSH;

    if ($env != 'dev') {
      $message .= $this->optibackHelper->dbBackup();
    }

    // Sets site in mainetance mode. - Skip this for now.
    // $cmd = $drush . ' state:set system.maintenance_mode 1';
    // $message .= $this->optibackHelper->shellExecWithError($cmd, 'The site could not set to maintenance_mode 1.');

    // Runs the export.
    $this->optibackOrderExport->export();

    // Search for canceled orders and create {order_id}_calceled.csv
    // This is working but not in use for now.
    // @see also Drupal\optiback_import\RunImport
    // $message .= $this->optibackCancelOrder->cancelOrder();

    // Removes maintance mode.
    // $cmd = $drush . ' state:set system.maintenance_mode 0';
    // $message .= $this->optibackHelper->shellExecWithError($cmd, 'The site could not set to maintenance_mode 0.');

    $message .= $this->optibackHelper->backupCleanup();

    $this->optibackmessenger->addLog($message, 'status');

    $params = [
      'subject' => 'Drupal Optiback Export',
      'body' => 'Meldungen beim Drupal Export<br>' . $message,
    ];

    $mail = $this->optibackmessenger->sendMail($params);

    if ($mail) {
      $message .= $this->t('The optiback export email was send to the site owner.');
      $this->logger->get('optiback_export')->info($message);
    } else {
      $message .= $this->t('The optiback export email could not be send to the site owner.');
      $this->logger->get('optiback_export')->error($message);
    }

    return $message;
  }
}
