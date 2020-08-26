<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_base\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that installation finished correctly and known resources are available.
 *
 * @group ecms
 * @group ecms_base
 *
 */
class InstallationTest extends BrowserTestBase {

  protected $profile = 'ecms_base';

  protected $defaultTheme = 'stark';


  /**
   * Test the the ACSF modules are not installed.
   */
  public function testLandingPage() {
    $account = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-enable');
    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-duplication-enable');
    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-theme-enable');
    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-variables-enable');
  }

}
