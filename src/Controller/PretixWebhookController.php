<?php

namespace Drupal\itk_pretix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\itk_pretix\Pretix\OrderHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Pretix webhook controller.
 */
class PretixWebhookController extends ControllerBase {
  /**
   * The pretix order helper.
   *
   * @var \Drupal\itk_pretix\Pretix\OrderHelper
   */
  private $orderHelper;

  /**
   * The node helper.
   *
   * @var \Drupal\itk_pretix\NodeHelper
   */
  private $nodeHelper;

  /**
   * The event helper.
   *
   * @var \Drupal\itk_pretix\Pretix\EventHelper
   */
  private $eventHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->orderHelper = $container->get('itk_pretix.order_helper');
    $instance->nodeHelper = $container->get('itk_pretix.node_helper');
    $instance->eventHelper = $container->get('itk_pretix.event_helper');

    return $instance;
  }

  /**
   * Handle pretix webhook.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The payload.
   *
   * @see https://docs.pretix.eu/en/latest/api/webhooks.html#receiving-webhooks
   */
  public function main(Request $request) {
    $payload = json_decode($request->getContent(), TRUE);
    if (empty($payload)) {
      throw new BadRequestHttpException('Invalid or empty payload');
    }

    $action = $payload['action'] ?? NULL;
    switch ($action) {
      case OrderHelper::PRETIX_EVENT_ORDER_PAID:
      case OrderHelper::PRETIX_EVENT_ORDER_CANCELED:
        $this->handleOrderUpdated($payload, $action);
        break;
    }

    return new JsonResponse($payload);
  }

  /**
   * Handle order updated.
   *
   * @param array $payload
   *   The payload.
   * @param string $action
   *   The action.
   *
   * @return array
   *   The payload.
   *
   * @throws \InvalidMergeQueryException
   */
  private function handleOrderUpdated(array $payload, $action) {
    $organizerSlug = $payload['organizer'] ?? NULL;
    $eventSlug = $payload['event'] ?? NULL;
    $orderCode = $payload['code'] ?? NULL;

    $node = $this->orderHelper->getNode($organizerSlug, $eventSlug);

    if (NULL !== $node) {
      switch ($action) {
        case OrderHelper::PRETIX_EVENT_ORDER_PAID:
        case OrderHelper::PRETIX_EVENT_ORDER_CANCELED:
          break;

        default:
          return $payload;
      }

      $client = $this->orderHelper->getPretixClient($node);
      try {
        $order = $this->orderHelper
          ->setPretixClient($client)
          ->getOrder($organizerSlug, $eventSlug, $orderCode);
      }
      catch (\Exception $exception) {
        throw new HttpException(500, 'Cannot get order', $exception);
      }

      if ($this->nodeHelper->getSynchronizeWithPretix($node)) {
        $processed = [];
        foreach ($order->getPositions() as $position) {
          $subEvent = $position->getSubevent();
          if (isset($processed[$subEvent->getId()])) {
            continue;
          }

          $quotas = $this->orderHelper->getSubEventAvailability($subEvent);
          $subEventData['availability'] = $quotas->toArray();
          $item = $this->eventHelper->loadDateItem($subEvent);
          $this->orderHelper->addPretixSubEventInfo($item, $subEvent, $subEventData);
        }
      }

      // Update availability on event node.
      $this->eventHelper->updateEventAvailability($node);
    }

    return $payload;
  }

}
