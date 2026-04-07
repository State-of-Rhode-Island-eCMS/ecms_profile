<?php

declare(strict_types=1);

namespace Drupal\ecms_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The request.stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * EcmsApiHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\StreamWrapper\PublicStream $publicStream
   *   The stream_wrapper.public service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PublicStream $publicStream, RequestStack $requestStack) {
    $this->entityTypeManager = $entityTypeManager;
    $this->publicStreamWrapper = $publicStream;
    $this->requestStack = $requestStack;
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

  /**
   * Get the current request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request|null
   *   The current request object.
   */
  public function getCurrentRequest(): ?Request {
    return $this->requestStack->getCurrentRequest();
  }

}
