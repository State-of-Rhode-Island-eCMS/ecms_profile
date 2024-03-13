<?php

declare(strict_types=1);

namespace Drupal\ecms_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Helper service for the ecms_api base class.
 *
 * @package Drupal\ecms_api
 */
class EcmsApiHelper {

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The stream_wrapper.public service.
   *
   * @var \Drupal\Core\StreamWrapper\PublicStream
   */
  private $publicStreamWrapper;

  /**
   * EcmsApiHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\StreamWrapper\PublicStream $publicStream
   *   The stream_wrapper.public service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PublicStream $publicStream) {
    $this->entityTypeManager = $entityTypeManager;
    $this->publicStreamWrapper = $publicStream;
  }

  /**
   * Get the file path from file entity id.
   *
   * @param int $sourceFile
   *   The id of a file entity.
   *
   * @return string|null
   *   The real path to the file or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFilePath(int $sourceFile): ?string {
    $storage = $this->entityTypeManager->getStorage('file');
    /** @var \Drupal\file\FileInterface $file */
    $file = $storage->load($sourceFile);

    // Guard against an empty entity.
    if (empty($file)) {
      return NULL;
    }

    // Get the uri to the file.
    $path = $file->getFileUri();

    $host = parse_url($path, PHP_URL_HOST);
    $path = parse_url($path, PHP_URL_PATH);
    $realpath = $this->publicStreamWrapper->realpath();

    return "{$realpath}/{$host}{$path}";
  }

}
