<?php
/**
 * @file: A class wich process the imported credits.
 */

namespace Drupal\optiback_import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_mail_ui\MailHelperInterface;
use Drupal\custom_order_token\OrderTokenProvider;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\file\Entity\File;
use Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser;
use Drupal\file\FileRepositoryInterface;


class ProcessCredit implements ProcessCreditInterface {

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
   * @var Drupal\Core\Logger\LoggerChannelFactoryInterface $logger;
   */
  protected $logger;

  /**
   * The file repository service.
   *
   * @var Drupal\file\FileRepositoryInterface $fileRepo
   */
  protected $fileRepo;

  /**
   * The mime type guesser service.
   *
   * @var Drupal\Core\File\MimeType\MimeTypeGuesser $mimeTypeGuesser
   */
  protected $mimeTypeGuesser;

  /**
   * The order token provider service.
   *
   * @var \Drupal\custom_order_token\OrderTokenProvider;
   */
  protected $orderTokenProvider;

  /**
   * The mail helper.
   *
   * @var \Drupal\custom_mail_ui\MailHelperInterface
   */
  protected $mailHelper;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger,
    FileRepositoryInterface $file_repo,
    MimeTypeGuesser $mime_type_guesser,
    OrderTokenProvider $order_token_provider,
    MailHelperInterface $mail_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->fileRepo = $file_repo;
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->orderTokenProvider = $order_token_provider;
    $this->mailHelper = $mail_helper;
  }

  /**
   * {@inheritDoc}
   */
  public function run() {

    $message = '';

    $credits = array_diff(scandir(ObtibackConfigInterface::OPTIBACK_CREDIT), array('..', '.'));

    foreach ($credits as $credit) {

      $order_id = str_replace(".pdf","", $credit);

      if (is_numeric($order_id)) {

        $order = $this->entityTypeManager->getStorage('commerce_order')->load($order_id);

        if (!$order) {
          continue;
        }

        // Checks if the credit is already processed.
        if ($field_credit_imported = $order->get('field_credit_imported')->getValue()) {
          if ($field_credit_imported[0]['value'] == 1) {
            continue;
          }
        }

        $prefix = ObtibackConfigInterface::CREDIT_PREFIX;
        $source_path = ObtibackConfigInterface::OPTIBACK_CREDIT . '/' . $credit;
        $dest_path = ObtibackConfigInterface::DRUPAL_CREDIT . '/' . $prefix . $credit;
        $dest_uri = ObtibackConfigInterface::DRUPAL_CREDIT_URI . '/' . $prefix . $credit;

        // We backup old credit if exists and move it.
        //   This prevents file permission problem in drupal.
        if ($field_credit = $order->get('field_credit')->getValue()) {
          if ($fid = $field_credit[0]['target_id']) {
            $old_file = $this->entityTypeManager->getStorage("file")->load($fid);

            $backup_file = $this->fileRepo->move(
              $old_file,
              ObtibackConfigInterface::DRUPAL_CREDIT_URI . '/' . $prefix . $order_id . '_' . $fid . '.pdf');
          }
        }

        // Copies pdf files with shell copy command.
        // Option -v verbose output
        // Option --backup=t
        //   --backup: Make a backup of each existing destination file that would
        //     otherwise be overwritten or removed.
        //   numbered: Make numbered backups. Backup format file.pdf.~1~
        $cp_cmd = 'cp -v --backup=numbered ' . $source_path . ' ' . $dest_path;

        $cp_output = shell_exec($cp_cmd);

        $message .= 'Copy credits to private folder. ' . $cp_output;

        // Checks if the file was copied to the drupal private path.
        if (!file_exists($dest_path)) {
          $message .= $this->t('The file :file could not be copied to drupal private path. :log',[
            ':file' => $prefix . $credit,
            ':log'  => $log,
          ]);
          $this->logger->get('optiback_import')->warning($message);
          continue;
        }

        // Creates a drupal managed file.
        $uid = $order->getCustomer()->id();
        $values = [
          'uid' => $uid,
          'filename' => $prefix . $credit,
          'filesize' => filesize($dest_path),
          'uri' => $dest_uri,
        ];

        $values['filemime'] = $this->mimeTypeGuesser->guess($dest_path);

        $file = File::create($values);

        if ($file) {
          $file->isPermanent();
          $file->save();

          // Deletes source file.
          $delete = unlink($source_path);

          if (!$delete) {
            $message .= $this->t('The file :file could not be deleted.',[
              ':file' => $source_path
            ]);
            $this->logger->get('optiback_import')->warning($message);
          }
        }

        // Adds file to the file field of the order.
        $order->set('field_credit' , [
          'target_id' => $file->id(),
          'display'   => 1,
          'description' => 'Gutschrift'
        ]);


        $states = [
          'canceled',
        ];

        $current_state = $order->getState()->getValue()['value'];
        if (!in_array($current_state, $states)) {
          // Fulfills the order.
          $order->getState()->applyTransitionById('cancel');
        }

        // Sets the flag to true, that we know the credit is already processed.
        $order->set('field_credit_imported',1);

        $res = $order->save();

        if ($res) {

          // Remove the {order_id}_canceled.csv file.
          array_map('unlink', glob(ObtibackConfigInterface::OPTIBACK_IN . '/' . ObtibackConfigInterface::OPTIBACK_CANCEL . '/' . $order_id . '_canceled.csv'));

          $message .= 'The order was successfully saved.';

          if ($file) {

            // Sends a order is credit notification to the customer.
            $customer = $order->getCustomer();
            // Retrieves the configurable email text.
            $config = $this->orderTokenProvider->getEmailConfigTokenReplaced('custom_mail_ui.commerce_order_credit', $order, $customer);

            $params = [
              'subject' => $config['subject'],
              'body' => $config['body'],
              'from' => $config['from'],
              'bcc' => $config['bcc'],
              'files' => [$file]
            ];

            $mail = $this->mailHelper->sendMail(
              'custom_order_shipping',
              'custom_order_shipping',
              $order->getEmail(),
              'de',
              $params
            );

            if (!$mail) {
              $message = $this->t('The credit notification could not be send to the customer.');
              $this->logger->get('optiback_import')->error($message);
            }
          } else {
            $message = $this->t('The credit notification could not be send, because credit file is missing.');
            $this->logger->get('optiback_import')->error($message);
          }
        }
      }
    }
    return $message;
  }
}
