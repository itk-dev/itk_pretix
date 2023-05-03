<?php

namespace Drupal\itk_pretix\Exporter;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\itk_pretix\Pretix\EventHelper;
use ItkDev\Pretix\Api\Client;
use ItkDev\Pretix\Api\Entity\CheckInList;

/**
 * Abstract exporter.
 */
abstract class AbstractExporter extends FormBase implements ExporterInterface {
  /**
   * The exporter id.
   *
   * @var string
   */
  protected static $id;

  /**
   * The exporter name.
   *
   * @var string
   */
  protected static $name;

  /**
   * The pretix client.
   *
   * @var \ItkDev\Pretix\Api\Client
   */
  protected $client;

  /**
   * The event info.
   *
   * @var array
   */
  protected $eventInfo;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    if (NULL === static::$id) {
      throw new \RuntimeException(sprintf('Property id not defined in class %s', static::class));
    }
    return static::$id;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    if (NULL === static::$name) {
      throw new \RuntimeException(sprintf('Property name not defined in class %s', static::class));
    }
    return static::$name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'itk_pretix_exporter_' . $this->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    throw new \RuntimeException();
  }

  /**
   * Process input parameters.
   */
  public function processInputParameters(array $parameters) {
    unset($parameters['op'], $parameters['form_build_id'], $parameters['form_token'], $parameters['form_id']);

    return $parameters;
  }

  /**
   * Get pretix client.
   */
  public function setPretixClient(Client $client) {
    $this->client = $client;

    return $this;
  }

  /**
   * Set event info.
   */
  public function setEventInfo(array $eventInfo) {
    $this->eventInfo = $eventInfo;

    return $this;
  }

  /**
   * Build check-in list field.
   */
  protected function buildCheckInListField(array $element = []) {
    $options = [];
    $checkInLists = $this->getCheckInLists();
    foreach ($checkInLists as $checkInList) {
      $options[$checkInList->getId()] = $checkInList->getName();
    }
    $defaultValue = $checkInLists->first() ? $checkInLists->first()->getId() : NULL;

    return $element + [
      '#type' => 'select',
      '#title' => $this->t('Check-in list'),
      '#options' => $options,
      '#default_value' => $defaultValue,
    ];
  }

  /**
   * Build questions field.
   */
  protected function buildQuestionsField(array $element = []) {
    $options = [];
    $questions = $this->getQuestions();
    foreach ($questions as $question) {
      $text = $question->getQuestion();
      if (is_array($text)) {
        // Get first value in multi-lingual string.
        $text = reset($text);
      }
      $options[$question->getId()] = $text;
    }
    $defaultValue = array_keys($options);

    return $element + [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Questions'),
      '#options' => $options,
      '#default_value' => $defaultValue,
    ];
  }

  /**
   * Memoization of check-in lists from api.
   *
   * @var array
   */
  protected static $checkInLists = [];

  /**
   * Get check-in lists for the current event.
   *
   * @return \Doctrine\Common\Collections\Collection|CheckInList[]
   *   The check-in lists.
   */
  private function getCheckInLists() {
    $event = $this->eventInfo[EventHelper::PRETIX_EVENT_SLUG];

    if (!isset(self::$checkInLists[$event])) {
      $defaultName = 'exporter check-in list (do not delete)';
      $isDefaultList = static function (CheckInList $list) use ($defaultName) {
        return $defaultName === $list->getName();
      };
      $checkInLists = $this->client->getCheckInLists($event);
      $defaultList = $checkInLists->filter($isDefaultList)->first();
      if (!$defaultList) {
        $defaultList = $this->client->createCheckInList(
              $this->eventInfo[EventHelper::PRETIX_EVENT_SLUG],
              [
                'name' => $defaultName,
              ]
          );
        $checkInLists->add($defaultList);
      }

      [$head, $tail] = $checkInLists->partition(static function ($key, $list) use ($isDefaultList) {
          return $isDefaultList($list);
      });

      // Make sure that the default list is first in collection.
      self::$checkInLists[$event] = new ArrayCollection(
            array_merge($head->toArray(FALSE), $tail->toArray(FALSE))
        );
    }

    return self::$checkInLists[$event];
  }

  /**
   * Memoization of questions from api.
   *
   * @var array
   */
  protected static $questions = [];

  /**
   * Get questions for the current event.
   *
   * @return \Doctrine\Common\Collections\Collection|Question[]
   *   The questions.
   */
  private function getQuestions() {
    $event = $this->eventInfo[EventHelper::PRETIX_EVENT_SLUG];

    if (!isset(self::$questions[$event])) {
      self::$questions[$event] = $this->client->getQuestions($event);
    }

    return self::$questions[$event];
  }

}
