<?php

declare(strict_types=1);

namespace Drupal\ecms_distribution\TwigExtension;

use Drupal\Core\Extension\ThemeExtensionList;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig functions exposed by the ecms distribution.
 */
final class EcmsTwigExtension extends AbstractExtension {

  public function __construct(
    private readonly ThemeExtensionList $themeList,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('ecms_read_file', $this->readFile(...), ['is_safe' => ['html']]),
    ];
  }

  /**
   * Reads a file from inside the ecms theme directory.
   *
   * Bypass for Drupal's Twig FilesystemLoader extension whitelist
   * (css/html/js/svg/twig only) — use this for colocated data files such as
   * JSON manifests next to an SDC.
   *
   * @param string $relative_path
   *   Path relative to the ecms theme root. Directory traversal (`..`) and
   *   absolute paths are rejected.
   *
   * @return string
   *   The file contents, or an empty string if missing or unreadable.
   */
  public function readFile(string $relative_path): string {
    if ($relative_path === '' || str_contains($relative_path, '..') || str_starts_with($relative_path, '/')) {
      return '';
    }
    $full_path = $this->themeList->getPath('ecms') . '/' . $relative_path;
    return is_readable($full_path) ? (string) file_get_contents($full_path) : '';
  }

}
