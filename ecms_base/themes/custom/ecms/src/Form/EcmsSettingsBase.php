<?php

declare(strict_types=1);

namespace Drupal\ecms\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract base class for ecms theme settings form alter sections.
 */
abstract class EcmsSettingsBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Construct a new settings form.
   */
  public function __construct(
    protected ThemeSettingsProvider $themeSettingsProvider,
    protected ThemeManagerInterface $themeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(ThemeSettingsProvider::class),
      $container->get('theme.manager'),
    );
  }

}
