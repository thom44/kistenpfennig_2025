<?php

namespace Drupal\optiback_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\optiback_export\RunExportInterface;
use Drupal\optiback_import\RunImportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a UI for optiback export/import.
 */
class RunProcessForm extends FormBase {

  /**
   * The environment tag.
   *
   * @var string $env
   *   Possible values (dev|prod)
   */
  protected $env = 'dev';

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The run export service.
   *
   * @var \Drupal\optiback_export\RunExportInterface
   */
  protected $runExport;

  /**
   * The run export service.
   *
   * @var \Drupal\optiback_import\RunImportInterface
   */
  protected $runImport;

  /**
   * The Constructor
   */
  public function __construct(
    Messenger $messenger,
    RunExportInterface $run_export,
    RunImportInterface $run_import
  ) {
    $this->messenger = $messenger;
    $this->runExport = $run_export;
    $this->runImport = $run_import;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('optiback_export.run_export'),
      $container->get('optiback_import.run_import'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optiback_run';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $active = array('prod' => t('Produktiv'), 'dev' => t('Entwicklung'));

    $form['environment'] = array(
      '#type' => 'radios',
      '#title' => t('Server Umgebung'),
      '#default_value' => 'prod',
      '#options' => $active,
      '#description' => t('Wird das Formular auf dem Entwicklungsserver oder dem Produktiven Server ausgeführt.'),
    );

    $form['export']['header'] = array(
      '#type' => 'markup',
      '#markup' => '<h2>Bestellungen exportieren</h2>',
    );

    $form['export']['actions']['#type'] = 'actions';
    $form['export']['actions']['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export starten'),
      '#button_type' => 'primary',
    ];

    $form['import']['header'] = array(
      '#type' => 'markup',
      '#markup' => '<h2>Artikel importieren</h2>',
    );

    $form['import']['product'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Produkte importieren'),
    );

    $form['import']['invoice'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Rechnungen importieren'),
    );

    $form['import']['tracking'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Sendungsnummern importieren'),
    );

    $form['import']['actions']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import starten'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $input = $form_state->getUserInput();

    // The environment prod|dev.
    $env = $input['environment'];

    $options = [
      'product' => FALSE,
      'invoice' => FALSE,
      'tracking'=> FALSE,
    ];

    if ($input['op'] == 'Import starten') {

      if ($input['product']) {
        $options['product'] = TRUE;
      }
      if ($input['invoice']) {
        $options['invoice'] = TRUE;
      }
      if ($input['tracking']) {
        $options['tracking'] = TRUE;
      }

      $message = $this->runImport->run($env, $options);
      $this->messenger->addStatus('Der Import wurde gestartet. ' . $message);
    }

    if ($input['op'] == 'Export starten') {
      $message = $this->runExport->run($env);
      $this->messenger->addStatus('Der Export wurde gestartet. ' . $message);
    }

  }
}
