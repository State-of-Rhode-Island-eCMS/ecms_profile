<?php

declare(strict_types=1);

namespace Drupal\ecms_layout;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Swaps the class of core's menu active trail service.
 *
 * Only the class changes. Core keeps its arguments, tags and lazy flag, so a
 * core release that changes them needs no edit here. The lazy flag needs the
 * proxy class in src/ProxyClass/EcmsMenuActiveTrail.php.
 *
 * @see \Drupal\ecms_layout\EcmsMenuActiveTrail
 */
final class EcmsLayoutServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if ($container->hasDefinition('menu.active_trail')) {
      $container->getDefinition('menu.active_trail')
        ->setClass(EcmsMenuActiveTrail::class);
    }
  }

}
