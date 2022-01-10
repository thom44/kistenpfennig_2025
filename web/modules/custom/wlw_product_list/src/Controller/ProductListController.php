<?php

namespace Drupal\wlw_product_list\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller class ProductListController.
 *
 * note: We use this controller to pass the path component category_id
 *   (which is the term id of the shop category) to the ProductListBlock, which
 *   is rendered in the content.
 *   With this method we can have a specific SEO url for each category and
 *   prevent that page cache module cause problems.
 */
class ProductListController extends ControllerBase {

  /**
   * $var object The request_stack object.
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  public function page($category) {

     return [
       '#theme' => 'product_list_page',
       '#label' => 'Product List Page'
     ];
  }
}