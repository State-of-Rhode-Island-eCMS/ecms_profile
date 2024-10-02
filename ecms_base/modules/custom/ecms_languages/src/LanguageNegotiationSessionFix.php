<?php

declare(strict_types=1);

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
   * {@inheritDoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL): string {
    if (empty($path)) {
      return '';
    }
    $parent = parent::processOutbound($path, $options, $request, $bubbleable_metadata);

    // Make sure we have an entity.
    if (empty($options['entity'])) {
      return $parent;
    }

    // Make sure we have a language.
    if (empty($options['language'])) {
      return $parent;
    }

    /** @var \Symfony\Component\Routing\Route $proposedRoute */
    $proposedRoute = $options['route'];
    $form = $proposedRoute->getDefault('_entity_form');

    // If the proposed route is not a form, do not specify a translation.
    if (empty($form)) {
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
