<?php

namespace Drupal\wlw_course\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Path\AliasManagerInterface;

/**
 * Plugin implementation of the 'addon_commerce_add_to_cart' formatter.
 *
 * @FieldFormatter(
 *   id = "wlw_course_product_link",
 *   label = @Translation("WLW Course Product Link"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class CourseProductLink extends FormatterBase {

  use StringTranslationTrait;

  /**
   * @var $renderer
   */
  protected $renderer;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * The path alias manager service.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface;
   */
  protected $pathAliasManager;

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, Renderer $renderer, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $path_alias_manager) {
      parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
      $this->renderer = $renderer;
      $this->entityTypeManager = $entity_type_manager;
      $this->pathAliasManager = $path_alias_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('path.alias_manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $list_items = [];
    $markup = '';

    foreach ($items as $delta => $item) {
      $product_id = $item->__get('target_id');
      $product = $this->entityTypeManager->getStorage('commerce_product')->load($product_id);

      // Creates product title as h3 tag.
      $title = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $product->getTitle(),
        '#attributes' => [
          'class' => 'wlw-course-title',
        ],
      ];

      $title = $this->renderer->render($title);

      // Creates url from product path alias.
      $system_path = '/product/' . $product->id();
      $alias = $this->pathAliasManager->getAliasByPath($system_path);
      /** @var \Drupal\Core\Url $url */
      $url = Url::fromUserInput($alias);

      // Creates link as button.
      $link_options = [
        'attributes' => [
          'class' => [
            'button',
            'btn',
            'button-primary',
            'btn-primary',
          ],
          'title' => $title,
        ],
      ];
      $markup = $title;

      $btn = 'Configure product';
      // Creates link label from year field value.
      if ($product->hasField('field_button') && isset($product->get('field_button')->getValue()[0]['value'])) {
        $btn = $product->get('field_button')->getValue()[0]['value'];
      }
      $link_label = $this->t($btn);

      $url->setOptions($link_options);
      /** @var \Drupal\Core\GeneratedLink $link */
      $markup .= Link::fromTextAndUrl($link_label, $url)->toString();

      // Adds prerendered markup to the list items.
      $list_items[] = ['#markup' => $markup];
    }

    $output = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => $this->t('Online Buchen'),
      '#items' => $list_items,
      '#attributes' => ['class' => ['wlw-course-list','button','btn-blue']],
      '#wrapper_attributes' => ['class' => 'wlw-course-list-wrapper'],
    ];

    $elements[0]['wlw_course_product_link'] = $output;

    return $elements;
  }

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    $field_name = $field_definition->getName();
    return $entity_type == 'node'  && $bundle == 'course' && $field_name == 'field_product';
  }

}
