<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
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
class PretixDateWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\itk_pretix\Pretix\EventHelper $eventHelper */
    $eventHelper = \Drupal::service('itk_pretix.event_helper');
    /** @var \Drupal\itk_pretix\Plugin\Field\FieldType\PretixDate $item */
    $item = $items[$delta];

    $element['#element_validate'][] = [$this, 'validate'];

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
      '#attached' => [
        'library' => [
          'itk_pretix/itk-pretix',
        ],
      ],
      '#attributes' => ['class' => ['js-dawa-element']],
      '#required' => $element['#required'],
    ];

    $time_from = $items[$delta]->time_from ?? NULL;
    $time_to = $items[$delta]->time_from ?? NULL;

    if ($this->useSingleDate()) {
      $element['date_value'] = [
        '#title' => t('Date'),
        '#type' => 'datetime',
        '#date_time_element' => 'none', // hide date element
        '#default_value' => NULL,
        '#date_increment' => 1,
        '#date_timezone' => date_default_timezone_get(),
        '#required' => $element['#required'],
      ];
      if ($time_from) {
        $element['date_value']['#default_value'] = $this->createDefaultValue($time_from, $element['date_value']['#date_timezone']);
      }

      $element['start_time_value'] = [
        '#title' => t('Start time'),
        '#type' => 'datetime',
        '#date_date_element' => 'none', // hide date element
        '#default_value' => NULL,
        '#date_increment' => 1,
        '#date_timezone' => date_default_timezone_get(),
        '#required' => $element['#required'],
      ];

      if ($time_from) {
        $element['start_time_value']['#default_value'] = $this->createDefaultValue($time_from, $element['start_time_value']['#date_timezone']);
      }

      $element['end_time_value'] = [
        '#title' => t('End time'),
        '#type' => 'datetime',
        '#date_date_element' => 'none', // hide date element
        '#default_value' => NULL,
        '#date_increment' => 1,
        '#date_timezone' => date_default_timezone_get(),
        '#required' => $element['#required'],
      ];

      if ($time_to) {
        $element['end_time_value']['#default_value'] = $this->createDefaultValue($time_to, $element['end_time_value']['#date_timezone']);
      }

    }
    else {
      $element['time_from_value'] = [
        '#title' => t('Start time'),
        '#type' => 'datetime',
        '#default_value' => NULL,
        '#date_increment' => 1,
        '#date_timezone' => date_default_timezone_get(),
        '#required' => $element['#required'],
      ];

      if ($time_from) {
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

      if ($time_to) {
        $element['time_to_value']['#default_value'] = $this->createDefaultValue($time_to, $element['time_to_value']['#date_timezone']);
      }
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
        ? Url::fromRoute('itk_pretix.pretix_orders_date',
          [
            'node' => $item->getEntity()->id(),
            'uuid' => $item->uuid,
          ], [
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

      if (NULL !== $pretixOrdersUrl) {
        $url = $pretixOrdersUrl;
        $element['pretix_links']['pretix_orders_url'] = [
          '#type' => 'item',
          '#title' => $this->t('Pretix orders'),
          'value' => [
            '#title' => $this->t('Show pretix orders'),
            '#type' => 'link',
            '#url' => $url,
          ],
        ];
      }
    }

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if (1 === $this->fieldDefinition->getFieldStorageDefinition()->getCardinality()) {
      $element += [
        '#type' => 'fieldset',
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.
    $storage_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $user_timezone = new \DateTimeZone(date_default_timezone_get());

    foreach ($values as &$item) {

      if ($this->useSingleDate()) {
        if (isset($item['date_value'], $item['start_time_value'], $item['end_time_value'])
            && $item['date_value'] instanceof DrupalDateTime
            && $item['start_time_value'] instanceof DrupalDateTime
            && $item['end_time_value'] instanceof DrupalDateTime) {
          $timeFrom = clone $item['date_value'];
          [$hours, $minutes, $seconds] = array_map('intval', explode(':', $item['start_time_value']->format('H:i:s')));
          $timeFrom->setTime($hours, $minutes, $seconds);
          $item['time_from_value'] = $timeFrom;

          $timeTo = clone $item['date_value'];
          [$hours, $minutes, $seconds] = array_map('intval', explode(':', $item['end_time_value']->format('H:i:s')));
          $timeTo->setTime($hours, $minutes, $seconds);
          $item['time_to_value'] = $timeTo;
        }
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
  public static function defaultSettings() {
    return [
      'use_end_date' => FALSE,
      'spots_min' => 1,
      'spots_max' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['use_end_date'] = [
      '#title' => $this->t('Use end date'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('use_end_date'),
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
  public function settingsSummary() {
    if ($this->getSetting('use_end_date')) {
      $summary[] = $this->t('Use end date');
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
  public function errorElement(
    array $element,
    ConstraintViolationInterface $error,
    array $form,
    FormStateInterface $form_state
  ) {
    $propertyPath = preg_replace('/^\d+\./', '', $error->getPropertyPath());
    return $element[$propertyPath] ?? $element;
  }

  /**
   * #element_validate callback to ensure that
   *   the start date <= the end date.
   *   the number of spots are positive.
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
    $time_from = $element['time_from_value']['#value']['object'];
    $time_to = $element['time_to_value']['#value']['object'];

    if ($time_from instanceof DrupalDateTime && $time_to instanceof DrupalDateTime && $time_to < $time_from) {
      $form_state->setError($element['time_to_value'], $this->t('The end time cannot be before the start time'));
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
  protected function createDefaultValue($date, $timezone) {
    // The date was created and verified during field_load(), so it is safe to
    // use without further inspection.
    if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      $date->setDefaultDateTime();
    }
    $date->setTimezone(new \DateTimeZone($timezone));
    return $date;
  }

  private function useSingleDate() {
    header('content-type: text/plain'); echo var_export(['settings' => $this->getSettings()], true); die(__FILE__.':'.__LINE__.':'.__METHOD__);
    return TRUE !== $this->getSetting('use_end_date');
  }

}
