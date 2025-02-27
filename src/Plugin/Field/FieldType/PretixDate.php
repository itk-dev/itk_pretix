<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime\DateTimeComputed;
use Nicoeg\Dawa\Dawa;

/**
 * Plugin implementation of the 'pretix_date' field type.
 *
 * @FieldType(
 *   id = "pretix_date",
 *   label = @Translation("Pretix date"),
 *   description = @Translation("Provides values required by pretix API"),
 *   default_widget = "pretix_date_widget",
 *   default_formatter = "pretix_date_formatter",
 *   constraints = {"PretixDateConstraint" = {}}
 * )
 *
 * @property string uuid
 * @property string location
 * @property string address
 * @property DateTimeComputed registration_deadline
 * @property DateTimeComputed time_from
 * @property DateTimeComputed time_to
 * @property int spots
 * @property array data
 */
class PretixDate extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['uuid'] = DataDefinition::create('string')
      ->setLabel(t('UUID'))
      ->setRequired(TRUE);
    $properties['location'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Location'))
      ->setRequired(TRUE);
    $properties['address'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Address'))
      ->setRequired(TRUE);

    $properties['registration_deadline_value'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Registration deadline value'))
      ->setRequired(TRUE);
    $properties['registration_deadline'] = DataDefinition::create('any')
      ->setLabel(t('Computed registration deadline'))
      ->setDescription(t('The computed registration deadline DateTime object.'))
      ->setComputed(TRUE)
      ->setClass(DateTimeComputed::class)
      ->setSetting('date source', 'registration_deadline_value');

    $properties['time_from_value'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Time from value'))
      ->setRequired(TRUE);
    $properties['time_from'] = DataDefinition::create('any')
      ->setLabel(t('Computed time from'))
      ->setDescription(t('The computed time from DateTime object.'))
      ->setComputed(TRUE)
      ->setClass(DateTimeComputed::class)
      ->setSetting('date source', 'time_from_value');

    $properties['time_to_value'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Time to value'))
      ->setRequired(TRUE);
    $properties['time_to'] = DataDefinition::create('any')
      ->setLabel(t('Computed time to'))
      ->setDescription(t('The computed time to DateTime object.'))
      ->setComputed(TRUE)
      ->setClass(DateTimeComputed::class)
      ->setSetting('date source', 'time_to_value');

    $properties['spots'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Spots'))
      ->setRequired(TRUE);
    $properties['data'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Data'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'uuid' => [
          'type' => 'varchar',
          'length' => 128,
        ],
        'location' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'address' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'registration_deadline_value' => [
          'description' => 'The registration deadline value.',
          'type' => 'varchar',
          'length' => 20,
        ],
        'time_from_value' => [
          'description' => 'The time from value.',
          'type' => 'varchar',
          'length' => 20,
        ],
        'time_to_value' => [
          'description' => 'The time to value.',
          'type' => 'varchar',
          'length' => 20,
        ],
        'spots' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'data' => [
          'type' => 'blob',
          'size' => 'normal',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => [
        'value' => ['uuid'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $location = $this->get('location')->getValue();
    $timeFrom = $this->get('time_from_value')->getValue();
    $timeTo = $this->get('time_to_value')->getValue();
    return empty($location) || empty($timeFrom) || empty($timeTo);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if (empty($this->get('uuid')->getValue())) {
      $this->get('uuid')->setValue(\Drupal::service('uuid')->generate());
    }

    $address = $this->get('address')->getValue();
    if (!empty($address)) {
      try {
        $results = (new Dawa())->accessAddressSearch($address);
        if (isset($results[0]->adgangspunkt->koordinater)) {
          $this->addData(['coordinates' => $results[0]->adgangspunkt->koordinater]);
        }
      }
      catch (\Exception $exception) {
      }
    }
  }

  /**
   * Add data to this date.
   *
   * @param array $values
   *   The values to add.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function addData(array $values) {
    $field = $this->get('data');
    $value = $field->getValue() ?? [];
    $value = array_merge($value, $values);
    $field->setValue($value);
  }

  /**
   * Handler for hook_cloned_node_alter().
   */
  public function clonedNodeAlter() {
    $this->get('uuid')->setValue(NULL);
  }

}
