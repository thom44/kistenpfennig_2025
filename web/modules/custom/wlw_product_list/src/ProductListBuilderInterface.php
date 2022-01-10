<?php

namespace Drupal\wlw_product_list;

/**
 * Retrieves Date for Product List.
 */
interface ProductListBuilderInterface {

  /**
   * Retrieve taxonomy term date from shop_category vocabulary.
   *
   * @return array The name of the term.
   */
  public function getCategoryList();

  /**
   * Retrieve taxonomy term date from shop_category vocabulary with depth.
   *
   * @return array The name of the term.
   */
  public function getCategoryDepth();

  /**
   * Retrieves all products which reverenced the category.
   *
   * @param $category integer The category id.
   * @param $filter array The filter values.
   * @return array (optional) The prepared list with product values.
   */
  public function getProductByCategory(int $category, array $filter = []);

  /**
   * Retrieves all content-type course.
   *
   * @param $filter array (optional) The filter values.
   * @return mixed
   */
  public function getCourseList($filter = []);


}
