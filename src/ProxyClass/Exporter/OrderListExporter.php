<?php

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use ItkDev\Pretix\Api\Client;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\itk_pretix\Exporter\ExporterInterface;

namespace Drupal\itk_pretix\ProxyClass\Exporter {

  /**
   * Provides a proxy class for \Drupal\itk_pretix\Exporter\OrderListExporter.
   *
   * @see \Drupal\Component\ProxyBuilder
   */
  class OrderListExporter implements ExporterInterface {

    use \Drupal\Core\DependencyInjection\DependencySerializationTrait;

    /**
     * The id of the original proxied service.
     *
     * @var string
     */
    protected $drupalProxyOriginalServiceId;

    /**
     * The real proxied service, after it was lazy loaded.
     *
     * @var \Drupal\itk_pretix\Exporter\OrderListExporter
     */
    protected $service;

    /**
     * The service container.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * Constructs a ProxyClass Drupal proxy object.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *   The container.
     * @param string $drupal_proxy_original_service_id
     *   The service ID of the original service.
     */
    public function __construct(ContainerInterface $container, $drupal_proxy_original_service_id) {
      $this->container = $container;
      $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
    }

    /**
     * Lazy loads the real service from the container.
     *
     * @return object
     *   Returns the constructed real service.
     */
    protected function lazyLoadItself() {
      if (!isset($this->service)) {
        $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
      }

      return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
      return $this->lazyLoadItself()->buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function getId() {
      return $this->lazyLoadItself()->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
      return $this->lazyLoadItself()->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
      return $this->lazyLoadItself()->getFormId();
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      return $this->lazyLoadItself()->submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function processInputParameters(array $parameters) {
      return $this->lazyLoadItself()->processInputParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function setPretixClient(Client $client) {
      return $this->lazyLoadItself()->setPretixClient($client);
    }

    /**
     * {@inheritdoc}
     */
    public function setEventInfo(array $eventInfo) {
      return $this->lazyLoadItself()->setEventInfo($eventInfo);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
      FormBase::create($container);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
      return $this->lazyLoadItself()->validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function setConfigFactory(ConfigFactoryInterface $config_factory) {
      return $this->lazyLoadItself()->setConfigFactory($config_factory);
    }

    /**
     * {@inheritdoc}
     */
    public function resetConfigFactory() {
      return $this->lazyLoadItself()->resetConfigFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestStack(RequestStack $request_stack) {
      return $this->lazyLoadItself()->setRequestStack($request_stack);
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep() {
      return $this->lazyLoadItself()->__sleep();
    }

    /**
     * {@inheritdoc}
     */
    public function __wakeup() {
      return $this->lazyLoadItself()->__wakeup();
    }

    /**
     * {@inheritdoc}
     */
    public function setLinkGenerator(LinkGeneratorInterface $generator) {
      return $this->lazyLoadItself()->setLinkGenerator($generator);
    }

    /**
     * {@inheritdoc}
     */
    public function setLoggerFactory(LoggerChannelFactoryInterface $logger_factory) {
      return $this->lazyLoadItself()->setLoggerFactory($logger_factory);
    }

    /**
     * {@inheritdoc}
     */
    public function setMessenger(MessengerInterface $messenger) {
      return $this->lazyLoadItself()->setMessenger($messenger);
    }

    /**
     * {@inheritdoc}
     */
    public function messenger() {
      return $this->lazyLoadItself()->messenger();
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirectDestination(RedirectDestinationInterface $redirect_destination) {
      return $this->lazyLoadItself()->setRedirectDestination($redirect_destination);
    }

    /**
     * {@inheritdoc}
     */
    public function setStringTranslation(TranslationInterface $translation) {
      return $this->lazyLoadItself()->setStringTranslation($translation);
    }

    /**
     * {@inheritdoc}
     */
    public function setUrlGenerator(UrlGeneratorInterface $generator) {
      return $this->lazyLoadItself()->setUrlGenerator($generator);
    }

  }

}
