<?php

declare(strict_types = 1);

namespace Drupal\ecms_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StreamWrapper\PublicStream;

class EcmsApiMediaHelper {

  protected $entityTypeManager;

  private $publicStreamWrapper;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, PublicStream $publicStream) {
    $this->entityTypeManager = $entityTypeManager;
    $this->publicStreamWrapper = $publicStream;
  }

  public function getFilePath(int $sourceFile): ?string {
    $storage = $this->entityTypeManager->getStorage('file');
    /** @var \Drupal\file\FileInterface $file */
    $file = $storage->load($sourceFile);

    // Guard against an empty entity.
    if (empty($file)) {
      return NULL;
    }

    // @todo: Get the realpath to the file.
    $path = $file->getFileUri();

    $host = parse_url($path, PHP_URL_HOST);
    $path = parse_url($path, PHP_URL_PATH);
    $realpath = $this->publicStreamWrapper->realpath();

    return "{$realpath}/{$host}{$path}";
  }

  private function getRealPath(string $uri): ?string {
    $path = $this->publicStreamWrapper->realpath();

    if (empty($path)) {
      return NULL;
    }

    return $path;
  }

}
