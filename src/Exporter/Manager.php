<?php

namespace Drupal\itk_pretix\Exporter;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
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
  private const string EXPORTER_RESULT_BASE_URL = 'private://itk_pretix/exporters';

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
   * Constructor.
   */
  public function __construct(
    private readonly FileSystemInterface $fileSystem,
    private readonly AccessCheck $accessCheck,
    private readonly AccountInterface $currentUser,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Add an event exporter.
   */
  #[\Override]
  public function addEventExporter(ExporterInterface $exporter, $priority = 0) {
    $this->eventExporters[$exporter->getId()] = $exporter;
    $this->eventExporterForms[$exporter->getFormId()] = $exporter;

    return $this;
  }

  /**
   * Get event exporters.
   */
  #[\Override]
  public function getEventExporters(?array $ids = NULL) {
    return array_filter($this->eventExporters, static fn(ExporterInterface $exporter) => NULL === $ids || in_array($exporter->getId(), $ids, TRUE));
  }

  /**
   * Get event exporter.
   */
  #[\Override]
  public function getEventExporter(string $id) {
    return $this->eventExporters[$id] ?? NULL;
  }

  /**
   * Save exporter result to local file system.
   */
  #[\Override]
  public function saveExporterResult(NodeInterface $node, Response $response) {
    $header = $response->getHeaderLine('content-disposition');
    if (preg_match('/filename="(?<filename>[^"]+)"/', $header, $matches)) {
      $filename = $matches['filename'];

      $url = $this->getExporterResultFileUrl($node, $filename);
      $directory = dirname((string) $url);
      $this->fileSystem->prepareDirectory($directory, FileSystem::CREATE_DIRECTORY);
      $filePath = $this->fileSystem->realpath($url);
      $this->fileSystem->saveData((string) $response->getBody(), $filePath, FileExists::Replace);
      $this->fileSystem->saveData(json_encode($response->getHeaders()), $filePath . '.headers', FileExists::Replace);

      return $this->fileUrlGenerator->generateAbsoluteString($url);
    }

    return NULL;
  }

  /**
   * Implementation of itk_pretix_file_download.
   *
   * @param string $uri
   *   The file uri.
   */
  #[\Override]
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
