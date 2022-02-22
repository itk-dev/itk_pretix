<?php

/**
 * @file
 * Hooks provided by the ITK Pretix module.
 */

use Drupal\itk_pretix\Pretix\EventHelper;
use Drupal\node\NodeInterface;

/**
 * Perform alterations on liveness of the pretix event shop.
 */
function hook_itk_pretix_shop_live_alter(&$live, NodeInterface $node) {
  if ($node->field_cancelled->value) {
    $live = FALSE;
  }
}

/**
 * Perform alterations on pretix event api data.
 *
 * @see https://docs.pretix.eu/en/latest/api/resources/events.html
 */
function hookitk_pretix_event_data_alter(array &$data, NodeInterface $node, array $context) {
  $isNewevent = $context['is_new_event'] ?? FALSE;

  $data['presale_end'] = (new Date())->format(EventHelper::DATETIME_FORMAT);
}

/**
 * Perform alterations on pretix sub-event api data.
 *
 * @see https://docs.pretix.eu/en/latest/api/resources/subevents.html
 */
function hook_itk_pretix_subevent_data_alter(array &$data, NodeInterface $node, array $context) {
  $isNewSubevent = $context['is_new_subevent'] ?? FALSE;
  /** @var ?\ItkDev\Pretix\Api\Entity\Event $event */
  $event = $context['event'] ?? NULL;
  /** @var ?\Drupal\itk_pretix\Plugin\Field\FieldType\PretixDate $pretixDate */
  $pretixDate = $context['pretix_date'] ?? NULL;

  $data['presale_end'] = (new Date())->format(EventHelper::DATETIME_FORMAT);
}
