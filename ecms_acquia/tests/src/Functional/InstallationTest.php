<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_acquia\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that installation finished correctly and known resources are available.
 *
 * @group ecms
 * @group ecms_acquia
 *
 */
class InstallationTest extends BrowserTestBase {

  protected $profile = 'ecms_acquia';

  protected $defaultTheme = 'stark';


  /**
   * Test the home page is loading.
   */
  public function testLandingPage() {
    $account = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->checkboxChecked('edit-modules-acsf-enable');
    $this->assertSession()->checkboxChecked('edit-modules-acsf-duplication-enable');
    $this->assertSession()->checkboxChecked('edit-modules-acsf-theme-enable');
    $this->assertSession()->checkboxChecked('edit-modules-acsf-variables-enable');
  }

}