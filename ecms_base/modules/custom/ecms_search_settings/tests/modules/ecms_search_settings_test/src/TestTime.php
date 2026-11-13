<?php

declare(strict_types=1);

namespace Drupal\ecms_search_settings_test;

use Drupal\Component\Datetime\Time;

/**
 * A time service whose request time can be set by tests.
 */
class TestTime extends Time {

  /**
   * The request time to report, or NULL to use the real one.
   */
  public static ?int $requestTime = NULL;

  /**
   * {@inheritdoc}
   */
  public function getRequestTime(): int {
    return static::$requestTime ?? parent::getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTime(): int {
    return static::$requestTime ?? parent::getCurrentTime();
  }

}
