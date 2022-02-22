<?php

namespace Drupal\itk_pretix\Exporter;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use ItkDev\Pretix\Api\Client;

/**
 * Exporter interface.
 */
interface ExporterInterface extends FormInterface, ContainerInjectionInterface {

  /**
   * Get id.
   */
  public function getId();

  /**
   * Get name.
   */
  public function getName();

  /**
   * Process input parameters.
   */
  public function processInputParameters(array $parameters);

  /**
   * Set pretix client.
   */
  public function setPretixClient(Client $client);

  /**
   * Set event info.
   */
  public function setEventInfo(array $eventInfo);

}
