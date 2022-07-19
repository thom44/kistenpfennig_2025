<?php

namespace Drupal\custom_table_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;


/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "key_value_table_formatter",
 *   label = @Translation("Key-Value to Table Formatter"),
 *   field_types = {
 *     "string",
 *     "uri",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class KeyValueTableFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    $header = [];
    $rows = [];
    $row = [];

    $i=0;
    foreach ($items as $item) {
      $row[] = $item->getValue()['value'];
      $i++;
      if ($i >= 2) {
        $i = 0;
        $rows[] = $row;
        $row = [];
      }
    }

      $elements[0] = [];
      if (!empty($rows)) {
        $elements[0] = [
          '#theme' => 'table__file_formatter_table',
          '#header' => $header,
          '#rows' => $rows,
          '#attributes' => [
            'class' => $items->getName(),
          ]
        ];
      }

    return $elements;
  }

}
