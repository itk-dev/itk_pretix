<?php

namespace Drupal\itk_pretix;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\itk_pretix\Pretix\EventHelper;

/**
 * Node helper.
 */
class NodeHelper {
  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The event helper.
   *
   * @var \Drupal\itk_pretix\Pretix\EventHelper
   */
  private $eventHelper;

  /**
   * Constructor.
   *
   * @param \Drupal\itk_pretix\Pretix\EventHelper $eventHelper
   *   The event helper.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EventHelper $eventHelper, MessengerInterface $messenger) {
    $this->eventHelper = $eventHelper;
    $this->setMessenger($messenger);
  }

  /**
   * Get a date item by uuid.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $uuid
   *   The date item uuid.
   *
   * @return \Drupal\itk_pretix\Plugin\Field\FieldType\PretixDate|null
   *   The date item if any.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getDateItem(NodeInterface $node, string $uuid) {
    $field = $this->getFieldByType($node, 'pretix_date');

    if (NULL !== $field) {
      foreach ($field as $item) {
        if ($item->uuid === $uuid) {
          return $item;
        }
      }
    }

    return NULL;
  }

  /**
   * Load a PretixDateFieldType by uuid.
   *
   * @param string $uuid
   *   The uuid.
   *
   * @return null|PretixDateFieldType
   *   The date item.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function loadDateItem(string $uuid) {
    $query = \Drupal::entityQuery('node')
      ->condition('field_pretix_dates.uuid', $uuid);

    $nids = $query->execute();
    // We only want one node (and ignore non-unique unique ids for now).
    $node = Node::load(reset($nids));

    return $node ? $this->getDateItem($node, $uuid) : NULL;
  }

  /**
   * Get template events available for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Doctrine\Common\Collections\Collection
   *   A collection of template events.
   */
  public function getTemplateEvents(NodeInterface $node) {
    $field = $this->getFieldByType($node, 'pretix_date');
    $dateCardinality = $field->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
    $events = [];

    $client = $this->eventHelper->getPretixClient($node);
    $config = $this->eventHelper->getPretixConfiguration();
    $templateEventSlugs = array_unique(array_filter(array_map('trim', explode(PHP_EOL, $config['template_event_slugs'] ?? ''))));
    foreach ($templateEventSlugs as $slug) {
      try {
        $event = $client->getEvent($slug);
        $events[] = $event;
      }
      catch (\Exception $exception) {
      }
    }

    return new ArrayCollection($events);
  }

  /**
   * Synchronize a node with an event in pretix.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $action
   *   The action triggering the synchronization.
   */
  public function synchronizeEvent(NodeInterface $node, string $action) {
    $settings = $this->getPretixSettings($node);
    if (NULL === $settings || !$settings->synchronize_event) {
      return;
    }

    $dates = $this->getPretixDates($node);
    if (NULL !== $dates && !$dates->isEmpty()) {
      try {
        $this->eventHelper->syncronizePretixEvent(
          $node,
          [
            'dates' => $dates,
            'settings' => $settings,
          ]
        );
      }
      catch (\Exception $exception) {
        $this->messenger->addError($this->t('There was a problem updating the event in pretix. Please verify in pretix that all settings for the event are correct.'));
        $this->messenger->addError($exception->getMessage());
        return;
      }

      $pretixEventUrl = $this->eventHelper->getPretixEventUrl($node);
      $this->messenger->addStatus($this->t('Successfully updated <a href="@pretix_event_url">the event in pretix</a>.', [
        '@pretix_event_url' => $pretixEventUrl,
      ]));

      $live = $node->isPublished();

      // Allow modules to change shop state.
      \Drupal::moduleHandler()->alter('itk_pretix_shop_live', $live, $node);

      try {
        $result = $this->eventHelper->setEventLive($node, $live);

        $message = $live
          ? t('Successfully set <a href="@pretix_event_url">the pretix event</a> live.', [
            '@pretix_event_url' => $pretixEventUrl,
          ])
            : t('Successfully set <a href="@pretix_event_url">the pretix event</a> not live.', [
              '@pretix_event_url' => $pretixEventUrl,
            ]);
        $this->messenger->addStatus($message);
      }
      catch (\Exception $exception) {
        $error = $exception->getMessage();
        $message = $live
          ? t('Error setting <a href="@pretix_event_url">the pretix event</a> live: @error', [
            '@pretix_event_url' => $pretixEventUrl,
            '@error' => $error,
          ])
              : t('Error setting <a href="@pretix_event_url">the pretix event</a> not live: @error', [
                '@pretix_event_url' => $pretixEventUrl,
                '@errors' => $error,
              ]);
        $this->messenger->addError($message);
      }
    }
  }

  /**
   * Handler for hook_cloned_node_alter().
   *
   * @param \Drupal\node\NodeInterface $node
   *   The cloned node.
   */
  public function clonedNodeAlter(NodeInterface $node) {
    $dates = $this->getFieldByType($node, 'pretix_date');

    if (NULL !== $dates) {
      /** @var \Drupal\itk_pretix\Plugin\Field\FieldType\PretixDate $date */
      foreach ($dates as $date) {
        $date->clonedNodeAlter();
      }
    }
  }

  /**
   * Get dates from a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   A list of dates if a pretix_date field exists on the node.
   */
  private function getPretixDates(NodeInterface $node) {
    $items = $this->getFieldByType($node, 'pretix_date');

    if (NULL !== $items) {
      foreach ($items as $item) {
        foreach (['time_from', 'time_to'] as $key) {
          if (isset($item->{$key}) && is_string($item->{$key})) {
            $item->{$key} = new DrupalDateTime($item->{$key});
          }
        }
      }

      return $items;
    }

    return NULL;
  }

  /**
   * Get pretix settings for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\itk_pretix\Plugin\Field\FieldType\PretixEventSettings|null
   *   The settings if a pretix_event_settings field exists on the node.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getPretixSettings(NodeInterface $node) {
    $items = $this->getFieldByType($node, 'pretix_event_settings');

    return NULL !== $items ? $items->first() : NULL;
  }

  /**
   * Decide if node must be synchronized with pretix.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return bool
   *   The result.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getSynchronizeWithPretix(NodeInterface $node) {
    return $this->getPretixSettings($node)->synchronize_event ?? FALSE;
  }

  /**
   * Get a node field by type.
   *
   * Returns only the first matching field found.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $fieldType
   *   The field type.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   *   A field with the specified type if found.
   */
  private function getFieldByType(NodeInterface $node, string $fieldType) {
    $fields = $node->getFields();
    foreach ($fields as $field) {
      if ($fieldType === $field->getFieldDefinition()->getType()) {
        return $field;
      }
    }

    return NULL;
  }

}
