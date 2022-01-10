<?php

namespace Drupal\wlw_product_list;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\wlw_product\PriceFormatterHelperInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Retrieves Date for Product List.
 */
class ProductListBuilder implements ProductListBuilderInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * The path alias manager service.
   *
   * @var Drupal\Core\Path\AliasManager;
   */
  protected $aliasManager;

  /**
   * The price formatter helper service.
   *
   * @var ProductListBuilderInterface
   */
  protected $priceFormatterHelper;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer;
   */
  protected $renderer;


  public function __construct(EntityTypeManagerInterface $entity_type_manager, AliasManager $path_alias_manager, PriceFormatterHelperInterface $price_formatter_helper, Connection $database, LanguageManagerInterface $language_manager, Renderer $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $path_alias_manager;
    $this->priceFormatterHelper = $price_formatter_helper;
    $this->database = $database;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritDoc}
   */
  public function getCategoryList() {

    $categories = [];

    $terms_data = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('shop_category');

    foreach ($terms_data as $term_data) {
      $tid = $term_data->tid;

      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($term_data->tid);
      $field_shop_category_title = $term->get('field_shop_category_title')->getValue();

      if (isset($field_shop_category_title[0]['value'])) {
        $categories[$tid] = [
          '#markup' => $term->get('field_shop_category_title')->getValue()[0]['value'],
        ];
      }
    }
    return $categories;
  }


  /**
   * {@inheritDoc}
   */
  public function getCategoryDepth() {

    $categories = [];

    $terms_data = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('shop_category');

    foreach ($terms_data as $term_data) {
      $tid = $term_data->tid;
      $categories[$tid]['depth'] = $term_data->depth;
      $categories[$tid]['name'] = $term_data->name;

    }
    return $categories;
  }

  /**
   * {@inheritDoc}
   */
  public function getProductByCategory($category, $filter = []) {

    $product_list = [];
    // Product types which should be not in the list.
    $exclude = [
      'course',
      'combi_product',
    ];

    $query = $this->entityTypeManager->getStorage('commerce_product')->getQuery();
    $query->condition('status', 1);
    $query->condition('type', $exclude, 'NOT IN');

    // Filter by year if year has value.
    if (isset($filter['year'])) {
      // Gets the tid from taxonomy_term which name is the requested year.
      $tid = $this->getTermByYear($filter['year']);

      if ($tid) {
        // We display the product if the selelcted year is the filtered year
        // or it has no year selected.
        $orGroup = $query->orConditionGroup()
          ->condition('field_year.target_id', $tid)
          ->notExists('field_year.target_id');
        $query->condition($orGroup);
      }
    }
    $product_ids = $query->execute();

    $products = $this->entityTypeManager->getStorage('commerce_product')->loadMultiple($product_ids);

    foreach ($products as $product) {

      $price_prefix = '';
      $price_postfix = '';
      $tax = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => ' inkl. Mwst.',
        '#attributes' => [
          'class' => 'tax-rate',
        ]
      ];
      $tax_rate = $this->renderer->render($tax)->__toString();

      $product_id = $product->id();

      $fields = $product->getFields();

      // The list depends on category selection of the required field.
      $product_category = $fields['field_shop_category']->getValue()[0]['target_id'];

      if ($product_category) {

        if ($category !== NULL && $category != $product_category) {
          continue;
        }

        $langcode = $this->languageManager->getCurrentLanguage()->getId();

        if (isset($fields['path'])) {
          $product_list[$product_category][$product_id]['path'] = $this->aliasManager->getAliasByPath('/product/'. $product_id, $langcode);
        }
        $product_list[$product_category][$product_id]['id'] = 'product_' . $product_id;

        $product_list[$product_category][$product_id]['langcode'] = $product->getTranslationLanguages();
        $product_list[$product_category][$product_id]['title'] = $product->getTitle();

        if (isset($fields['field_product_image'])) {
          $product_list[$product_category][$product_id]['image'] = $this->createImageRenderArray($fields['field_product_image']);
        }

        if (isset($fields['field_font_awesome'])
          &&
          !empty($fields['field_font_awesome']->getValue())
        ) {
          // Gets the selected font awesome CSS-Class term_id.
          if ($tid = $fields['field_font_awesome']->getValue()[0]['target_id'])  {

            $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);

            // Gets the class value from the term field.
            if ($term->hasField('field_css_class')) {
              $field_css_class = $term->get('field_css_class')->getValue();

              if (isset($field_css_class[0]['value'])) {
                $product_list[$product_category][$product_id]['font_awesome'] = $field_css_class[0]['value'];
              }
            }
          }
        }

        if (isset($fields['field_overlay'])
          &&
          !empty($fields['field_overlay']->getValue())
        ) {
          $product_list[$product_category][$product_id]['overlay'] = $fields['field_overlay']->getValue()[0]['value'];
        }

        $product_list[$product_category][$product_id]['body'] = [
          '#markup' => $fields['field_shop_description']->getValue()[0]['value'],
          ];

        // Gets field_price_prefix value.
        if (
          isset($fields['field_price_prefix'])
          &&
          !empty($fields['field_price_prefix']->getValue())
        ) {
          $prefix = [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $fields['field_price_prefix']->getValue()[0]['value'],
            '#attributes' => [
              'class' => 'price-prefix',
            ]
          ];

          $price_prefix = $this->renderer->render($prefix)->__toString();
        }

        // Gets field_price_prefix value.
        if (
          isset($fields['field_price_postfix'])
          &&
          !empty($fields['field_price_postfix']->getValue())
        ) {
          $postfix = [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $fields['field_price_postfix']->getValue()[0]['value'],
            '#attributes' => [
              'class' => 'price-postfix',
            ]
          ];
          $price_postfix = $this->renderer->render($postfix)->__toString();
        }

        // Overrides price with price info.
        if (
          isset($fields['field_display_price'])
        &&
          !empty($fields['field_display_price']->getValue())
        ) {

          if (
            isset($fields['field_tax_rate'])
            &&
            !empty($fields['field_tax_rate']->getValue())
          ) {
            $tax_rate = $this->priceFormatterHelper->getFormattedTaxRate($fields['field_tax_rate']->getValue()[0]['value']);
          }

          $display_price = $this->priceFormatterHelper->getFormattedPriceByAmount($fields['field_display_price']->getValue()[0]);

          $product_list[$product_category][$product_id]['price'] = [
            '#markup' => $price_prefix . $display_price . $tax_rate . $price_postfix,
            ];

          if (
            isset($fields['field_availability'])
            &&
            !empty($fields['field_availability']->getValue())
          ) {
            $value = $fields['field_availability']->getValue()[0]['value'];

            $i_tag = [
              '#type' => 'html_tag',
              '#tag' => 'i',
              '#value' => '',
              '#attributes' => [
                'class' => 'fa fa-circle',
                'aria-hidden' => 'true',
              ],
            ];

            $i_tag = $this->renderer->render($i_tag);

            // Gets array with allowed values key/value.
            $allowed_values = $fields['field_availability']->getSetting('allowed_values');

            $availability = [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#value' => $i_tag . $allowed_values[$value],
              '#attributes' => [
                'class' => $value,
              ],
            ];


            $product_list[$product_category][$product_id]['availability'] = $availability;
          }

        // Takes the first variation price for price display.
        } else {

          $variation_id = $product->getVariationIds()[0];

          $product_list[$product_category][$product_id]['price'] = $price_prefix . $this->priceFormatterHelper->getFormattedPriceByVatiationId($variation_id) . $price_postfix;
        }
      }
    }
     return $product_list;
  }

  /**
   * {@inheritDoc}
   */
  public function getCourseList($filter = []) {

    $course_list = [];

    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->condition('status', 1);
    $query->condition('type', 'course');

    // Filter by year if year has value.
    if (isset($filter['year'])) {
      // Gets the tid from taxonomy_term which name is the requested year.
      $tid = $this->getTermByYear($filter['year']);

      if ($tid) {
        // We display the course if the selelcted year is the filtered year
        // or it has no year selected.
        $orGroup = $query->orConditionGroup()
          ->condition('field_year.target_id', $tid)
          ->notExists('field_year.target_id');
        $query->condition($orGroup);
      }
    }

    $node_ids = $query->execute();

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($node_ids);

    foreach ($nodes as $node) {

      if ($node->hasField('field_shop_category')) {

        $field_shop_category = $node->get('field_shop_category')->getValue();

        if ($field_shop_category) {

          $price_prefix = '';
          $price_postfix = '';
          $tax_rate = '';

          $course_category = $field_shop_category[0]['target_id'];

          $nid = $node->id();

          $fields = $node->getFields();

          $langcode = $this->languageManager->getCurrentLanguage()->getId();

          if (isset($fields['path'])) {
            $course_list[$course_category]['node_' . $nid]['path'] = $this->aliasManager->getAliasByPath('/node/'. $nid, $langcode);
          }

          $course_list[$course_category]['node_' . $nid]['id'] = 'nid_' . $nid;

          $course_list[$course_category]['node_' . $nid]['title'] = $node->getTitle();

          // Creates image render arr
          if (isset($fields['field_product_image'])) {
            $course_list[$course_category]['node_' . $nid]['image'] = $this->createImageRenderArray($fields['field_product_image']);
          }

          if (isset($fields['field_shop_description'])) {
            $course_list[$course_category]['node_' . $nid]['body'] = $this->createBodyRenderArray($fields['field_shop_description']);
          }

          if (isset($fields['field_font_awesome'])
            &&
            !empty($fields['field_font_awesome']->getValue())
          ) {

            // Gets the selected font awesome CSS-Class term_id.
            if ($tid = $fields['field_font_awesome']->getValue()[0]['target_id'])  {

              $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);

              // Gets the class value from the term field.
              $field_css_class = $term->get('field_css_class')->getValue();

              if (isset($field_css_class[0]['value'])) {
                $course_list[$course_category]['node_' . $nid]['font_awesome'] = $field_css_class[0]['value'];
              }
            }

          }

          if (isset($fields['field_overlay'])
            &&
            !empty($fields['field_overlay']->getValue())
          ) {
            $course_list[$course_category]['node_' . $nid]['overlay'] = $fields['field_overlay']->getValue()[0]['value'];
          }

          if (
            isset($fields['field_availability'])
            &&
            !empty($fields['field_availability']->getValue())
          ) {
            $value = $fields['field_availability']->getValue()[0]['value'];

            $i_tag = [
              '#type' => 'html_tag',
              '#tag' => 'i',
              '#value' => '',
              '#attributes' => [
                'class' => 'fa fa-circle',
                'aria-hidden' => 'true',
              ],
            ];

            $i_tag = $this->renderer->render($i_tag);

            // Gets array with allowed values key/value.
            $allowed_values = $fields['field_availability']->getSetting('allowed_values');

            $availability = [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#value' => $i_tag . $allowed_values[$value],
              '#attributes' => [
                'class' => $value,
              ],
            ];

            $course_list[$course_category]['node_' . $nid]['availability'] = $availability;
          }

          // Gets field_price_prefix value.
          if (
            isset($fields['field_price_prefix'])
            &&
            !empty($fields['field_price_prefix']->getValue())
          ) {
            $prefix = [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => $fields['field_price_prefix']->getValue()[0]['value'],
              '#attributes' => [
                'class' => 'price-prefix',
              ]
            ];

            $price_prefix = $this->renderer->render($prefix)->__toString();
          }

          // Gets field_price_prefix value.
          if (
            isset($fields['field_price_postfix'])
            &&
            !empty($fields['field_price_postfix']->getValue())
          ) {
            $postfix = [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => $fields['field_price_postfix']->getValue()[0]['value'],
              '#attributes' => [
                'class' => 'price-postfix',
              ]
            ];
            $price_postfix = $this->renderer->render($postfix)->__toString();
          }

          // Overrides price with price info.
          if (
            isset($fields['field_display_price'])
            &&
            !empty($fields['field_display_price']->getValue())
          ) {

            if (
              isset($fields['field_tax_rate'])
              &&
              !empty($fields['field_tax_rate']->getValue())
            ) {

              $tax_rate = $this->priceFormatterHelper->getFormattedTaxRate($fields['field_tax_rate']->getValue()[0]['value']);

              // We render this string in course products if tax rate is not set.
              if (!$tax_rate) {
                $tax_arr = [
                  '#type' => 'html_tag',
                  '#tag' => 'span',
                  '#value' => $this->t('Umsatzsteuerfrei'),
                  '#attributes' => [
                    'class' => 'tax-rate',
                  ]
                ];

                $tax_rate = $this->renderer->render($tax_arr)->__toString();
              }
            }

            $display_price = $this->priceFormatterHelper->getFormattedPriceByAmount($fields['field_display_price']->getValue()[0]);

            $course_list[$course_category]['node_' . $nid]['price'] = [
              '#markup' => $price_prefix . $display_price . $tax_rate . $price_postfix,
            ];

            // Takes the first variation price for price display.
          } else {
            if (
              isset($fields['field_product'])
              &&
              !empty($fields['field_product']->getValue())
            ) {
              // Gets the product from reverence field.
              $product_id = $fields['field_product']->getValue()[0]['target_id'];

              $product = $this->entityTypeManager->getStorage('commerce_product')->load($product_id);
              $variation_id = $product->getVariationIds()[0];

              $course_list[$course_category]['node_' . $nid]['price'] = [
                '#markup' => $price_prefix . $this->priceFormatterHelper->getFormattedPriceByVatiationId($variation_id)
                ];
            }
          }
        }
      }
    }

    return $course_list;
  }

  /**
   * Creates image render array from field object.
   *
   * @param $image_field
   * @return array|bool
   */
  protected function createImageRenderArray($image_field) {

    if (!$value = $image_field->getValue()) {
      return FALSE;
    }

    if (!isset($value[0]['target_id'])) {
      return FALSE;
    }

    $file = $this->entityTypeManager->getStorage('file')->load($value[0]['target_id']);

    // @todo: fix image style not used in production environment.
    $uri = $file->getFileUri();

    return [
      '#theme' => 'image_style',
      '#style_name' => 'shop_product_list',
      '#uri' => $uri,
    ];
  }

  /**
   * Creates markup render array from textlong formatted field.
   *
   * @param $body_field The field object.
   * @return array|bool
   */
  protected function createBodyRenderArray($body_field) {

    if (!$value = $body_field->getValue()) {
      return FALSE;
    }

    if (empty($value)) {
      return FALSE;
    }

    return [
      '#markup' => $body_field->getValue()[0]['value'],
    ];
  }

  /**
   * Retieves node id's which are reverenced to a term.
   *
   * @param $termIds
   * @return |null
   */
  function getNodesByTaxonomyTermIds($term_ids) {

    $term_ids = (array)$term_ids;

    if (empty($term_ids)) {
      return NULL;
    }

    $query = $this->database->select('taxonomy_index', 'ti');
    $query->fields('ti', array('nid'));
    $query->condition('ti.tid', $term_ids, 'IN');
    $query->distinct(TRUE);
    $result = $query->execute();

    if ($nodeIds = $result->fetchCol()) {
      return $nodeIds;
    }

    return NULL;
  }

  /**
   * Retieves products which are reverenced to a term.
   *
   * @param $term_ids
   * @return |null
   */
  function getProductsByTaxonomyTermIds($term_ids) {

    $term_ids = (array) $term_ids;

    if(empty($term_ids)) {
      return NULL;
    }

    $query = $this->database->select('commerce_product__field_shop_category', 'fsc');
    $query->fields('fsc', array('entity_id'));
    $query->condition('fsc.field_shop_category_target_id', $term_ids, 'IN');
    $query->distinct(TRUE);
    $result = $query->execute();

    if($productIds = $result->fetchCol()) {
      return $productIds;
    }

    return NULL;
  }

  /**
   * Gets the term id from year value.
   *
   * @parem $year sting
   * @return null|integer
   */
  public function getTermByYear($year) {

    $tid = NULL;

    $properties = [
      'vid' => 'jahr',
      'name' => $year
    ];
    $terms_data = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties($properties);
    foreach ($terms_data as $term_data) {
      $term_id = $term_data->tid;
      if (!empty($term_id->getValue())) {
        $tid = $term_id->getValue()[0]['value'];
      }
    }
    return $tid;
  }
}
