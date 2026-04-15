<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_distribution\Kernel;

use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\ecms_distribution\EcmsDistributionServiceProvider;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\ViewsData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Verifies that views.views_data uses a database-backed cache bin.
 *
 * The fix has two parts:
 *   1. ecms_distribution.services.yml defines cache.views_data as a
 *      DatabaseBackend, giving ViewsData its own atomic cache bin.
 *   2. EcmsDistributionServiceProvider::alter() rewires views.views_data to
 *      inject @cache.views_data instead of @cache.default. The ServiceProvider
 *      runs after all *.services.yml files are merged, so the override cannot
 *      be silently overwritten by views.services.yml loading after us.
 *
 * @see \Drupal\ecms_distribution\EcmsDistributionServiceProvider
 * @see https://thinkoomph.jira.com/browse/ONSA-833
 * @see ecms_distribution.services.yml
 */
#[Group('ecms_distribution')]
#[CoversClass(EcmsDistributionServiceProvider::class)]
class ViewsDataCacheOverrideTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'views',
    'ecms_distribution',
  ];

  /**
   * The service provider rewires views.views_data to use @cache.views_data.
   *
   * This tests the alter() method directly: builds a minimal ContainerBuilder
   * that mimics the pre-alter state (views.views_data using @cache.default),
   * runs the provider, and asserts the first argument was replaced.
   *
   * This is the authoritative test for the service provider class. The kernel
   * tests below validate the compiled result, but this test isolates the
   * provider's own logic independently of the full container compile.
   */
  public function testAlterRewiresViewsDataToViewsDataCacheBin(): void {
    $container = new ContainerBuilder();

    // Register a stand-in for views.views_data using @cache.default, matching
    // the pre-alter state from views.services.yml.
    $container->register('views.views_data', ViewsData::class)
      ->addArgument(new Reference('cache.default'));

    $provider = new EcmsDistributionServiceProvider();
    $provider->alter($container);

    $arguments = $container->getDefinition('views.views_data')->getArguments();
    $first_arg = reset($arguments);

    $this->assertInstanceOf(Reference::class, $first_arg);
    $this->assertSame(
      'cache.views_data',
      (string) $first_arg,
      'alter() must replace the first argument of views.views_data with ' .
      '@cache.views_data. If this fails, the service provider is not ' .
      'overriding the cache backend and ViewsData will write to cache.default ' .
      '(memcache), leaving the RIGA-833 race condition open.'
    );
  }

  /**
   * The cache.views_data bin resolves to a DatabaseBackend in the container.
   *
   * Validates the service definition in ecms_distribution.services.yml.
   */
  public function testViewsDataCacheBinIsDatabaseBackend(): void {
    $cache = $this->container->get('cache.views_data');
    $this->assertInstanceOf(
      DatabaseBackend::class,
      $cache,
      'cache.views_data must be a DatabaseBackend. A memcache backend here ' .
      'allows concurrent workers to race on multipart chunk writes, producing ' .
      'partially-deserialized handler definitions with entity_type: "" (RIGA-833).'
    );
  }

  /**
   * The compiled container injects a DatabaseBackend into views.views_data.
   *
   * Validates that both the services.yml definition and the ServiceProvider
   * alter() work together correctly end-to-end in the full compiled container.
   */
  public function testViewsDataServiceReceivesDatabaseCacheBackend(): void {
    $views_data = $this->container->get('views.views_data');
    $this->assertInstanceOf(ViewsData::class, $views_data);

    $reflection = new \ReflectionProperty($views_data, 'cacheBackend');
    $cache = $reflection->getValue($views_data);

    $this->assertInstanceOf(
      DatabaseBackend::class,
      $cache,
      'views.views_data must be injected with a DatabaseBackend. If this ' .
      'fails, EcmsDistributionServiceProvider::alter() is not taking effect ' .
      'and ViewsData is still writing to cache.default (memcache), leaving ' .
      'the RIGA-833 multipart write-race condition open.'
    );
  }

}
