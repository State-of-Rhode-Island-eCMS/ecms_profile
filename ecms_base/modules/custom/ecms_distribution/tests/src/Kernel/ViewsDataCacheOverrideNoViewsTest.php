<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_distribution\Kernel;

use Drupal\ecms_distribution\EcmsDistributionServiceProvider;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies EcmsDistributionServiceProvider::alter() is safe without views.
 *
 * Uses a container compiled without the views module to confirm that alter()
 * does not throw when views.views_data is not registered — guarding against
 * a fatal error if ecms_distribution is ever enabled without views.
 *
 * Kept in a separate class from ViewsDataCacheOverrideTest because $modules
 * is static: the views module must be absent from the compiled container for
 * this test to be meaningful.
 *
 * @see \Drupal\ecms_distribution\EcmsDistributionServiceProvider
 */
#[Group('ecms_distribution')]
#[CoversClass(EcmsDistributionServiceProvider::class)]
class ViewsDataCacheOverrideNoViewsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'ecms_distribution',
  ];

  /**
   * alter() does not throw when views.views_data is absent from the container.
   *
   * Tests against the real compiled kernel container (not a hand-built stub)
   * to confirm the hasDefinition() guard in alter() works under actual
   * bootstrap conditions.
   */
  public function testAlterIsNoopWhenViewsNotPresent(): void {
    $this->assertFalse(
      $this->container->hasDefinition('views.views_data'),
      'Precondition: views.views_data must not be registered. If this fails, ' .
      'the views module was loaded and the test is not exercising the no-op path.'
    );

    $provider = new EcmsDistributionServiceProvider();

    // Must not throw when views.views_data is absent.
    $provider->alter($this->container);

    // Container is unchanged.
    $this->assertFalse($this->container->hasDefinition('views.views_data'));
  }

}
