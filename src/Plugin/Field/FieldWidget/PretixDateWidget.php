<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'pretix_date_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "pretix_date_widget_type",
 *   module = "itk_pretix",
 *   label = @Translation("Pretix date widget type"),
 *   field_types = {
 *     "pretix_date_field_type"
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

    $element['uuid'] = [
      '#type' => 'hidden',
      '#default_value' => $item->uuid ?? '',
    ];

    $element['location'] = [
      '#type' => 'textfield',
      '#title' => t('Location'),
      '#default_value' => $item->location ?? '',
      '#size' => 45,
      '#required' => TRUE,
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
      '#required' => TRUE,
    ];

    if ($item->time_from) {
      $datePartsFrom = explode(' ', $item->time_from);
    }
    $element['time_from'] = [
      '#type' => 'datetime',
      '#title' => t('Start time'),
      '#default_value' => isset($datePartsFrom) ? DrupalDateTime::createFromFormat('Y-m-d H:i:s', $datePartsFrom[0] . ' ' . $datePartsFrom[1], $datePartsFrom[2]) : DrupalDateTime::createFromTimestamp($this->roundedTime(time())),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_date_format' => 'd/m/Y',
      '#date_time_format' => 'H:i',
      '#size' => 15,
      '#required' => TRUE,
    ];

    if ($item->time_to) {
      $datePartsTo = explode(' ', $item->time_to);
    }
    $element['time_to'] = [
      '#type' => 'datetime',
      '#title' => t('End time'),
      '#default_value' => isset($datePartsTo) ? DrupalDateTime::createFromFormat('Y-m-d H:i:s', $datePartsTo[0] . ' ' . $datePartsTo[1], $datePartsTo[2]) : DrupalDateTime::createFromTimestamp($this->roundedTime(time())),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_date_format' => 'd/m/Y',
      '#date_time_format' => 'H:i',
      '#size' => 15,
      '#required' => TRUE,
    ];
    $element['spots'] = [
      '#type' => 'number',
      '#title' => t('Number of spots'),
      '#default_value' => $item->spots ?? NULL,
      '#size' => 3,
      '#required' => TRUE,
    ];

    if (isset($item->uuid)) {
      $pretixOrdersListUrl = $item->getEntity()->id()
        ? Url::fromRoute('itk_pretix.pretix_orders_date',
          [
            'node' => $item->getEntity()->id(),
            'uuid' => $item->uuid,
          ], [
            'absolute' => TRUE,
          ]
        )
        : NULL;

      $element['data'] = [
        '#theme' => 'itk_pretix_date_data',
        '#data' => array_merge(
          $item->data ?? [],
          $eventHelper->loadPretixSubEventInfo($item) ?? [],
          [
            'pretix_orders_list_url' => $pretixOrdersListUrl,
          ]
        ),
      ];
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

}