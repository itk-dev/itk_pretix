<?php

namespace Drupal\itk_pretix\Plugin\Field\FieldWidget;

use Drupal\Core\Database\Exception\EventException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\itk_pretix\NodeHelper;
use Drupal\itk_pretix\Pretix\EventHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'pretix_event_settings_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "pretix_event_settings_widget",
 *   module = "itk_pretix",
 *   label = @Translation("pretix event settings"),
 *   field_types = {
 *     "pretix_event_settings"
 *   }
 * )
 */
final class PretixEventSettingsWidget extends WidgetBase {

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
    private readonly NodeHelper $nodeHelper,
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
      $container->get(EventHelper::class),
      $container->get(NodeHelper::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state,
  ) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $items->getParent()->getEntity();
    $helper = $this->nodeHelper;
    $templateEvents = $helper->getTemplateEvents($node);
    $templateEventOptions = [];
    foreach ($templateEvents as $event) {
      $names = $event->getName();
      // @todo Try to get name from current locale.
      $name = reset($names);
      $templateEventOptions[$event->getSlug()] = sprintf('%s (%s)', $name, $event->getSlug());
    }

    $defaultValue = $items[$delta]->template_event ?? NULL;
    $emptyOption = t('Select template event');
    if (1 === $templateEvents->count()) {
      $defaultValue = $templateEvents->first()->getSlug();
      $emptyOption = NULL;
    }

    $element['template_event'] = [
      '#type' => 'select',
      '#options' => $templateEventOptions,
      '#title' => $this->t('Template event'),
      '#description' => $this->t('Select the template event to clone when creating the pretix event'),
      '#default_value' => $defaultValue,
      '#empty_option' => $emptyOption,
      '#required' => $element['#required'] && !empty($templateEventOptions),
    ];

    $element['synchronize_event'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Synchronize event in pretix'),
      '#description' => $this->t('If set, the pretix event will be updated when changes are made to the dates on this node'),
        // The default value is TRUE for new nodes.
      '#default_value' => NULL === $node->id() ? TRUE : ($items[$delta]->synchronize_event ?? NULL),
    ];

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if (1 === $this->fieldDefinition->getFieldStorageDefinition()->getCardinality()) {
      $element += [
        '#type' => 'details',
        '#open' => TRUE,
      ];
    }

    return $element;
  }

}
