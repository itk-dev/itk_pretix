<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\itk_pretix\Plugin\Field\FieldType\PretixDate;
use Drupal\itk_pretix\Pretix\EventHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'pretix_date_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "pretix_date_formatter",
 *   label = @Translation("pretix date formatter"),
 *   field_types = {
 *     "pretix_date"
 *   }
 * )
 */
final class PretixDateFormatter extends FormatterBase {

  /**
   * Class constructor.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    private readonly EventHelper $eventHelper,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get(EventHelper::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $dates = iterator_to_array($items);

    // @todo Get this from widget settings.
    $sortField = 'time_from';
    $sortDirection = 'desc';

    if (NULL !== $sortField) {
      // Sort ascending.
      usort($dates, static fn(PretixDate $a, PretixDate $b) => $a->{$sortField} <=> $b->{$sortField});
      // Reverse if requested.
      if (0 === strcasecmp('desc', $sortDirection)) {
        $dates = array_reverse($dates);
      }
    }

    foreach ($dates as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'itk_pretix_date_entry',
        '#variables' => [
          'uuid' => $item->uuid,
          'name' => $item->getName(),
          'entity' => $items->getEntity(),
          'location' => $item->location,
          'address' => $item->address,
          'registration_deadline' => $item->registration_deadline,
          'time_from' => $item->time_from,
          'time_to' => $item->time_to,
          'spots' => $item->spots,
          'data' => array_merge(
                $item->data ?? [],
                $this->eventHelper->loadPretixSubEventInfo($item) ?? []
          ),
        ],
      ];
    }

    return $elements;
  }

}
