<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_languages\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecms_languages\LanguageNegotiationSessionFix;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit tests for the LanguageNegotiationSessionFix class.
 *
 * @package Drupal\Tests\ecms_languages\Unit
 * @group ecms_languages
 */
class LanguageNegotiationSessionFixTest extends UnitTestCase {

  /**
   * Mock the request.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * Mock the BubbleableMetadata cache.
   *
   * @var \Drupal\Core\Render\BubbleableMetadata|\PHPUnit\Framework\MockObject\MockObject
   */
  private $bubbleCache;

  /**
   * Mock an entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entity;

  /**
   * Mock a language object.
   *
   * @var \Drupal\Core\Language\LanguageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $language;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->request = $this->createMock(Request::class);
    $this->bubbleCache = $this->createMock(BubbleableMetadata::class);
    $this->entity = $this->createMock(EntityInterface::class);
    $this->language = $this->createMock(LanguageInterface::class);
  }

  /**
   * Test the processOutbound method.
   *
   * @param string $path
   *   The path to test with.
   * @param bool $entity
   *   Whether an entity is expected.
   * @param bool $langCode
   *   Whether a language is expected.
   * @param bool $methodsCalled
   *   Whether the custom process methods will be called.
   *
   * @dataProvider dataProviderForTestProcessOutbound
   */
  public function testProcessOutbound(string $path, bool $entity, bool $langCode, bool $methodsCalled): void {

    $options = [];
    $options['entity'] = $this->entity;
    $methodCount = 0;

    if ($langCode) {
      $options['language'] = $this->language;
    }

    if (!$entity) {
      unset($options['entity']);
    }

    if ($methodsCalled) {
      $methodCount = 1;
    }

    $this->language->expects($this->exactly($methodCount))
      ->method('getId')
      ->willReturn('de');

    $this->bubbleCache->expects($this->exactly($methodCount))
      ->method('addCacheTags')
      ->willReturnSelf();;

    $this->bubbleCache->expects($this->exactly($methodCount))
      ->method('addCacheContexts')
      ->with(['url.query_args:language'])
      ->willReturnSelf();

    $user = $this->createMock(AccountInterface::class);
    $user->expects($this->once())
      ->method('isAnonymous')
      ->willReturn(FALSE);

    $immutableConfig = $this->createMock(ImmutableConfig::class);
    $immutableConfig->expects($this->exactly($methodCount))
      ->method('getCacheTags')
      ->willReturn([]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->expects($this->exactly($methodCount))
      ->method('get')
      ->with('language.negotiation')
      ->willReturn($immutableConfig);

    $languageManager = $this->createMock(ConfigurableLanguageManager::class);

    $testClass = new LanguageNegotiationSessionFix();

    $testClass->setCurrentUser($user);
    $testClass->setConfig($configFactory);
    $testClass->setLanguageManager($languageManager);

    $result = $testClass->processOutbound($path, $options, $this->request, $this->bubbleCache);

    $this->assertEquals($path, $result);
  }

  /**
   * Data provider for the testProcessOutbound method.
   *
   * @return array[]
   *   Parameters to pass to testProcessOutbound.
   */
  public function dataProviderForTestProcessOutbound(): array {
    return [
      'test1' => [
        'node/123/edit',
        TRUE,
        TRUE,
        TRUE,
      ],
      'test2' => [
        'node/123/delete',
        TRUE,
        TRUE,
        TRUE,
      ],
      'test3' => [
        'node/123/edit',
        FALSE,
        TRUE,
        FALSE,
      ],
      'test4' => [
        'node/123/delete',
        FALSE,
        TRUE,
        FALSE,
      ],
      'test5' => [
        'node/123/edit',
        TRUE,
        FALSE,
        FALSE,
      ],
      'test6' => [
        'node/123/delete',
        TRUE,
        FALSE,
        FALSE,
      ],
      'test7' => [
        'not/a/node/path',
        FALSE,
        FALSE,
        FALSE,
      ],
      'test8' => [
        'node/path/add',
        TRUE,
        TRUE,
        FALSE,
      ],
      'test9' => [
        'path/to/delete',
        TRUE,
        TRUE,
        FALSE,
      ],
    ];
  }

}
