<?php

declare(strict_types=1);

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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

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
   * Mock of a route object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Routing\Route
   */
  private $route;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->request = $this->createMock(Request::class);
    $this->bubbleCache = $this->createMock(BubbleableMetadata::class);
    $this->entity = $this->createMock(EntityInterface::class);
    $this->language = $this->createMock(LanguageInterface::class);
    $this->route = $this->createMock(Route::class);
  }

  /**
   * Test the processOutbound method.
   *
   * @param string $path
   *   The path to test with.
   * @param bool $entity
   *   Whether an entity is expected.
   * @param string|null $entityFormRoute
   *   The expected return for the _entity_form route default.
   * @param bool $langCode
   *   Whether a language is expected.
   * @param bool $methodsCalled
   *   Whether the custom process methods will be called.
   *
   * @dataProvider dataProviderForTestProcessOutbound
   */
  public function testProcessOutbound(string $path, bool $entity, ?string $entityFormRoute, bool $langCode, bool $methodsCalled): void {

    $options = [];
    $options['entity'] = $this->entity;
    $options['route'] = $this->route;
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

    if ($entity && $langCode) {
      $this->route->expects($this->once())
        ->method('getDefault')
        ->with('_entity_form')
        ->willReturn($entityFormRoute);
    }

    $this->language->expects($this->exactly($methodCount))
      ->method('getId')
      ->willReturn('de');

    $this->bubbleCache->expects($this->exactly($methodCount))
      ->method('addCacheTags')
      ->willReturnSelf();

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
    $requestStack = $this->createMock(RequestStack::class);

    $testClass = new LanguageNegotiationSessionFix($requestStack);

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
      'test0' => [
        'node/123',
        TRUE,
        NULL,
        TRUE,
        FALSE,
      ],
      'test1' => [
        'node/123/edit',
        TRUE,
        $this->randomMachineName(),
        TRUE,
        TRUE,
      ],
      'test2' => [
        'node/123/delete',
        TRUE,
        $this->randomMachineName(),
        TRUE,
        TRUE,
      ],
      'test3' => [
        'node/123/edit',
        FALSE,
        $this->randomMachineName(),
        TRUE,
        FALSE,
      ],
      'test4' => [
        'node/123/delete',
        FALSE,
        $this->randomMachineName(),
        TRUE,
        FALSE,
      ],
      'test5' => [
        'node/123/edit',
        TRUE,
        $this->randomMachineName(),
        FALSE,
        FALSE,
      ],
      'test6' => [
        'node/123/delete',
        TRUE,
        $this->randomMachineName(),
        FALSE,
        FALSE,
      ],
      'test7' => [
        'not/a/node/path',
        FALSE,
        $this->randomMachineName(),
        FALSE,
        FALSE,
      ],
      'test8' => [
        'media/123/delete',
        TRUE,
        $this->randomMachineName(),
        TRUE,
        TRUE,
      ],
      'test9' => [
        'media/123/edit',
        TRUE,
        $this->randomMachineName(),
        TRUE,
        TRUE,
      ],
      'test10.1' => [
        'taxonomy/term/123',
        TRUE,
        NULL,
        TRUE,
        FALSE,
      ],
      'test10' => [
        'taxonomy/term/123/edit',
        TRUE,
        $this->randomMachineName(),
        TRUE,
        TRUE,
      ],
      'test11' => [
        'taxonomy/term/123/delete',
        TRUE,
        $this->randomMachineName(),
        TRUE,
        TRUE,
      ],
      'test12' => [
        'taxonomy/term/123/edit',
        TRUE,
        $this->randomMachineName(),
        FALSE,
        FALSE,
      ],
      'test13' => [
        'taxonomy/term/123/delete',
        TRUE,
        $this->randomMachineName(),
        FALSE,
        FALSE,
      ],
      'test14' => [
        'block/123/?language=de',
        TRUE,
        $this->randomMachineName(),
        TRUE,
        TRUE,
      ],
      'test15' => [
        'block/123/delete?language=de',
        TRUE,
        $this->randomMachineName(),
        TRUE,
        TRUE,
      ],
    ];
  }

}
