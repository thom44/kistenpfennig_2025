<?php
/**
 * @file: A class wich process the imported invoices.
 */

namespace Drupal\optiback_import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_mail_ui\MailHelperInterface;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\custom_order_token\OrderTokenProvider;
use Drupal\optiback\OptibackHelperInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use function PHPUnit\Framework\assertInstanceOf;


class ProcessTrackingNumber implements ProcessTrackingNumberInterface {

  use StringTranslationTrait;

  /**
   * The entityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The order token provider service.
   *
   * @var \Drupal\custom_order_token\OrderTokenProvider
   */
  protected $orderTokenProvider;

  /**
   * The mail helper.
   *
   * @var \Drupal\custom_mail_ui\MailHelperInterface
   */
  protected $mailHelper;

  /**
   * The optiback helper service.
   *
   * @var \Drupal\optiback\OptibackHelperInterface
   */
  protected $optibackHelper;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger,
    OrderTokenProvider $order_token_provider,
    MailHelperInterface $mail_helper,
    OptibackHelperInterface $optiback_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->orderTokenProvider = $order_token_provider;
    $this->mailHelper = $mail_helper;
    $this->optibackHelper = $optiback_helper;
  }

  /**
   * {@inheritDoc}
   */
  public function run() {

    $message = '';

    $tno_files = array_diff(scandir(ObtibackConfigInterface::OPTIBACK_TRACKING), array('..', '.'));

    foreach ($tno_files as $tno_file) {

      $file_path = ObtibackConfigInterface::OPTIBACK_TRACKING . '/' . $tno_file;

      $file_part = str_replace("SEND_","", $tno_file);

      $order_id = str_replace(".csv","", $file_part);

      if (is_numeric($order_id)) {

        /* @var \Drupal\commerce\Entity\OrderInterface */
        $order = $this->entityTypeManager->getStorage('commerce_order')->load($order_id);

        if (!$order instanceof OrderInterface) {
          \Drupal::logger('optiback_import')->error('Order with ID @id is not a valid order entity.', ['@id' => $order_id]);
          return; // Exit the method early if the order is invalid.
        }

        // Checks if the order has already a tracking number.
        if ($field_tracking_number = $order->get('field_tracking_number')->getValue()) {
          if ($field_tracking_number[0]['value']) {
            // If field_tracking_number has already content, we skip the process
            // and add a logging message.
            $message .= $this->t('The file :file could not be updated, because a tracking number exists already in order :order_id.',[
              ':file' => $file_path,
              ':order_id' => $order_id,
            ]);
            $this->logger->get('optiback_import')->warning($message);

            continue;
          }
        } else {
          // If field_tracking_number is empty, we save it in the field.

          // Gets the tracking number from the file. Max length 255 characters.

          #$csv_content = file_get_contents($file_path, false, null, 0, 255);
          $csv_content = $this->optibackHelper->csvToArray($file_path, "\t");
          // @todo: get tracking number from csv content.
          $sn = $csv_content[0]['Sendungsnummer'];
          // Cleanup.
          $tracking_number = str_replace("\n","",trim($sn));

          $order->set('field_tracking_number', $tracking_number);

          // Deletes source file.
          $delete = unlink($file_path);

          if (!$delete) {
            $message .= $this->t('The file :file could not be deleted.',[
              ':file' => $file_path
            ]);
            $this->logger->get('optiback_import')->warning($message);
          }
        }

        $states = [
          'validation',
          'paid',
          'fulfillment',
        ];

        $current_state = $order->getState()->getValue()['value'];
        if (in_array($current_state, $states)) {
          // Fulfills the order.
          $order->getState()->applyTransitionById('fulfill');
        }

        // Sends a order is shipped notification to the customer.
        $customer = $order->getCustomer();
        // Retrieves the configurable email text.
        $config = $this->orderTokenProvider->getEmailConfigTokenReplaced('custom_mail_ui.commerce_order_shipping', $order, $customer);

        $params = [
          'subject' => $config['subject'],
          'body' => $config['body'],
          'from' => $config['from'],
          'bcc' => $config['bcc'],
        ];

        $mail = $this->mailHelper->sendMail(
          'custom_order_shipping',
          'custom_order_shipping',
          $order->getEmail(),
          'de',
          $params
        );
        if (!$mail) {
          $message = $this->t('The shipping notification with the tracking number could not be send to the customer.');
          $this->logger->get('optiback_import')->error($message);
        }

        // Sets the flag to true, that we know the invoice is already processed.
        $res = $order->save();
        if ($res) {
          $message .= 'The order was successfully saved.';
        }
      } else {
        // If order_id is not numeric, we add a warning.
        $message .= $this->t(':order_id is no valid order id. The file :file could not be processed.',[
          ':file' => $file_path,
          ':order_id' => $order_id,
        ]);
        $this->logger->get('optiback_import')->warning($message);
      }
    }
    return $message;
  }
}
