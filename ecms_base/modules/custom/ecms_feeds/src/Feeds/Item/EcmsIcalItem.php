<?php

namespace Drupal\ecms_feeds\Feeds\Item;

use Drupal\feeds\Feeds\Item\BaseItem;

/**
 * {@inheritdoc}
 */
class EcmsIcalItem extends BaseItem {

  // ICAL Variables.
  /**
   * {@inheritdoc}
   */
  protected $dtstart;

  /**
   * {@inheritdoc}
   */
  protected $dtend;

  /**
   * {@inheritdoc}
   */
  protected $dtstamp;

  /**
   * {@inheritdoc}
   */
  protected $uid;

  /**
   * {@inheritdoc}
   */
  protected $created;

  /**
   * {@inheritdoc}
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  protected $lastmodified;

  /**
   * {@inheritdoc}
   */
  protected $location;

  /**
   * {@inheritdoc}
   */
  protected $sequence;

  /**
   * {@inheritdoc}
   */
  protected $status;

  /**
   * {@inheritdoc}
   */
  protected $summary;

  /**
   * {@inheritdoc}
   */
  protected $transp;

}
