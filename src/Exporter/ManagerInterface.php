<?php

namespace Drupal\itk_pretix\Exporter;

use Drupal\node\NodeInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Exporter manager interface.
 */
interface ManagerInterface {

  /**
   * Add an event exporter.
   */
  public function addEventExporter(ExporterInterface $exporter, $priority = 0);

  /**
   * Get event exporters.
   *
   * @param array|null $ids
   *   Filter on exporter ids.
   *
   * @return array|ExporterInterface[]
   *   The exporters.
   */
  public function getEventExporters(array $ids = NULL);

  /**
   * Get event exporter.
   */
  public function getEventExporter(string $id);

  /**
   * Save exporter result to local file system.
   */
  public function saveExporterResult(NodeInterface $node, Response $response);

  /**
   * Implementation of itk_pretix_file_download.
   *
   * @param string $uri
   *   The file uri.
   */
  public function fileDownload(string $uri);

}
