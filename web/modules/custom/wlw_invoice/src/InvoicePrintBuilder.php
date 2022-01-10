<?php

namespace Drupal\wlw_invoice;

use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_print\FilenameGeneratorInterface;
use Drupal\entity_print\Plugin\PrintEngineInterface;
use Drupal\entity_print\Renderer\RendererFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileSystem;

/**
 * The print builder service.
 *   Generates printable pdf-invoice.
 *   Files are saved in privte://invoice/ and attached to order-receipt email.
 *   This is used in
 *   - Pdf generation in order status transition validate.
 *     @see \Drupal\wlw_workflow\Mail\InvoiceFileAttachment
 */
class InvoicePrintBuilder implements InvoicePrintBuilderInterface {

  use StringTranslationTrait;
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity storage for the 'file' entity type.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The Entity print filename generator.
   *
   * @var \Drupal\entity_print\FilenameGeneratorInterface
   */
  protected $filenameGenerator;

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The print preparer service.
   *
   * @var \Drupal\wlw_invoice\InvoicePrintPreparerInterface;
   */
  protected $printPreparer;

  /**
   * The Print Renderer factory.
   *
   * @var \Drupal\entity_print\Renderer\RendererFactoryInterface
   */
  protected $rendererFactory;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer $renderer
   */
  protected $renderer;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger $messenger;
   */
  protected $messenger;

  /**
   * The sting translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   */
  protected $stringTranslation;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem $fileSystem
   */
  protected $fileSystem;

  /**
   * InvoicePrintBuilder constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\entity_print\FilenameGeneratorInterface $filename_generator
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\wlw_invoice\InvoicePrintPreparerInterface $print_preparer
   * @param \Drupal\entity_print\Renderer\RendererFactoryInterface $renderer_factory
   * @param \Drupal\Core\Render\Renderer $renderer
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\Core\File\FileSystem $file_system
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    FilenameGeneratorInterface $filename_generator,
    AccountInterface $current_user,
    InvoicePrintPreparerInterface $print_preparer,
    RendererFactoryInterface $renderer_factory,
    Renderer $renderer,
    Messenger $messenger,
    TranslationInterface $string_translation,
    FileSystem $file_system) {
    $this->configFactory = $config_factory;
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->filenameGenerator = $filename_generator;
    $this->currentUser = $current_user;
    $this->printPreparer = $print_preparer;
    $this->rendererFactory = $renderer_factory;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
    $this->fileSystem = $file_system;
  }


  /**
   * {@inheritDoc}
   */
  public function savePrintableInvoice(InvoiceInterface $invoice, PrintEngineInterface $print_engine, $scheme = 'private') {

    $filename = $this->generateFilename($invoice);
    $uri = "$scheme://invoice/$filename";

    $params = [
      'filename'    => $filename,
      'uri'         => $uri,
      'pdf_output_type' => 'invoice', // we use this in preprocess function.
    ];

    return $this->savePrintable($invoice, $print_engine,$params);
  }

  /**
   * {@inheritDoc}
   */
  public function savePrintableCredit(InvoiceInterface $invoice, PrintEngineInterface $print_engine, $scheme = 'private') {

    $filename = $this->generateFilename($invoice,'wlw_gutschrift_');
    $uri = "$scheme://invoice/$filename";

    $params = [
      'filename'  => $filename,
      'uri'       => $uri,
      'pdf_output_type' => 'credit', // we use this in preprocess function.
    ];

    return $this->savePrintable($invoice, $print_engine,$params);
  }


  /**
   * {@inheritdoc}
   */
  public function savePrintable(InvoiceInterface $invoice, PrintEngineInterface $print_engine, $params) {

    // Generates page html for download method.
    // Original savePrintable:
    // @see \Drupal\commerce_invoice\InvoicePrintBuilder
    $config = $this->configFactory->get('entity_print.settings');
    $default_css = $config->get('default_css');

    $renderer = $this->rendererFactory->create([$invoice]);

    // Transfer this flag to the preprocess function to differ invoice|credit.
    $invoice->setData('pdf_output_type',$params['pdf_output_type']);
    // Renders invoice seven_preprocess_commerce_invoice is called.
    $content = $renderer->render([$invoice]);

    $render = [
      '#theme' => 'entity_print__' . $invoice->getEntityTypeId() . '__' . $invoice->bundle(),
      '#title' => 'View dompdf',
      '#content' => $content,
      '#attached' => [],
    ];

    $page = $renderer->generateHtml([$invoice], $render, $default_css, TRUE);

    // Creates pdf from html with dompdf library with entity_print api.
    $print_engine->addPage($page);

    // Saves the file as unmanaged file.
    $binary = $print_engine->getBlob();
    // Drupal 8 Code: $saved = file_unmanaged_save_data($binary, $params['uri'], FILE_EXISTS_REPLACE);
    $saved = $this->fileSystem->saveData($binary, $params['uri'], FileSystemInterface::EXISTS_REPLACE);

    if ($saved) {
      $this->messenger->addStatus($this->t('The pdf file was saved: @file.', [
        '@file' => $saved,
      ]));

      // Prepares file for email attachment.
      $file = new \stdClass();
      $file->uri = $params['uri'];
      $file->filename = $params['filename'];
      $file->filemime = 'application/pdf';

      return $file;
    }
    return FALSE;
  }

  /**
   * Generates a filename for the given invoice.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice
   *   The invoice.
   * @param string $prafix
   *   (optional) The filename prafix, defaults to 'wlw_rechnung_'.
   *
   * @return string
   *   The generated filename.
   */
  protected function generateFilename(InvoiceInterface $invoice, $prafix = 'wlw_rechnung_') {
    $file_name = $this->filenameGenerator->generateFilename([$invoice]);
    $file_name = $prafix . $file_name . '.pdf';
    return $file_name;
  }
}
