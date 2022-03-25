<?php

namespace Drupal\optiback_user_invoice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller class ProductListController.
 *
 * note: We use this controller to pass the path component category_id
 *   (which is the term id of the shop category) to the ProductListBlock, which
 *   is rendered in the content.
 *   With this method we can have a specific SEO url for each category and
 *   prevent that page cache module cause problems.
 */
class UserInvoiceController extends ControllerBase {

  /**
   * $var object The request_stack object.
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(

    );
  }

  public function page($uid) {

    $uri = file_create_url("/user/1/rechnung/12");


    return [
      '#markup' => '<a href="' . $uri . '" target="_blank">Rechnung</a>',
    ];
  }
}
