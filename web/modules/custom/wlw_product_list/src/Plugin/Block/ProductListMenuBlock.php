<?php

namespace Drupal\wlw_product_list\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wlw_product_list\ProductListBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a taxonomy menu block of the product list.
 *
 * @Block(
 *   id = "wlw_product_list_menu_block",
 *   admin_label = @Translation("WLW Product List Menu"),
 *   category = @Translation("WLW"),
 * )
 */
class ProductListMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

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
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ProductListBuilderInterface $product_list_builder, Renderer $renderer, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('wlw_product_list.product_list_builder'),
      $container->get('renderer'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $items = [];
    $parent = NULL;

    // Gets current category to add is_active class.
    $current_category = $this->requestStack->getCurrentRequest()->query->get('category');

    $categories = $this->productListBuilder->getCategoryDepth();

    foreach ($categories as $cid => $category) {

      $class = [
        'level-' . $category['depth'],
      ];

      // Prepares fontawesome i tag.
      $i_tag = [
        '#type' => 'html_tag',
        '#tag' => 'i',
        '#attributes' => [
          'class' => 'fa fa-chevron-right',
        ],
      ];
      $i_tag = $this->renderer->render($i_tag);

      $link_text = [
        '#type' => 'markup',
        '#markup' => $i_tag . $this->t($category['name']),
      ];

      // Gets reverenced nodes.
      $nodes = $this->productListBuilder->getNodesByTaxonomyTermIds($cid);
      // Gets reverenced products.
      $products = $this->productListBuilder->getProductsByTaxonomyTermIds($cid);

      // We display only a link when the category has items.
      if ($nodes || $products) {
        $path = '/shop/kategorie/' . $cid;

        $url_object = Url::fromUserInput($path);

        /** @var \Drupal\Core\Link $link */
        $item = Link::fromTextAndUrl($link_text,$url_object)->toString();

      } else {
        $item = $this->t($category['name']);
        $class[] = 'no_items';
      }

      if ($cid == $current_category) {
        $class[] = 'is_active';
      }

      $items[] = [
        '#type' => 'markup',
        '#markup' => $item,
        '#wrapper_attributes' => [
          'class' => $class,
        ],
      ];
    }

    $category_list = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    $markup = $this->renderer->render($category_list);

    return [
      '#type' => 'markup',
      '#markup' => $markup,
      '#cache' => [
        'tags' => ['taxonomy_term_list']
      ],
    ];
  }

}
