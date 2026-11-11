<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_blocks\Unit\Form;

use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\ecms_blocks\Form\SearchStateBlockForm;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;

/**
 * Unit tests for the statewide search URL.
 */
#[CoversMethod(SearchStateBlockForm::class, 'buildSearchUrl')]
#[Group('ecms')]
#[Group('ecms_blocks')]
class SearchStateBlockFormTest extends UnitTestCase {

  /**
   * RI.gov, which hosts multisite search, links to its own search page.
   */
  public function testSearchUrlWithMultisiteSearchRoute(): void {
    $route_provider = $this->createMock(RouteProviderInterface::class);
    $route_provider->expects($this->once())
      ->method('getRoutesByNames')
      ->with([SearchStateBlockForm::MULTISITE_SEARCH_ROUTE])
      ->willReturn([SearchStateBlockForm::MULTISITE_SEARCH_ROUTE => new Route('/search-rhode-island')]);

    $url = SearchStateBlockForm::buildSearchUrl('dmv hours', $route_provider);

    $this->assertFalse($url->isExternal());
    $this->assertSame(SearchStateBlockForm::MULTISITE_SEARCH_ROUTE, $url->getRouteName());
    $this->assertSame(['search_api_fulltext' => 'dmv hours'], $url->getOption('query'));
  }

  /**
   * Other sites link to the RI.gov statewide search page.
   */
  public function testSearchUrlWithoutMultisiteSearchRoute(): void {
    $route_provider = $this->createMock(RouteProviderInterface::class);
    $route_provider->method('getRoutesByNames')->willReturn([]);

    $url = SearchStateBlockForm::buildSearchUrl('dmv hours', $route_provider);

    $this->assertTrue($url->isExternal());
    $this->assertSame('https://www.ri.gov/search/result.php', $url->getUri());
    $this->assertSame(['q' => 'dmv hours'], $url->getOption('query'));
  }

}
