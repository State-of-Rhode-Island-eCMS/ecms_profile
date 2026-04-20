<?php

declare(strict_types=1);

namespace Drupal\ecms_distribution;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides views.views_data to use a database-backed cache bin.
 *
 * The services.yml definitions are merged in module load order, so an override
 * in ecms_distribution.services.yml can be silently overwritten by
 * views.services.yml if views loads later. ServiceProvider::alter() runs
 * after all *.services.yml files are merged, guaranteeing the override sticks.
 *
 * @see ecms_distribution.services.yml
 */
class EcmsDistributionServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if ($container->hasDefinition('views.views_data')) {
      $container->getDefinition('views.views_data')
        ->setArgument(0, new Reference('cache.views_data'));
    }
  }

}
