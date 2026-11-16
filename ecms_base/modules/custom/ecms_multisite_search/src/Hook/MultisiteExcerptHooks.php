<?php

declare(strict_types=1);

namespace Drupal\ecms_multisite_search\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Markup;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Query\ResultSetInterface;

/**
 * Builds result excerpts for the multisite index from Solr highlighting.
 *
 * The Search API highlight processor cannot build excerpts for this index:
 * it reads field values per datasource, but multisite result items carry the
 * originating site's datasource ID (e.g. entity:node) rather than
 * ecms_multisite_all, so it finds no text. Solr's own highlighting still
 * returns snippets, so the excerpt is built from those instead.
 */
class MultisiteExcerptHooks {

  /**
   * The index this applies to.
   */
  protected const INDEX_ID = 'ecms_multisite_index';

  /**
   * The Search API field whose snippets make up the excerpt.
   */
  protected const FIELD = 'rendered_item';

  /**
   * Maximum excerpt length, in characters, before markup is added.
   */
  protected const MAX_LENGTH = 256;

  /**
   * Implements hook_search_api_results_alter().
   */
  #[Hook('search_api_results_alter')]
  public function resultsAlter(ResultSetInterface $results): void {
    if ($results->getQuery()->getIndex()->id() !== self::INDEX_ID) {
      return;
    }

    $response = $results->getExtraData('search_api_solr_response');
    $highlighting = $response['highlighting'] ?? [];

    foreach ($results->getResultItems() as $item) {
      if ($item->getExcerpt()) {
        continue;
      }
      $document = $item->getExtraData('search_api_solr_document');
      $solr_id = $document['id'] ?? NULL;

      $snippets = [];
      foreach ($highlighting[$solr_id] ?? [] as $solr_field => $field_snippets) {
        // Solr field names are language specific, e.g.
        // tcngramm_X3b_en_rendered_item.
        if (str_ends_with($solr_field, '_' . self::FIELD)) {
          array_push($snippets, ...$field_snippets);
        }
      }

      // Without search keys (or a match in the body) there is nothing to
      // highlight, so fall back to the start of the item's text.
      if (!$snippets) {
        $snippets = [$this->leadText($item)];
      }

      if (array_filter($snippets)) {
        $item->setExcerpt($this->buildExcerpt($snippets));
      }
    }
  }

  /**
   * Returns the item's indexed text without its leading title.
   *
   * The rendered item starts with the page title, which the result card
   * already shows.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The result item.
   *
   * @return string
   *   The text, or an empty string if the item has none.
   */
  protected function leadText(ItemInterface $item): string {
    $values = $item->getField(self::FIELD, FALSE)?->getValues() ?? [];
    $text = trim(implode(' ', array_map('strval', $values)));
    $title = trim((string) ($item->getField('title', FALSE)?->getValues()[0] ?? ''));
    if ($title !== '' && str_starts_with($text, $title)) {
      $text = ltrim(substr($text, strlen($title)));
    }
    return $text;
  }

  /**
   * Joins Solr snippets into a short, safe excerpt with <strong> highlights.
   *
   * @param string[] $snippets
   *   Plain-text snippets with [HIGHLIGHT] markers from Solr.
   *
   * @return \Drupal\Core\Render\Markup
   *   The excerpt markup.
   */
  protected function buildExcerpt(array $snippets): Markup {
    $text = implode(' … ', array_map('trim', $snippets));
    $text = Unicode::truncate($text, self::MAX_LENGTH, TRUE, TRUE);
    // An unterminated marker can be left behind by the truncation.
    if (substr_count($text, '[HIGHLIGHT]') > substr_count($text, '[/HIGHLIGHT]')) {
      $text .= '[/HIGHLIGHT]';
    }
    $text = str_replace(
      ['[HIGHLIGHT]', '[/HIGHLIGHT]'],
      ['<strong>', '</strong>'],
      Html::escape($text),
    );
    return Markup::create($text);
  }

}
