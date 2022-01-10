<?php

namespace Drupal\wlw_contract;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContractFiles implements ContractFilesInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ContractFiles constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Messenger $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function collectContractsFromOrder(OrderInterface $order) {

    $contracts = [];
    // @var $processed_fids array The file ids already processed.
    $processed_fids = [];

    $order_items = $order->getItems();

    foreach ($order_items as $item) {
      $term_id = NULL;

      if ($item->bundle() == 'course') {

        // Gets the ordered product and product type.
        $product_variation = $item->getPurchasedEntity();
        $product = $product_variation->getProduct();
        $type = $product->get('type')->getValue()[0]['target_id'];

        if ($type == 'course') {

          if ($product->hasField('field_contract')) {

            // Retrieves term from reference field.
            $term_id = $product->get('field_contract')
              ->getValue()[0]['target_id'];

            if (!empty($term_id)) {

              $term = $this->entityTypeManager->getStorage('taxonomy_term')
                ->load($term_id);

              // Gets fid from file taxonomy term file field.
              $contract_fid = $term->get('field_contract_file')
                ->getValue()[0]['target_id'];;
              $contracts[$contract_fid]['name'] = $term->getName();

              // Checks if the file is not yet processed.
              //   We don't want display the same contract twice.
              if (!in_array($contract_fid, $processed_fids)) {

                // Adds the fid to the list of processed files.
                $processed_fids[] = $contract_fid;

                // Retrieves the uri of the file.
                $file_storage = $this->entityTypeManager->getStorage('file');
                $file = $file_storage->load($contract_fid);
                $contracts[$contract_fid]['file'] = $file;

                // Prepares stdClass for mail attachment.
                $file_std_class = new \stdClass();
                $file_std_class->uri = $file->getFileUri();
                $file_std_class->filename = $file->getFilename();
                $file_std_class->filemime = $file->getMimeType();

                $contracts[$contract_fid]['file_std_class'] = $file_std_class;

              }
            }
          }
        }
      }
    }

    return $contracts;
  }
}