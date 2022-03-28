<?php
/**
 * @file: A class wich process the imported invoices.
 */

namespace Drupal\optiback_import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\optiback\ObtibackConfigInterface;
use Drupal\file\Entity\File;
use Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser;
use Drupal\file\FileRepositoryInterface;


class ProcessInvoice implements ProcessInvoiceInterface {

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

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger,
    FileRepositoryInterface $file_repo,
    MimeTypeGuesser $mime_type_guesser
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->fileRepo = $file_repo;
    $this->mimeTypeGuesser = $mime_type_guesser;
  }

  /**
   * {@inheritDoc}
   */
  public function run() {

    $message = '';

    $invoices = array_diff(scandir(ObtibackConfigInterface::OPTIBACK_INVOICE), array('..', '.'));

    foreach ($invoices as $invoice) {

      $order_id = str_replace(".pdf","", $invoice);

      if (is_numeric($order_id)) {

        $order = $this->entityTypeManager->getStorage('commerce_order')->load($order_id);

        if (!$order) {
          continue;
        }

        // Checks if the invoice is already processed.
        if ($field_invoice_imported = $order->get('field_invoice_imported')->getValue()) {
          if ($field_invoice_imported[0]['value'] == 1) {
            continue;
          }
        }

        $prefix = ObtibackConfigInterface::INVOICE_PREFIX;
        $source_path = ObtibackConfigInterface::OPTIBACK_INVOICE . '/' . $invoice;
        $dest_path = ObtibackConfigInterface::DRUPAL_INVOICE . '/' . $prefix . $invoice;
        $dest_uri = ObtibackConfigInterface::DRUPAL_INVOICE_URI . '/' . $prefix . $invoice;

        // We backup old invoice if exists and move it.
        //   This prevents file permission problem in drupal.
        if ($field_invoice = $order->get('field_invoice')->getValue()) {
          if ($fid = $field_invoice[0]['target_id']) {
            $old_file = $this->entityTypeManager->getStorage("file")->load($fid);

            $backup_file = $this->fileRepo->move(
              $old_file,
              ObtibackConfigInterface::DRUPAL_INVOICE_URI . '/' . $prefix . $order_id . '_' . $fid . '.pdf');
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

        $message .= 'Copy invoices to private folder. ' . $cp_output;

        // Checks if the file was copied to the drupal private path.
        if (!file_exists($dest_path)) {
          $message .= $this->t('The file :file could not be copied to drupal private path. :log',[
            ':file' => $prefix . $invoice,
            ':log'  => $log,
          ]);
          $this->logger->get('optiback_import')->warning($message);
          continue;
        }

        // Creates a drupal managed file.
        $uid = $order->getCustomer()->id();
        $values = [
          'uid' => $uid,
          'filename' => $prefix . $invoice,
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
        $order->set('field_invoice' , [
          'target_id' => $file->id(),
          'display'   => 1,
          'description' => 'Rechnung'
        ]);

        // Fulfills the order.
        $order->setRefreshState('fulfillment');

        // Sets the flag to true, that we know the invoice is already processed.
        $order->set('field_invoice_imported',1);
        $res = $order->save();
        if ($res) {
          $message .= 'The order was successfully saved.';
        }
      }
    }
    return $message;
  }
}
