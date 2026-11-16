<?php

declare(strict_types=1);

namespace Drupal\ecms_multisite_search\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
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
   * Visible characters kept before the first highlight.
   */
  protected const LEAD_LENGTH = 60;

  /**
   * Placeholder for Solr's [HIGHLIGHT] marker while the excerpt is cut.
   */
  protected const OPEN = "\u{E000}";

  /**
   * Placeholder for Solr's [/HIGHLIGHT] marker while the excerpt is cut.
   */
  protected const CLOSE = "\u{E001}";

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
   * The index sets Solr's fragsize to 0, so a snippet can be a whole field.
   * The excerpt is therefore a window of visible text around the first
   * highlight (or the start of the text when nothing is highlighted). The
   * [HIGHLIGHT] markers are swapped for single placeholder characters first,
   * so they never count towards the length and a cut can never split one.
   *
   * @param string[] $snippets
   *   Plain-text snippets with [HIGHLIGHT] markers from Solr.
   *
   * @return \Drupal\Core\Render\Markup
   *   The excerpt markup.
   */
  protected function buildExcerpt(array $snippets): Markup {
    $text = implode(' … ', array_map('trim', $snippets));
    // Remove any placeholder characters already in the text before swapping
    // the markers for them, so only the markers become highlights.
    $text = str_replace([self::OPEN, self::CLOSE], '', $text);
    $text = str_replace(['[HIGHLIGHT]', '[/HIGHLIGHT]'], [self::OPEN, self::CLOSE], $text);

    $chars = mb_str_split($text);
    $visible = array_keys(array_filter($chars, fn($char) => $char !== self::OPEN && $char !== self::CLOSE));
    $length = count($visible);

    // Start a little before the first highlight, at a word boundary.
    $start = 0;
    $first = array_search(self::OPEN, $chars, TRUE);
    if ($first !== FALSE && $length > self::MAX_LENGTH) {
      $visible_before = count(array_filter($visible, fn($index) => $index < $first));
      $start = max(0, min($visible_before - self::LEAD_LENGTH, $length - self::MAX_LENGTH));
    }
    $end = min($length, $start + self::MAX_LENGTH);

    // Map the visible-character window back onto the marked-up characters.
    $from = $start > 0 ? $visible[$start] : 0;
    $to = $end < $length ? $visible[$end] : count($chars);
    $window = implode('', array_slice($chars, $from, $to - $from));

    // Keep whole words at both cut edges.
    if ($start > 0) {
      $window = preg_replace('/^\S*\s+/u', '', $window, 1);
    }
    if ($end < $length) {
      $window = preg_replace('/\s+\S*$/u', '', $window, 1);
    }

    $window = $this->balanceMarkers(trim($window));
    $html = str_replace([self::OPEN, self::CLOSE], ['<strong>', '</strong>'], Html::escape($window));
    $excerpt = ($start > 0 ? '… ' : '') . $html . ($end < $length ? '…' : '');
    // The text is already escaped; the filter makes sure <strong> stays the
    // only markup even if the steps above change.
    return Markup::create(Xss::filter($excerpt, ['strong']));
  }

  /**
   * Drops unmatched closing markers and closes an unterminated highlight.
   *
   * A window can start inside a highlight or end before it closes.
   *
   * @param string $text
   *   Text with placeholder highlight markers.
   *
   * @return string
   *   The text with every highlight opened and closed exactly once.
   */
  protected function balanceMarkers(string $text): string {
    $balanced = '';
    $open = FALSE;
    foreach (mb_str_split($text) as $char) {
      if ($char === self::OPEN) {
        if ($open) {
          continue;
        }
        $open = TRUE;
      }
      elseif ($char === self::CLOSE) {
        if (!$open) {
          continue;
        }
        $open = FALSE;
      }
      $balanced .= $char;
    }
    return $open ? $balanced . self::CLOSE : $balanced;
  }

}
