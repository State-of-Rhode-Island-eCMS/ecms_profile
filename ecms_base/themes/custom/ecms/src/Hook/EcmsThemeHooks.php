<?php

declare(strict_types=1);

namespace Drupal\ecms\Hook;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Hook implementations for the ecms theme.
 */
class EcmsThemeHooks {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LanguageManagerInterface $languageManager,
    private readonly EntityRepositoryInterface $entityRepository,
    private readonly RendererInterface $renderer,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Implements hook_preprocess_menu().
   *
   * Ensures the main menu's render cache is invalidated whenever theme settings
   * change. Without this, toggling the mega_menu setting has no effect until
   * the cache is manually cleared, because the block render cache has no
   * dependency on the theme settings config object.
   */
  #[Hook('preprocess_menu')]
  public function preprocessMenu(array &$variables): void {
    if ($variables['menu_name'] === 'main') {
      $variables['#cache']['tags'][] = 'config:ecms.settings';
    }
  }

  /**
   * Implements hook_preprocess_menu__main__megamenu().
   *
   * Adds a 'description' key to each menu item, sourced from the title
   * attribute stored in the link's URL options (where Drupal stores the
   * menu link description field).
   */
  #[Hook('preprocess_menu__main__megamenu')]
  public function preprocessMenuMainMegamenu(array &$variables): void {
    $this->addDescriptions($variables['items']);
  }

  /**
   * Recursively adds 'description' to each item from its URL title attribute.
   */
  private function addDescriptions(array &$items): void {
    foreach ($items as &$item) {
      $options = $item['url']->getOptions();
      $item['description'] = $options['attributes']['title'] ?? '';
      if (!empty($item['below'])) {
        $this->addDescriptions($item['below']);
      }
    }
  }

  /**
   * Implements hook_preprocess_node__person().
   *
   * Builds three template variables:
   *   - categories: comma-separated string of resolved parent/own term names.
   *   - additional_fields: array of label/value render arrays from the
   *     person_additional_fields paragraph field.
   *   - social_links: array of link objects (url, title, icon, is_website) for
   *     the card view display social row.
   */
  #[Hook('preprocess_node__person')]
  public function preprocessNodePerson(array &$variables): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];
    $language = $this->languageManager->getCurrentLanguage()->getId();

    // --- Categories ---
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $categories = $node->get('field_person_category')->referencedEntities();
    $variables['categories'] = [];

    foreach ($categories as $category) {
      $parents = $term_storage->loadParents($category->id());

      if (!empty($parents)) {
        $parent = reset($parents);
        $parent_trans = $this->entityRepository->getTranslationFromContext($parent, $language);
        $variables['categories'][] = $parent_trans->getName();
      }
      else {
        $category_trans = $this->entityRepository->getTranslationFromContext($category, $language);
        $variables['categories'][] = $category_trans->getName();
      }
    }

    $variables['categories'] = implode(', ', $variables['categories']);

    // --- Additional fields ---
    if (!$node->field_person_additional_fields->isEmpty()) {
      $paragraphs = $node->field_person_additional_fields->referencedEntities();
      $variables['additional_fields'] = [];

      foreach ($paragraphs as $paragraph) {
        $field_label = NULL;

        if (
          $paragraph->hasField('field_person_field_label') &&
          !$paragraph->get('field_person_field_label')->isEmpty() &&
          $paragraph->get('field_person_field_label')->entity instanceof TermInterface
        ) {
          $term_trans = $this->entityRepository->getTranslationFromContext(
            $paragraph->get('field_person_field_label')->entity,
            $language
          );
          $field_label = $term_trans->getName();
          $this->renderer->addCacheableDependency($variables['additional_fields'], $term_trans);
        }

        $paragraph_trans = $this->entityRepository->getTranslationFromContext($paragraph, $language);
        $field_value = $paragraph_trans->get('field_person_field_value')->getString();
        $variables['additional_fields'][] = [
          'label' => [
            '#markup' => $field_label,
            '#access' => !is_null($field_label),
          ],
          'value' => [
            '#markup' => $field_value,
            '#access' => !empty($field_value),
          ],
        ];
        $this->renderer->addCacheableDependency($variables['additional_fields'], $paragraph);
      }
    }

    // --- Photo ---
    $variables['photo'] = NULL;
    $photo_media = $node->field_person_photo->entity ?? NULL;
    if ($photo_media) {
      $image_item = $photo_media->field_personal_photo_image;
      $file = $image_item->entity ?? NULL;
      if ($file) {
        $variables['photo'] = [
          'src' => $this->fileUrlGenerator->generateString($file->getFileUri()),
          'alt' => $image_item->alt ?? '',
          'width' => (int) ($image_item->width ?? 0),
          'height' => (int) ($image_item->height ?? 0),
        ];
      }
    }

    // --- Social links ---
    $variables['social_links'] = [];

    if ($node->hasField('field_person_social_links') && !$node->field_person_social_links->isEmpty()) {
      foreach ($node->field_person_social_links as $link_item) {
        $url = $link_item->uri ?? '';
        $title = $link_item->title ?? '';
        $icon = $this->socialIconFromUrl($url);
        $variables['social_links'][] = [
          'url' => $url,
          'title' => $title,
          'icon' => $icon,
          'is_website' => $icon === 'globe',
        ];
      }
    }
  }

  /**
   * Maps a URL to a social media icon name.
   *
   * Returns the SVG filename (without extension) from @icons/ that best
   * matches the URL's domain. Falls back to 'globe' for unknown URLs.
   */
  private function socialIconFromUrl(string $url): string {
    $map = [
      'facebook.com' => 'social-facebook',
      'twitter.com' => 'social-twitter',
      'x.com' => 'social-twitter',
      'instagram.com' => 'social-instagram',
      'youtube.com' => 'social-youtube',
      'linkedin.com' => 'social-linkedin',
      'bsky.app' => 'social-blusky',
      'bluesky.social' => 'social-blusky',
      'flickr.com' => 'social-flickr',
      'blogger.com' => 'social-blogger',
    ];
    foreach ($map as $domain => $icon) {
      if (str_contains($url, $domain)) {
        return $icon;
      }
    }
    if (str_contains($url, '/feed') || str_ends_with($url, '.rss')) {
      return 'feed';
    }
    return 'globe';
  }

}
