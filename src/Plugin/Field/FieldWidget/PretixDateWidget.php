<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\itk_pretix\Pretix\EventHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'pretix_date_widget' widget.
 *
 * @FieldWidget(
 *   id = "pretix_date_widget",
 *   module = "itk_pretix",
 *   label = @Translation("pretix date widget"),
 *   field_types = {
 *     "pretix_date"
 *   }
 * )
 */
final class PretixDateWidget extends WidgetBase {

  /**
   * Date widget constructor.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    private readonly EventHelper $eventHelper,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get(EventHelper::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $eventHelper = $this->eventHelper;
    /** @var \Drupal\itk_pretix\Plugin\Field\FieldType\PretixDate $item */
    $item = $items[$delta];

    // If cardinality is 1, ensure a label is output for the field by using a
    // fieldset element.
    $element['#type'] = 1 === $this->fieldDefinition->getFieldStorageDefinition()->getCardinality()
      ? 'fieldset' : 'container';

    $element['#element_validate'][] = [$this, 'validate'];
    $element['#attributes']['class'][] = 'pretix-date-widget';
    if ($this->hideEndDate()) {
      $element['#attributes']['class'][] = 'hide-end-date';
    }
    $element['#attached']['library'][] = 'itk_pretix/itk-pretix';

    $element['uuid'] = [
      '#type' => 'hidden',
      '#default_value' => $item->uuid ?? '',
    ];

    $element['location'] = [
      '#type' => 'textfield',
      '#title' => t('Location'),
      '#default_value' => $item->location ?? '',
      '#size' => 45,
      '#required' => $element['#required'],
    ];
    $element['address'] = [
      '#type' => 'search',
      '#title' => t('Address'),
      '#default_value' => $item->address ?? '',
      '#size' => 45,
      '#attributes' => ['class' => ['js-dawa-element']],
      '#required' => $element['#required'],
    ];

    $element['registration_deadline_value'] = [
      '#title' => t('Registration deadline'),
      '#type' => 'datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_timezone' => date_default_timezone_get(),
      '#required' => $element['#required'],
    ];

