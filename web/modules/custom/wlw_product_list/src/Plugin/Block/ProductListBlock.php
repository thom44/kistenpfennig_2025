<?php

namespace Drupal\wlw_product_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wlw_product_list\ProductListBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Provides a block with a custom product list.
 *
 * @Block(
 *   id = "wlw_product_list_block",
 *   admin_label = @Translation("WLW Product List"),
 *   category = @Translation("WLW"),
 * )
 */
class ProductListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  //use UncacheableDependencyTrait;
  use StringTranslationTrait;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * The product list builder service.
   *
   * @var \Drupal\wlw_product_list\ProductListBuilderInterface;
   */
  protected $productListBuilder;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer $renderer
   */
  protected $renderer;

  /**
   * $var object The request_stack object.
   */
  protected $requestStack;

  /**
   * @var integer The term id of course category.
   */
  protected $courseTerm = 3983;

  /**
   * ProductListBlock constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\wlw_product_list\ProductListBuilderInterface $product_list_builder
   * @param \Drupal\Core\Render\Renderer $renderer
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Messenger $messenger, EntityTypeManagerInterface $entity_type_manager, ProductListBuilderInterface $product_list_builder, Renderer $renderer, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
    $this->productListBuilder = $product_list_builder;
    $this->renderer = $renderer;
    $this->requestStack = $requestStack;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('entity_type.manager'),
      $container->get('wlw_product_list.product_list_builder'),
      $container->get('renderer'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $category = NULL;
    $courses = [];
    $year = NULL;
    $output = [];

    $path = $this->requestStack->getCurrentRequest()->getPathInfo();
    $path_components = explode("/", $path);
    $category = $path_components[3];

    $categories = $this->productListBuilder->getCategoryList();

    if (isset($this->configuration['#options']['year'])) {
      // Gets the year value from ajax refreshProductListBlock() Method.
      $year = $this->configuration['#options']['year'];
    } else {
      // Gets the year value from query string on page load.
      $year = $this->requestStack->getCurrentRequest()->query->get('year');
    }

    // Gets all products sorted by category add it to the list.
    $products = $this->productListBuilder->getProductByCategory($category, ['year' => $year]);

    // Loads courses only if
    if ($category === NULL || $category == $this->courseTerm) {
      // Gets all course nodes and add it to the list.
      $courses = $this->productListBuilder->getCourseList(['year' => $year]);
    }

    if ($content = $products + $courses) {
      // Creates markup of the products.
      foreach ($categories as $tid => $category_name) {
        if (isset($content[$tid])) {
          $output[] = [
            '#theme' => 'product_list',
            '#product_list' => $content[$tid],
            '#category' => $category_name,
            '#filter' => rand(0,100),
          ];
        }
      }
    } else {
      // note: Not use simple '#markup', because ajax stop working on second call.
      $output[] = [
        '#theme' => 'product_list',
        '#product_list' => NULL,
        '#category' => $categories[$category],
        '#filter' => $this->t('Keine Produkte vorhanden'),
      ];
    }

    return $output;
  }

  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('url'));
  }
}
