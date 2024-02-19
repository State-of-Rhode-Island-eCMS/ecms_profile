<?php

declare(strict_types=1);

namespace Drupal\ecms_feeds\Feeds\Parser;

use Drupal\ecms_feeds\Feeds\Item\EcmsIcalItem;
use Drupal\feeds\Component\XmlParserTrait;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use ICal\ICal;

/**
 * Defines an Ical Feeds parser.
 *
 * @FeedsParser(
 *   id = "feeds_ecms_ical",
 *   title = @Translation("eCMS iCal Parser"),
 *   description = @Translation("Parse iCal Feed.")
 * )
 */
class EcmsIcalParser extends PluginBase implements ParserInterface {
  use XmlParserTrait;

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {

    // Look at our response.
    $rawResponseString = trim($fetcher_result->getRaw());
    if (!strlen($rawResponseString)) {
      throw new EmptyFeedException();
    }

    // Create our result object to add to.
    $result = new ParserResult();

    // Use our ical class to parse the feed.
    try {

      // Create ical parsing object.
      $ical = new ICal('ICal.ics', [
        'defaultSpan'                 => 2,
        'defaultTimeZone'             => 'UTC',
        'defaultWeekStart'            => 'MO',
        'disableCharacterReplacement' => FALSE,
        'skipRecurrence'              => FALSE,
        'useTimeZoneWithRRules'       => FALSE,
      ]);

      $ical->initString($rawResponseString);

      // Get and loop over events.
      $events = $ical->events();
      foreach ($events as $eventIndex => $event) {

        // Create item to return for processing.
        $itemObject = new EcmsIcalItem();

        // Loop over information from feed and add to item.
        foreach ($event as $eventProperty => $eventPropertyValue) {

          switch ($eventProperty) {
            case 'dtstart':
              $itemObject->set($eventProperty, $event->dtstart_array[2]);
              break;

            case 'dtend':
              $itemObject->set($eventProperty, $event->dtend_array[2]);
              break;

            default:
              $itemObject->set($eventProperty, $eventPropertyValue);

          }
        }

        // Add item to results.
        $result->addItem($itemObject);

      }

    }

    catch (\Exception $e) {
      \Drupal::logger('ecms_feeds')->error($e);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources(): array {
    return [
      'dtstart' => [
        'label' => $this->t('DTSTART'),
      ],
      'dtend' => [
        'label' => $this->t('DTEND'),
      ],
      'dtstamp' => [
        'label' => $this->t('DTSTAMP'),
      ],
      'uid' => [
        'label' => $this->t('UID'),
        'suggestions' => [
          'targets' => ['guid'],
        ],
      ],
      'created' => [
        'label' => $this->t('CREATED'),
      ],
      'description' => [
        'label' => $this->t('DESCRIPTION'),
      ],
      'lastmodified' => [
        'label' => $this->t('LAST-MODIFIED'),
      ],
      'location' => [
        'label' => $this->t('LOCATION'),
      ],
      'rrule' => [
        'label' => $this->t('RRULE'),
      ],
      'sequence' => [
        'label' => $this->t('SEQUENCE'),
      ],
      'status' => [
        'label' => $this->t('STATUS'),
      ],
      'summary' => [
        'label' => $this->t('SUMMARY'),
        'suggestions' => [
          'targets' => ['title'],
        ],
      ],
      'transp' => [
        'label' => $this->t('TRANSP'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return [];
  }

}
