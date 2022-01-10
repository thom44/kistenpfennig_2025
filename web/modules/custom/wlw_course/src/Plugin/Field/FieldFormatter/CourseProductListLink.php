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
 * Plugin implementation of field formatter.
 *
 * @FieldFormatter(
 *   id = "wlw_course_product_list_link",
 *   label = @Translation("WLW Course Product List Link"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class CourseProductListLink extends FormatterBase {

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
      $node_id = $item->__get('target_id');
      $node = $this->entityTypeManager->getStorage('node')->load($node_id);

      // Creates product title as h3 tag.
      $title = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $node->getTitle(),
        '#attributes' => [
          'class' => 'wlw-course-title',
        ],
      ];

      $title = $this->renderer->render($title);

      // Creates url from product path alias.
      $system_path = '/node/' . $node->id();
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

      $btn = 'Product list';
      // Creates link label from field value.
      if ($node->hasField('field_button_text') && isset($node->get('field_button_text')->getValue()[0]['value'])) {
        $btn = $node->get('field_button_text')->getValue()[0]['value'];
      }
      $link_label = $this->t($btn);

      $url->setOptions($link_options);
      /** @var \Drupal\Core\GeneratedLink $link */
      $markup = Link::fromTextAndUrl($link_label, $url)->toString();

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
    return $entity_type == 'node'  && $bundle == 'course' && $field_name == 'field_course_product_list';
  }

}
