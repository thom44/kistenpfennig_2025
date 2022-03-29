<?php

namespace Drupal\optiback_export;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
   * {@inheritDoc}
   */
  public function __construct(
    OptibackOrderExport $optiback_order_export,
    OptibackHelperInterface $optiback_helper,
    OptibackLoggerInterface $optiback_logger,
    LoggerChannelFactoryInterface $logger
  ) {
    $this->optibackOrderExport = $optiback_order_export;
    $this->optibackHelper = $optiback_helper;
    $this->optibackLogger = $optiback_logger;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function run($env = 'prod') {

    // Here we run the complete export pipeline.

    $message = '';

    $drush = ObtibackConfigInterface::DRUSH;

    if ($env != 'dev') {
      $message .= $this->optibackHelper->dbBackup();
    }

    // Sets site in maintance mode.
    $cmd = $drush . ' state:set system.maintenance_mode 1';

    $message .= $this->optibackHelper->shellExecWithError($cmd, 'The site could not set to maintenance_mode 1.');

    // Runs the import.
    $message .= $this->optibackOrderExport->export();

    // Removes maintance mode.
    $cmd = $drush . ' state:set system.maintenance_mode 0';

    $message .= $this->optibackHelper->shellExecWithError($cmd, 'The site could not set to maintenance_mode 0.');

    $this->optibackLogger->addLog($message, 'status');

    $params = [
      'subject' => 'Drupal Optiback Export',
      'body' => 'Meldungen beim Drupal Export<br>' . $message,
    ];

    $mail = $this->optibackLogger->sendMail($params);

    if ($mail) {
      $message = $this->t('The optiback import email was send to the site owner.');
      $this->logger->get('optiback_export')->error($message);
    } else {
      $message = $this->t('The optiback import email could not be send to the site owner.');
      $this->logger->get('optiback_export')->error($message);
    }

    return $message;
  }
}
