<?php

namespace Drupal\itk_pretix\Exporter;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\itk_pretix\Access\AccessCheck;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Exporter manager.
 */
class Manager implements ManagerInterface {
  private const EXPORTER_RESULT_BASE_URL = 'private://itk_pretix/exporters';

  use StringTranslationTrait;

  /**
   * The event exporters.
   *
   * @var array|AbstractExporter[]
   */
  private $eventExporters;

  /**
   * The event exporter forms (indexed by form id).
   *
   * @var array|ExporterInterface[]
   */
  private $eventExporterForms;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  private $fileSystem;

  /**
   * The access checker.
   *
   * @var \Drupal\itk_pretix\Access\AccessCheck
   */
  private $accessCheck;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * Constructor.
   */
  public function __construct(FileSystem $fileSystem, AccessCheck $accessCheck, AccountInterface $currentUser) {
    $this->fileSystem = $fileSystem;
    $this->accessCheck = $accessCheck;
    $this->currentUser = $currentUser;
  }

  /**
   * Add an event exporter.
   */
  public function addEventExporter(ExporterInterface $exporter, $priority = 0) {
    $this->eventExporters[$exporter->getId()] = $exporter;
    $this->eventExporterForms[$exporter->getFormId()] = $exporter;

    return $this;
  }

  /**
   * Get event exporters.
   */
  public function getEventExporters(array $ids = NULL) {
    return array_filter($this->eventExporters, static function (ExporterInterface $exporter) use ($ids) {
      return NULL === $ids || in_array($exporter->getId(), $ids, TRUE);
    });
  }

  /**
   * Get event exporter.
   */
  public function getEventExporter(string $id) {
    return $this->eventExporters[$id] ?? NULL;
  }

  /**
   * Save exporter result to local file system.
   */
  public function saveExporterResult(NodeInterface $node, Response $response) {
    $header = $response->getHeaderLine('content-disposition');
    if (preg_match('/filename="(?<filename>[^"]+)"/', $header, $matches)) {
      $filename = $matches['filename'];

      $url = $this->getExporterResultFileUrl($node, $filename);
      $directory = dirname($url);
      $this->fileSystem->prepareDirectory($directory, FileSystem::CREATE_DIRECTORY);
      $filePath = $this->fileSystem->realpath($url);
      $this->fileSystem->saveData((string) $response->getBody(), $filePath, FileSystem::EXISTS_REPLACE);
      $this->fileSystem->saveData(json_encode($response->getHeaders()), $filePath . '.headers', FileSystem::EXISTS_REPLACE);

      return file_create_url($url);
    }

    return NULL;
  }

  /**
   * Implementation of itk_pretix_file_download.
   *
   * @param string $uri
   *   The file uri.
   */
  public function fileDownload(string $uri) {
    $info = $this->getExporterResultFileUrlInfo($uri);
    if (isset($info['nid'])) {
      $node = Node::load($info['nid']);
      if ($this->accessCheck->canRunExport($node, $this->currentUser)) {
        // Try to get headers from actual exporter run.
        $filePath = $this->fileSystem->realpath($uri . '.headers');
        if ($filePath) {
          $headers = json_decode(file_get_contents($filePath), TRUE);
          if ($headers) {
            return $headers;
          }
        }

        // Fall back to simple content-disposition header.
        $filename = basename($uri);
        return [
          'content-disposition' => 'attachment; filename="' . $filename . '"',
        ];
      }

      return -1;
    }

    return NULL;
  }

  /**
   * Get file url for storing exporter result in local file system.
   */
  private function getExporterResultFileUrl(NodeInterface $node, string $filename) {
    return sprintf('%s/%s/%s', self::EXPORTER_RESULT_BASE_URL, $node->id(), $filename);
  }

  /**
   * Get info on exporter result from local file uri.
   */
  private function getExporterResultFileUrlInfo(string $uri) {
    if (preg_match(
      '@^' . preg_quote(self::EXPORTER_RESULT_BASE_URL, '@') . '/(?P<nid>[^/]+)/(?P<filename>.+)$@',
      $uri,
      $matches
    )) {
      return [
        'nid' => $matches['nid'],
        'filename' => $matches['filename'],
      ];
    }

    return NULL;
  }

}
