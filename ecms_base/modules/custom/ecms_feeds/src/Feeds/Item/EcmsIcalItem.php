<?php

namespace Drupal\ecms_feeds\Feeds\Item;

use Drupal\feeds\Feeds\Item\BaseItem;

/**
 * Defines an item class for use with an Ical document.
 */
class EcmsIcalItem extends BaseItem {

  // ICAL Variables.
  protected $dtstart;
  protected $dtend;
  protected $dtstamp;
  protected $uid;
  protected $created;
  protected $description;
  protected $lastmodified;
  protected $location;
  protected $sequence;
  protected $status;
  protected $summary;
  protected $transp;

}
