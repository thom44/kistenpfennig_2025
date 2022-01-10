<?php

namespace Drupal\wlw_product_list\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Provides an UI for sending reminder emails to the customer.
 */
class ProductListForm extends FormBase {

  use UncacheableDependencyTrait;

  /**
   * The EntityTypeManager provider.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * The Constructor
   */
  public function __construct(EntityTypeManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wlw_product_list_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $year_options = [];

    $terms = $this->entityManager->getStorage('taxonomy_term')->loadTree('jahr');

    $form['year'] = [
      '#type' => 'fieldset',
      '#title' => t('Jahr'),
      '#attributes' => [
        'class' => ['wlw-filter','filter-year'],
      ],
    ];

    foreach ($terms as $term) {
      $tid = $term->tid;
      $year = $term->name;

      $form['year'][$tid] = [
        '#ajax' => [
          'callback' => [get_class($this), 'refreshProductListBlock'],
        ],
        '#type' => 'submit',
        '#value' => (int)$year,
        '#validated' => true,
      ];
    }

    $form['actions']['reset'] = [
      '#ajax' => [
        'callback' => [get_class($this), 'refreshProductListBlock'],
      ],
      '#type' => 'submit',
      '#value' => $this->t('Zurücksetzen'),
      '#validated' => true,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No need for submit handling.
  }

  /**
   * Ajax Callback.
   *   Refresh's the product_list block per ajax.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public static function refreshProductListBlock(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $year = NULL;

    if ($form_state->getValue('op') != 'Zurücksetzen') {
      // Gets the year value from submit button.
      $year = $form_state->getValue('op');
    }

    // note: We use static call, because we are in a static method.
    $block_manager = \Drupal::service('plugin.manager.block');

    $plugin_block = $block_manager->createInstance('wlw_product_list_block', ['#options' => ['year' => $year]]);

    $render = $plugin_block->build();

    $response->addCommand(new ReplaceCommand('#wlw-product-list', $render));

    return $response;
  }

}