<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\file\FileInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the EcmsApiHelper class.
 *
 * @package Drupal\Tests\ecms_api\Unit
 * @group ecms_api
 */
class EcmsApiHelperTest extends UnitTestCase {

  /**
   * A static file id to test with.
   */
  const FILE_ID = 2020;

  /**
   * Mock of the entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityTypeManager;

  /**
   * Mock of the stream_wrapper.public service.
   *
   * @var \Drupal\Core\StreamWrapper\PublicStream|\PHPUnit\Framework\MockObject\MockObject
   */
  private $publicStreamWrapper;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->publicStreamWrapper = $this->createMock(PublicStream::class);
  }

  /**
   * Test the getFilePath method.
   *
   * @param string|null $fileUri
   *   The URI of the file to test with.
   * @param string|null $expected
   *   The expected full path to the file or null.
   *
   * @dataProvider dataProviderForTestGetFilePath
   */
  public function testGetFilePath(?string $fileUri, ?string $expected): void {
    $file = $expected;

    if (!empty($expected)) {
      $file = $this->createMock(FileInterface::class);
      $file->expects($this->once())
        ->method('getFileUri')
        ->willReturn($fileUri);

      $realpath = "/var/www/html/sites/default/files";
      $this->publicStreamWrapper->expects($this->once())
        ->method('realpath')
        ->willReturn($realpath);
    }

    $fileStorage = $this->createMock(EntityStorageInterface::class);
    $fileStorage->expects($this->once())
      ->method('load')
      ->with(self::FILE_ID)
      ->willReturn($file);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($fileStorage);

    $testClass = new EcmsApiHelper($this->entityTypeManager, $this->publicStreamWrapper);
    $actual = $testClass->getFilePath(self::FILE_ID);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for the testGetFilePath method.
   *
   * @return array
   *   Array of parameters to pass to the testGetFilePath method.
   */
  public function dataProviderForTestGetFilePath(): array {
    return [
      'test1' => [NULL, NULL],
      'test2' => [
        'public://filename.png',
        '/var/www/html/sites/default/files/filename.png',
      ],
      'test3' => [
        'public://path/to/file/in/sites/directory/filename.png',
        '/var/www/html/sites/default/files/path/to/file/in/sites/directory/filename.png',
      ],
    ];
  }

}
