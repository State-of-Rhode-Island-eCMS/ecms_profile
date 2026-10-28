<?php

declare(strict_types=1);

namespace Drupal\ecms_search_settings\EventSubscriber;

use Drupal\search_api_solr\Event\PreQueryEvent;
use Solarium\Component\ComponentAwareQueryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets Solr highlight fields that were indexed without offsets.
 *
 * Non-English text field types (text_es, text_pt_pt, text_fr, etc.) in the
 * shared SearchStax schema lack storeOffsetsWithPositions. With "Retrieve
 * highlighting" enabled, the unified highlighter throws a 500 on those fields
 * and the whole search fails. Computing offsets from the stored text avoids
 * that without a schema change or reindex.
 */
class SolrHighlightSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // PreQueryEvent::class resolves to a string without autoloading, so this
    // stays safe on sites without search_api_solr.
    return [PreQueryEvent::class => 'onPreQuery'];
  }

  /**
   * Computes highlight offsets by re-analyzing stored text.
   *
   * @param \Drupal\search_api_solr\Event\PreQueryEvent $event
   *   The pre query event.
   */
  public function onPreQuery(PreQueryEvent $event): void {
    $query = $event->getSolariumQuery();
    if ($query instanceof ComponentAwareQueryInterface
      && $query->getComponent(ComponentAwareQueryInterface::COMPONENT_HIGHLIGHTING)) {
      $query->addParam('hl.offsetSource', 'ANALYSIS');
    }
  }

}
