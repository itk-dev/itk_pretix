<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'pretix_event_settings' field type.
 *
 * @FieldType(
 *   id = "pretix_event_settings",
 *   label = @Translation("pretix event settings"),
 *   description = @Translation("pretix event settings"),
 *   default_widget = "pretix_event_settings_widget"
 * )
 *
 * @property string template_event
 * @property bool synchronize_event
 */
class PretixEventSettings extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function propertyDefinitions(
    FieldStorageDefinitionInterface $field_definition,
  ) {
    $properties['template_event'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Template event'))
      ->setRequired(TRUE);
    $properties['synchronize_event'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Synchronize event'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function schema(
    FieldStorageDefinitionInterface $field_definition,
  ) {
    return [
      'columns' => [
        'template_event' => [
          'type' => 'varchar',
          'length' => 128,
        ],
        'synchronize_event' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function isEmpty() {
    return FALSE;
  }

}
