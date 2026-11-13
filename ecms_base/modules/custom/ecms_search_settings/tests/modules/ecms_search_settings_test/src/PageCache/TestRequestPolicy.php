<?php

declare(strict_types=1);

namespace Drupal\ecms_search_settings_test\PageCache;

use Drupal\Core\PageCache\DefaultRequestPolicy;
use Drupal\Core\PageCache\RequestPolicy\NoSessionOpen;
use Drupal\Core\PageCache\RequestPolicy\CommandLineOrUnsafeMethod;
use Drupal\Core\Session\SessionConfigurationInterface;

/**
 * The default page cache request policy, minus the command-line check.
 */
class TestRequestPolicy extends DefaultRequestPolicy {

  /**
   * {@inheritdoc}
   */
  public function __construct(SessionConfigurationInterface $session_configuration) {
    $this->addPolicy(new class() extends CommandLineOrUnsafeMethod {

      /**
       * {@inheritdoc}
       */
      protected function isCli() {
        return FALSE;
      }

    });
    $this->addPolicy(new NoSessionOpen($session_configuration));
  }

}
