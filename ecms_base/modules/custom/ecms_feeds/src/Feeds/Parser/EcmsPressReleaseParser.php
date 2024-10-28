<?php

declare(strict_types=1);

namespace Drupal\ecms_feeds\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Parser\SyndicationParser;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Laminas\Feed\Reader\Exception\ExceptionInterface;
use Laminas\Feed\Reader\Reader;

/**
 * Define a RSS feed parser specific for ri.gov press releases.
 *
 * This will place the <categories> into the feed_agency field for mapping.
 *
 * @FeedsParser(
 *   id = "ecms_agency_syndication",
 *   title = @Translation("RI.gov Agency Syndication"),
 *   description = @Translation("RSS syndication that includes the agency in the channel.")
 * )
 */
class EcmsPressReleaseParser extends SyndicationParser {

  /**
   * {@inheritdoc}
   */
  public function parse(
    FeedInterface $feed,
    FetcherResultInterface $fetcher_result,
    StateInterface $state,
  ): ParserResultInterface {
    /** @var \Drupal\feeds\Result\ParserResultInterface $result */
    $results = parent::parse($feed, $fetcher_result, $state);

    $raw = $fetcher_result->getRaw();
    if (!strlen(trim($raw))) {
      throw new EmptyFeedException();
    }

    try {
      $channel = Reader::importString($raw);
    }
    catch (ExceptionInterface $e) {
      $args = [
        '%site' => $feed->label() ?? '',
        '%error' => trim($e->getMessage()),
      ];
      throw new \RuntimeException(sprintf('The feed from %s seems to be broken because of error "%s".', $args['%site'], $args['%error']));
    }

    $agencies = $channel->getCategories()->getValues();

    $agency = (empty($agencies)) ? '' : $agencies[0];

    foreach ($results as $item) {
      $item->set('feed_agency', $agency);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources(): array {
    $mappings = parent::getMappingSources();

    $mappings['feed_agency'] = [
      'label' => $this->t('Feed agency'),
      'description' => $this->t('The agency that published the press release (<channel>).'),
    ];

    return $mappings;
  }

}
