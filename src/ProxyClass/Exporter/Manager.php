<?php
// phpcs:ignoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\itk_pretix\Exporter\Manager' "web/modules/contrib/itk_pretix/src".
 */

namespace Drupal\itk_pretix\ProxyClass\Exporter {

    /**
     * Provides a proxy class for \Drupal\itk_pretix\Exporter\Manager.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class Manager implements \Drupal\itk_pretix\Exporter\ManagerInterface
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
         * @var \Drupal\itk_pretix\Exporter\Manager
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
        public function addEventExporter(\Drupal\itk_pretix\Exporter\ExporterInterface $exporter, $priority = 0)
        {
            return $this->lazyLoadItself()->addEventExporter($exporter, $priority);
        }

        /**
         * {@inheritdoc}
         */
        public function getEventExporters(?array $ids = NULL)
        {
            return $this->lazyLoadItself()->getEventExporters($ids);
        }

        /**
         * {@inheritdoc}
         */
        public function getEventExporter(string $id)
        {
            return $this->lazyLoadItself()->getEventExporter($id);
        }

        /**
         * {@inheritdoc}
         */
        public function saveExporterResult(\Drupal\node\NodeInterface $node, \GuzzleHttp\Psr7\Response $response)
        {
            return $this->lazyLoadItself()->saveExporterResult($node, $response);
        }

        /**
         * {@inheritdoc}
         */
        public function fileDownload(string $uri)
        {
            return $this->lazyLoadItself()->fileDownload($uri);
        }

        /**
         * {@inheritdoc}
         */
        public function setStringTranslation(\Drupal\Core\StringTranslation\TranslationInterface $translation)
        {
            return $this->lazyLoadItself()->setStringTranslation($translation);
        }

    }

}
