<?php

namespace Drupal\optiback\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\optiback_export\RunExportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a UI for optiback export/import.
 */
class RunProcessForm extends FormBase {

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger $messenger;
   */
  protected $messenger;

  /**
   * The run export service.
   *
   * @var Drupal\optiback_export\RunExportInterface $run_export;
   */
  protected $run_export;

  /**
   * The Constructor
   */
  public function __construct(
    Messenger $messenger,
    RunExportInterface $run_export
  ) {
    $this->messenger = $messenger;
    $this->run_export = $run_export;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
    $container->get('optiback_export.run_export')
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
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $commerce_order = NULL) {

    #$markup = '<h2>' . $this->t('Optiback Schnittstelle für Export/Import') . '</h2>';

    $form['header'] = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run Export'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {


  }
}
