<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\itk_pretix\Exporter\CheckInListPdfExporter' "modules/contrib/itk_pretix/src".
 */

namespace Drupal\itk_pretix\ProxyClass\Exporter {

    /**
     * Provides a proxy class for \Drupal\itk_pretix\Exporter\CheckInListPdfExporter.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class CheckInListPdfExporter implements \Drupal\itk_pretix\Exporter\ExporterInterface
    {

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
         * @var \Drupal\itk_pretix\Exporter\CheckInListPdfExporter
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
        public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $drupal_proxy_original_service_id)
        {
            $this->container = $container;
            $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
        }

        /**
         * Lazy loads the real service from the container.
         *
         * @return object
         *   Returns the constructed real service.
         */
        protected function lazyLoadItself()
        {
            if (!isset($this->service)) {
                $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
            }

            return $this->service;
        }

        /**
         * {@inheritdoc}
         */
        public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
        {
            return $this->lazyLoadItself()->buildForm($form, $form_state);
        }

        /**
         * {@inheritdoc}
         */
        public function processInputParameters(array $parameters)
        {
            return $this->lazyLoadItself()->processInputParameters($parameters);
        }

        /**
         * {@inheritdoc}
         */
        public function getId()
        {
            return $this->lazyLoadItself()->getId();
        }

        /**
         * {@inheritdoc}
         */
        public function getName()
        {
            return $this->lazyLoadItself()->getName();
        }

        /**
         * {@inheritdoc}
         */
        public function getFormId()
        {
            return $this->lazyLoadItself()->getFormId();
        }

        /**
         * {@inheritdoc}
         */
        public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
        {
            return $this->lazyLoadItself()->submitForm($form, $form_state);
        }

        /**
         * {@inheritdoc}
         */
        public function setPretixClient(\ItkDev\Pretix\Api\Client $client)
        {
            return $this->lazyLoadItself()->setPretixClient($client);
        }

        /**
         * {@inheritdoc}
         */
        public function setEventInfo(array $eventInfo)
        {
            return $this->lazyLoadItself()->setEventInfo($eventInfo);
        }

        /**
         * {@inheritdoc}
         */
        public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container)
        {
            \Drupal\Core\Form\FormBase::create($container);
        }

        /**
         * {@inheritdoc}
         */
        public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
        {
            return $this->lazyLoadItself()->validateForm($form, $form_state);
        }

        /**
         * {@inheritdoc}
         */
        public function setConfigFactory(\Drupal\Core\Config\ConfigFactoryInterface $config_factory)
        {
            return $this->lazyLoadItself()->setConfigFactory($config_factory);
        }

        /**
         * {@inheritdoc}
         */
        public function resetConfigFactory()
        {
            return $this->lazyLoadItself()->resetConfigFactory();
        }

        /**
         * {@inheritdoc}
         */
        public function setRequestStack(\Symfony\Component\HttpFoundation\RequestStack $request_stack)
        {
            return $this->lazyLoadItself()->setRequestStack($request_stack);
        }

        /**
         * {@inheritdoc}
         */
        public function __sleep()
        {
            return $this->lazyLoadItself()->__sleep();
        }

        /**
         * {@inheritdoc}
         */
        public function __wakeup()
        {
            return $this->lazyLoadItself()->__wakeup();
        }

        /**
         * {@inheritdoc}
         */
        public function setLinkGenerator(\Drupal\Core\Utility\LinkGeneratorInterface $generator)
        {
            return $this->lazyLoadItself()->setLinkGenerator($generator);
        }

        /**
         * {@inheritdoc}
         */
        public function setLoggerFactory(\Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory)
        {
            return $this->lazyLoadItself()->setLoggerFactory($logger_factory);
        }

        /**
         * {@inheritdoc}
         */
        public function setMessenger(\Drupal\Core\Messenger\MessengerInterface $messenger)
        {
            return $this->lazyLoadItself()->setMessenger($messenger);
        }

        /**
         * {@inheritdoc}
         */
        public function messenger()
        {
            return $this->lazyLoadItself()->messenger();
        }

        /**
         * {@inheritdoc}
         */
        public function setRedirectDestination(\Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination)
        {
            return $this->lazyLoadItself()->setRedirectDestination($redirect_destination);
        }

        /**
         * {@inheritdoc}
         */
        public function setStringTranslation(\Drupal\Core\StringTranslation\TranslationInterface $translation)
        {
            return $this->lazyLoadItself()->setStringTranslation($translation);
        }

        /**
         * {@inheritdoc}
         */
        public function setUrlGenerator(\Drupal\Core\Routing\UrlGeneratorInterface $generator)
        {
            return $this->lazyLoadItself()->setUrlGenerator($generator);
        }

    }

}
