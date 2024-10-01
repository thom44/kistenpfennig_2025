<?php

namespace Drupal\optiback_export;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\custom_mail_ui\MailHelperInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\optiback\OptibackLoggerInterface;

class OptibackCancelOrder {

  use StringTranslationTrait;

  /**
   * An instance of the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail helper.
   *
   * @var \Drupal\custom_mail_ui\MailHelperInterface
   */
  protected $mailHelper;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger;
   */
  protected $logger;

  /**
   * The optiback logger service.
   *
   * @var \Drupal\optiback\OptibackLoggerInterface
   */
  protected $optibackLogger;

  /**
   * The run export service.
   *
   * @var \Drupal\optiback_export\OptibackOrderExport
   */
  protected $optibackOrderExport;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    MailHelperInterface $mail_helper,
    LoggerChannelFactoryInterface $logger,
    OptibackLoggerInterface $optiback_logger,
    OptibackOrderExport $optiback_order_export
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->mailHelper = $mail_helper;
    $this->logger = $logger;
    $this->optibackLogger = $optiback_logger;
    $this->optibackOrderExport = $optiback_order_export;
  }

  /**
   * Export a CSV of data.
   */
  public function cancelOrder() {

    // First we remove all csv export files, to export only new ones.
    //array_map('unlink', glob(ObtibackConfigInterface::OPTIBACK_IN . '/*.csv'));

    $error = FALSE;
    $result = NULL;

    // The csv {order_id}_{status}.csv file header.
    $header = [
      'Bestell-Nr.',
      'Status',
    ];

    $states = [
      'canceled'
    ];

    $orders = $this->entityTypeManager
      ->getStorage('commerce_order')
      ->loadByProperties(
        [
          'state' => $states
        ]
      );

    foreach ($orders as $order) {

      if (!$order instanceof OrderInterface) {
        continue;
      }

      $export = $order->get('field_export')->getValue()[0]['value'];

      // Process only already exported orders.
      if (!$export) {
        continue;
      }

      // Checks if the cancel state is already exported.
      if ($field_state_exported = $order->get('field_state_exported')->getValue()) {
        if ($field_state_exported[0]['value'] == 1) {
          continue;
        }
      }

      $order_id = $order->id();

      // Builds csv data.
      $data[0] = [
        'order_id' => $order_id,
        'state' => 'canceled',
      ];

      $filename = ObtibackConfigInterface::OPTIBACK_CANCEL . '/' . $order_id . '_canceled.csv';

      // Save file AuKopf_{oid}.csv file in "in" directory.
      $result = $this->optibackOrderExport->createCsvFile($header, $data, $filename);

      if (!$result) {
        $error = $this->t('The csv File :file could not be created.',[':file'=>$filename]);
      }

      // Marks this canceled state as exported.
      $order->set('field_state_exported',1);

      $order->save();
    }

    if ($error) {
      $this->logger->get('optiback_export')->error($error);

      $this->optibackLogger->addLog($error, 'error');
    }

    return $result;
  }
}
