<?php

namespace Drupal\itk_pretix\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\itk_pretix\Exporter\ManagerInterface as ExporterManagerInterface;
use Drupal\itk_pretix\Pretix\EventHelper;
use Drupal\itk_pretix\Pretix\OrderHelper;
use ItkDev\Pretix\Api\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A configuration form for the pretix module.
 */
final class PretixConfigForm extends ConfigFormBase {
  /**
   * The event helper.
   *
   * @var \Drupal\itk_pretix\Pretix\EventHelper
   */
  private $eventHelper;

  /**
   * The order helper.
   *
   * @var \Drupal\itk_pretix\Pretix\OrderHelper
   */
  private $orderHelper;

  /**
   * The exporter manager.
   *
   * @var ExporterManager
   */
  private $exporterManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EventHelper $eventHelper, OrderHelper $orderHelper, ExporterManagerInterface $exporterManager) {
    parent::__construct($config_factory);
    $this->eventHelper = $eventHelper;
    $this->orderHelper = $orderHelper;
    $this->exporterManager = $exporterManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('config.factory'),
          $container->get('itk_pretix.event_helper'),
          $container->get('itk_pretix.order_helper'),
          $container->get('itk_pretix.exporter_manager')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'itk_pretix.pretixconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pretix_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('itk_pretix.pretixconfig');

    $form['pretix_url'] = [
      '#type' => 'url',
      '#description' => $this->t('The full pretix url, e.g. https://pretix.eu/'),
      '#title' => $this->t('pretix url'),
      '#size' => 64,
      '#default_value' => $config->get('pretix_url') ?? 'https://pretix.eu/',
      '#required' => TRUE,
    ];

    $form['organizer_slug'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organizer (short form)'),
      '#description' => $this->t('The part of the url immediately after "/control/organizer/", e.g. "organizer-short-name" in "https://pretix.eu/control/organizer/organizer-short-name/"'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('organizer_slug'),
      '#required' => TRUE,
    ];

    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#description' => $this->t('The pretix API token (see https://docs.pretix.eu/en/latest/api/tokenauth.html#obtaining-an-api-token)'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('api_token'),
      '#required' => TRUE,
    ];

    $form['template_event_slugs'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Template events'),
      '#description' => $this->t('Template event short forms. One per line.'),
      '#default_value' => $config->get('template_event_slugs'),
      '#required' => TRUE,
    ];

    $eventExporterOptions = [];
    foreach ($this->exporterManager->getEventExporters() as $exporter) {
      $eventExporterOptions[$exporter->getId()] = $exporter->getName();
    }

    $form['exporters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Exporters'),

      'event_exporters_message' => [
        '#type' => 'textarea',
        '#title' => $this->t('Event exporters message'),
        '#description' => $this->t('Message to show on the event exporters page.'),
        '#default_value' => $config->get('event_exporters_message'),
      ],

      'event_exporters_enabled' => [
        '#type' => 'checkboxes',
        '#title' => $this->t('Enabled event exporters'),
        '#description' => $this->t('Select event exporters to enable'),
        '#options' => $eventExporterOptions,
        '#multiple' => TRUE,
        '#default_value' => $config->get('event_exporters_enabled'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('itk_pretix.pretixconfig')
      ->set('pretix_url', $form_state->getValue('pretix_url'))
      ->set('organizer_slug', $form_state->getValue('organizer_slug'))
      ->set('api_token', $form_state->getValue('api_token'))
      ->set('template_event_slugs', $form_state->getValue('template_event_slugs'))
      ->set('event_exporters_message', $form_state->getValue('event_exporters_message'))
      ->set('event_exporters_enabled', array_filter($form_state->getValue('event_exporters_enabled')))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    try {
      $client = new Client([
        'url' => $form_state->getValue('pretix_url'),
        'organizer' => $form_state->getValue('organizer_slug'),
        'api_token' => $form_state->getValue('api_token'),
      ]);
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('pretix_url', $this->t('Cannot create pretix api client'));
      return;
    }

    try {
      $client->getOrganizers();
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('pretix_url', $this->t('Cannot connect to pretix api'));
      return;
    }

    $templateEventSlugs = array_unique(array_filter(array_map('trim', explode(PHP_EOL, $form_state->getValue('template_event_slugs')))));
    $templateEvents = [];
    foreach ($templateEventSlugs as $eventSlug) {
      try {
        $event = $client->getEvent($eventSlug);
        $templateEvents[$event->getSlug()] = $event;
      }
      catch (\Exception $exception) {
      }
    }

    $invalidTemplateEventSlugs = array_diff($templateEventSlugs, array_keys($templateEvents));
    if (!empty($invalidTemplateEventSlugs)) {
      $form_state->setErrorByName(
            'template_event_slugs',
            $this->t(
                'Invalid template event slugs: @event_slugs',
                ['@event_slugs' => implode(', ', $invalidTemplateEventSlugs)]
            )
        );
      return;
    }

    foreach ($templateEvents as $event) {
      $error = $this->eventHelper->validateTemplateEvent($event, $client);
      if (is_array($error)) {
        // We only show the first error message.
        $message = reset($error);
        $form_state->setErrorByName(
              'template_event_slugs',
              $this->t('Event @event_slug is not a valid template: @message', [
                '@event_slug' => $event->getSlug(),
                '@message' => $message,
              ])
          );
        return;
      }
    }

    try {
      $this->orderHelper->ensureWebhook($client);
      $this->messenger->addStatus($this->t('pretix webhook created'));
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('pretix_url', $this->t('Cannot create webhook in pretix'));
      return;
    }

  }

}
