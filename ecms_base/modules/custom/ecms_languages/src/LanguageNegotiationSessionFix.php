<?php

declare(strict_types = 1);

namespace Drupal\ecms_languages;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSession;
use Symfony\Component\HttpFoundation\Request;

/**
 * Alter the session language negotiator to add query params on node operations.
 *
 * @package Drupal\ecms_languages
 */
class LanguageNegotiationSessionFix extends LanguageNegotiationSession {

  /**
   * Node operations to add the language query parameter.
   */
  const NODE_OPERATIONS = [
    'edit',
    'delete',
  ];

  /**
   * {@inheritDoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL): string {
    $parent = parent::processOutbound($path, $options, $request, $bubbleable_metadata);

    $parts = explode('/', ltrim($path, '/'));

    // Make sure we have the correct path length.
    if (count($parts) !== 3) {
      return $parent;
    }

    // Make sure we are dealing with a node.
    if ($parts[0] !== 'node') {
      return $parent;
    }

    // We only care about a few operations.
    if (!in_array($parts[2], self::NODE_OPERATIONS, TRUE)) {
      return $parent;
    }

    // Make sure we have an entity.
    if (empty($options['entity'])) {
      return $parent;
    }

    // Make sure we have a language.
    if (empty($options['language'])) {
      return $parent;
    }

    /** @var \Drupal\Core\Language\LanguageInterface $language */
    $language = $options['language'];

    // Add the language id to the query string.
    $options['query']['language'] = $language->getId();

    // Add cache metadata.
    $bubbleable_metadata
      ->addCacheTags($this->config->get('language.negotiation')->getCacheTags())
      ->addCacheContexts(['url.query_args:language']);

    return $path;
  }

}