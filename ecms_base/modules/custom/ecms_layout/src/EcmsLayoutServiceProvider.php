<?php

declare(strict_types=1);

namespace Drupal\ecms_layout;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;

/**
 * Swaps the class of core's menu active trail service.
 *
 * Only the class changes. Core keeps its arguments, tags and lazy flag, so a
 * core release that changes them needs no edit here. The swap runs after
 * core's ProxyServicesPass so core's own proxy wraps the subclass.
 *
 * @see \Drupal\ecms_layout\EcmsMenuActiveTrail
 * @see \Drupal\Core\DependencyInjection\Compiler\ProxyServicesPass
 */
final class EcmsLayoutServiceProvider extends ServiceProviderBase implements CompilerPassInterface {

  /**
   * The ID core gives the real service behind the lazy proxy.
   */
  private const PROXIED_SERVICE_ID = 'drupal.proxy_original_service.menu.active_trail';

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    // Core adds ProxyServicesPass at the default priority of 0. A lower
    // priority runs after it.
    $container->addCompilerPass($this, PassConfig::TYPE_BEFORE_OPTIMIZATION, -10);
  }

  /**
   * {@inheritdoc}
   */
  public function process(SymfonyContainerBuilder $container): void {
    // Fall back to the service itself in case core stops making it lazy.
    foreach ([self::PROXIED_SERVICE_ID, 'menu.active_trail'] as $id) {
      if ($container->hasDefinition($id)) {
        $container->getDefinition($id)->setClass(EcmsMenuActiveTrail::class);
        return;
      }
    }
  }

}