    if ($items[$delta]->registration_deadline) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $registration_deadline */
      $registration_deadline = $items[$delta]->registration_deadline;
      $element['registration_deadline_value']['#default_value'] = $this->createDefaultValue($registration_deadline, $element['registration_deadline_value']['#date_timezone']);
    }

    $element['time_from_value'] = [
      '#title' => t('Start time'),
      '#type' => 'datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_timezone' => date_default_timezone_get(),
      '#required' => $element['#required'],
    ];

    if ($items[$delta]->time_from) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $time_from */
      $time_from = $items[$delta]->time_from;
      $element['time_from_value']['#default_value'] = $this->createDefaultValue($time_from, $element['time_from_value']['#date_timezone']);
    }

    $element['time_to_value'] = [
      '#title' => t('End time'),
      '#type' => 'datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_timezone' => date_default_timezone_get(),
      '#required' => $element['#required'],
    ];

    if ($items[$delta]->time_to) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $time_to */
      $time_to = $items[$delta]->time_to;
      $element['time_to_value']['#default_value'] = $this->createDefaultValue($time_to, $element['time_to_value']['#date_timezone']);
    }

    $element['spots'] = [
      '#type' => 'number',
      '#title' => t('Number of spots'),
      '#default_value' => $item->spots ?? NULL,
      '#size' => 3,
      '#required' => $element['#required'],
      '#min' => $this->getSetting('spots_min'),
      '#max' => $this->getSetting('spots_max'),
    ];

    if (isset($item->uuid)) {
      $pretixOrdersUrl = $item->getEntity()->id()
              ? Url::fromRoute(
              'itk_pretix.pretix_orders_date',
              [
                'node' => $item->getEntity()->id(),
                'uuid' => $item->uuid,
              ],
              [
                'absolute' => TRUE,
              ]
          )
              : NULL;

      $element['pretix_links'] = [
        '#type' => 'details',
        '#title' => $this->t('pretix'),
      ];

      $data = array_merge(
            $item->data ?? [],
            $eventHelper->loadPretixSubEventInfo($item) ?? []
        );

      if (isset($data['data']['pretix_subevent_url'])) {
        $url = Url::fromUri($data['data']['pretix_subevent_url']);
        $element['pretix_links']['pretix_subevent_url'] = [
          '#type' => 'item',
          '#title' => $this->t('Pretix sub-event url'),
          '#description' => $this->t('The pretix sub-event url'),
          'value' => [
            '#title' => $url->getUri(),
            '#type' => 'link',
            '#url' => $url,
          ],
        ];
      }

      if (isset($data['data']['pretix_subevent_shop_url'])) {
        $url = Url::fromUri($data['data']['pretix_subevent_shop_url']);
        $element['pretix_links']['pretix_subevent_shop_url'] = [
          '#type' => 'item',
          '#title' => $this->t('Pretix sub-event shop url'),
          '#description' => $this->t('The pretix sub-event shop url'),
          'value' => [
            '#title' => $url->getUri(),
            '#type' => 'link',
            '#url' => $url,
          ],
        ];
      }

      $url = Url::fromRoute('itk_pretix.pretix_exporter_event', ['node' => $item->getEntity()->id()]);
      $element['pretix_links']['pretix_orders'] = [
        '#type' => 'item',
        '#title' => $this->t('Exports'),
        'value' => [
          '#title' => $this->t('Exports'),
          '#type' => 'link',
          '#url' => $url,
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.
    $storage_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $user_timezone = new \DateTimeZone(date_default_timezone_get());

    foreach ($values as &$item) {
      if (!empty($item['registration_deadline_value']) && $item['registration_deadline_value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $time_from */
        $registration_deadline = $item['registration_deadline_value'];

        // Adjust the date for storage.
        $item['registration_deadline_value'] = $registration_deadline->setTimezone($storage_timezone)->format($storage_format);
      }

      if (!empty($item['time_from_value']) && $item['time_from_value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $time_from */
        $time_from = $item['time_from_value'];

        // Adjust the date for storage.
        $item['time_from_value'] = $time_from->setTimezone($storage_timezone)->format($storage_format);
      }

      if (!empty($item['time_to_value']) && $item['time_to_value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $time_to */
        $time_to = $item['time_to_value'];

        // Adjust the date for storage.
        $item['time_to_value'] = $time_to->setTimezone($storage_timezone)->format($storage_format);
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function defaultSettings() {
    return [
      'hide_end_date' => FALSE,
      'spots_min' => 1,
      'spots_max' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['hide_end_date'] = [
      '#title' => $this->t('Hide end date'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('hide_end_date'),
    ];

    $element['spots_min'] = [
      '#title' => $this->t('Spots min'),
      '#type' => 'number',
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $this->getSetting('spots_min'),
    ];

    $element['spots_max'] = [
      '#title' => $this->t('Spots max'),
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => $this->getSetting('spots_max'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function settingsSummary() {
    if ($this->hideEndDate()) {
      $summary[] = $this->t('Hide end date');
    }

    $summary[] = $this->t('Spots: @min-@max', [
      '@min' => $this->getSetting('spots_min'),
      '@max' => $this->getSetting('spots_max'),
    ]);

    return $summary;
  }

  /**
   * Round seconds to nearest hour.
   *
   * @param int $seconds
   *   A timestamp.
   *
   * @return float|int
   *   A rounded timestamp.
   */
  private function roundedTime($seconds) {
    return round($seconds / 3600) * 3600;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function errorElement(
    array $element,
    ConstraintViolationInterface $error,
    array $form,
    FormStateInterface $form_state,
  ) {
    $propertyPath = preg_replace('/^\d+\./', '', $error->getPropertyPath());
    return $element[$propertyPath] ?? $element;
  }

  /**
   * Element validate callback.
   *
   * Ensures that
   *   * the start date <= the end date.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $time_from = $element['time_from_value']['#value']['object'] ?? NULL;
    $time_to = $element['time_to_value']['#value']['object'] ?? NULL;

    if ($time_from instanceof DrupalDateTime && $time_to instanceof DrupalDateTime && $time_to < $time_from) {
      $form_state->setError($element['time_to_value'], $this->t('The end time cannot be before the start time'));
    }

    $registration_deadline = $element['registration_deadline_value']['#value']['object'] ?? NULL;
    if ($registration_deadline instanceof DrupalDateTime && $time_from instanceof DrupalDateTime && $time_from < $registration_deadline) {
      $form_state->setError($element['registration_deadline_value'], $this->t('The registration deadline must be before the start time'));
    }
  }

  /**
   * Creates a date object for use as a default value.
   *
   * This will take a default value, apply the proper timezone for display in
   * a widget, and set the default time for date-only fields.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The UTC default date.
   * @param string $timezone
   *   The timezone to apply.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A date object for use as a default value in a field widget.
   */
  protected function createDefaultValue(DrupalDateTime $date, $timezone) {
    // The date was created and verified during field_load(), so it is safe to
    // use without further inspection.
    if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      $date->setDefaultDateTime();
    }
    $date->setTimezone(new \DateTimeZone($timezone));
    return $date;
  }

  /**
   * Decide if end date should be hidden.
   */
  private function hideEndDate() {
    return TRUE === $this->getSetting('hide_end_date');
  }

}
